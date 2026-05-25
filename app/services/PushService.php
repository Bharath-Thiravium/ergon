<?php
/**
 * PushService — sends Web Push (VAPID) and FCM notifications.
 *
 * Setup:
 *   1. Generate VAPID keys once:
 *      Visit https://vapidkeys.com  OR run:
 *      openssl ecparam -name prime256v1 -genkey -noout -out vapid_private.pem
 *      openssl ec -in vapid_private.pem -pubout -out vapid_public.pem
 *
 *   2. Add to .env on each server:
 *      VAPID_PUBLIC_KEY=your_base64url_public_key
 *      VAPID_PRIVATE_KEY=your_base64url_private_key
 *      VAPID_SUBJECT=mailto:admin@yourdomain.com
 *      FCM_SERVER_KEY=your_fcm_server_key
 */

class PushService {

    // ── Send to a single user (all their devices) ─────────────────────────────
    public static function sendToUser(int $userId, string $title, string $body, string $url = '', array $data = []): void {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::connect();

            $stmt = $db->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($subs as $sub) {
                if ($sub['type'] === 'web') {
                    self::sendWebPush($sub, $title, $body, $url, $data);
                } elseif ($sub['type'] === 'fcm') {
                    self::sendFCM($sub['fcm_token'], $title, $body, $url, $data);
                }
            }
        } catch (Exception $e) {
            error_log('PushService::sendToUser error: ' . $e->getMessage());
        }
    }

    // ── Web Push via VAPID ────────────────────────────────────────────────────
    private static function sendWebPush(array $sub, string $title, string $body, string $url, array $data): void {
        $vapidPublic  = $_ENV['VAPID_PUBLIC_KEY']  ?? '';
        $vapidPrivate = $_ENV['VAPID_PRIVATE_KEY'] ?? '';
        $vapidSubject = $_ENV['VAPID_SUBJECT']     ?? 'mailto:admin@example.com';

        if (!$vapidPublic || !$vapidPrivate) {
            error_log('PushService: VAPID keys not configured in .env');
            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'url'   => $url ?: '/ergon/notifications',
            'icon'  => '/ergon/assets/icons/icon-192.png',
            'badge' => '/ergon/assets/icons/icon-192.png',
            'data'  => $data,
        ]);

        $endpoint  = $sub['endpoint'];
        $p256dh    = $sub['p256dh'];
        $auth      = $sub['auth'];

        // Build VAPID JWT
        $parsedUrl = parse_url($endpoint);
        $audience  = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        $expiry    = time() + 43200;

        $header  = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims  = self::base64UrlEncode(json_encode(['aud' => $audience, 'exp' => $expiry, 'sub' => $vapidSubject]));
        $signing = $header . '.' . $claims;

        $privateKeyPem = self::vapidPrivateToPem($vapidPrivate);
        $privateKey    = openssl_pkey_get_private($privateKeyPem);
        if (!$privateKey) {
            error_log('PushService: Failed to load VAPID private key: ' . openssl_error_string());
            return;
        }

        openssl_sign($signing, $derSignature, $privateKey, OPENSSL_ALGO_SHA256);

        // Convert DER signature to raw R||S (64 bytes) for JWT ES256
        $rawSignature = self::derToRaw($derSignature);
        $jwt = $signing . '.' . self::base64UrlEncode($rawSignature);

        // Encrypt payload
        $encrypted = self::encryptPayload($payload, $p256dh, $auth);
        if (!$encrypted) {
            error_log('PushService: Payload encryption failed');
            return;
        }

        $headers = [
            'Authorization: vapid t=' . $jwt . ', k=' . $vapidPublic,
            'Content-Type: application/octet-stream',
            'Content-Encoding: aesgcm',
            'Encryption: salt=' . $encrypted['salt'],
            'Crypto-Key: dh=' . $encrypted['dh'] . ';p256ecdsa=' . $vapidPublic,
            'TTL: 86400',
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $encrypted['ciphertext'],
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        error_log("PushService Web Push [{$httpCode}] endpoint=" . substr($endpoint, 0, 60) . " response=" . substr($response, 0, 200) . ($curlError ? " curl_error=$curlError" : ''));

        if ($httpCode === 404 || $httpCode === 410) {
            try {
                $db = Database::connect();
                $db->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?")->execute([$endpoint]);
            } catch (Exception $e) {}
        }
    }

    // Convert DER-encoded ECDSA signature to raw R||S format required by JWT ES256
    private static function derToRaw(string $der): string {
        // DER structure: 0x30 [total-len] 0x02 [r-len] [r-bytes] 0x02 [s-len] [s-bytes]
        $offset = 2; // skip 0x30 and total length
        // R
        $offset++; // skip 0x02
        $rLen = ord($der[$offset++]);
        $r = substr($der, $offset, $rLen);
        $offset += $rLen;
        // S
        $offset++; // skip 0x02
        $sLen = ord($der[$offset++]);
        $s = substr($der, $offset, $sLen);

        // Pad or trim to exactly 32 bytes each
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");
        $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
        $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }

    // ── FCM (Firebase Cloud Messaging) ────────────────────────────────────────
    private static function sendFCM(string $token, string $title, string $body, string $url, array $data): void {
        $serverKey = $_ENV['FCM_SERVER_KEY'] ?? '';
        if (!$serverKey || $serverKey === 'your_fcm_key_here') {
            error_log('PushService: FCM_SERVER_KEY not configured in .env');
            return;
        }

        $payload = json_encode([
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body'  => $body,
                'icon'  => 'ic_notification',
                'sound' => 'default',
                'click_action' => $url ?: 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'data' => array_merge($data, ['url' => $url]),
            'priority' => 'high',
        ]);

        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: key=' . $serverKey,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        // Remove invalid tokens
        if (isset($result['results'][0]['error']) &&
            in_array($result['results'][0]['error'], ['InvalidRegistration', 'NotRegistered'])) {
            try {
                $db = Database::connect();
                $db->prepare("DELETE FROM push_subscriptions WHERE fcm_token = ?")->execute([$token]);
            } catch (Exception $e) {}
        }

        if ($httpCode !== 200) {
            error_log("PushService FCM failed [{$httpCode}]: {$response}");
        }
    }

    // ── Payload encryption (aesgcm) ───────────────────────────────────────────
    private static function encryptPayload(string $payload, string $p256dh, string $auth): ?array {
        try {
            $userPublicKey = self::base64UrlDecode($p256dh);
            $userAuth      = self::base64UrlDecode($auth);
            $salt          = random_bytes(16);

            // Generate server EC key pair
            $serverKey = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
            $serverKeyDetails = openssl_pkey_get_details($serverKey);
            $serverPublicKey  = $serverKeyDetails['key'];

            // Extract raw public key bytes (65 bytes uncompressed)
            $serverPublicKeyRaw = self::pemToRawPublicKey($serverPublicKey);

            // ECDH shared secret
            $userKey = openssl_pkey_get_public(self::rawPublicKeyToPem($userPublicKey));
            openssl_dh_compute_key($sharedSecret, $userKey, $serverKey);

            // HKDF
            $prk        = hash_hmac('sha256', $sharedSecret, $userAuth, true);
            $infoAuth   = "Content-Encoding: auth\x00";
            $contentKey = substr(hash_hmac('sha256', $prk . $infoAuth . "\x01", $salt, true), 0, 16);

            $infoNonce = "Content-Encoding: nonce\x00";
            $nonce     = substr(hash_hmac('sha256', $prk . $infoNonce . "\x01", $salt, true), 0, 12);

            // AES-128-GCM encrypt
            $ciphertext = openssl_encrypt(
                "\x00\x00" . $payload, // 2-byte padding
                'aes-128-gcm',
                $contentKey,
                OPENSSL_RAW_DATA,
                $nonce,
                $tag
            );

            return [
                'ciphertext' => $ciphertext . $tag,
                'salt'       => self::base64UrlEncode($salt),
                'dh'         => self::base64UrlEncode($serverPublicKeyRaw),
            ];
        } catch (Exception $e) {
            error_log('PushService encrypt error: ' . $e->getMessage());
            return null;
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }

    private static function vapidPrivateToPem(string $base64UrlKey): string {
        $raw = self::base64UrlDecode($base64UrlKey);
        // Wrap in EC private key DER structure for prime256v1
        $der = "\x30\x77\x02\x01\x01\x04\x20" . $raw
             . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07"
             . "\xa1\x44\x03\x42\x00";
        return "-----BEGIN EC PRIVATE KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END EC PRIVATE KEY-----\n";
    }

    private static function pemToRawPublicKey(string $pem): string {
        $der = base64_decode(preg_replace('/-----[^-]+-----|\s/', '', $pem));
        // Last 65 bytes are the uncompressed public key point
        return substr($der, -65);
    }

    private static function rawPublicKeyToPem(string $raw): string {
        // SubjectPublicKeyInfo DER for prime256v1
        $der = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"
             . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00" . $raw;
        return "-----BEGIN PUBLIC KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END PUBLIC KEY-----\n";
    }
}
