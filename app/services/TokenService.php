<?php
/**
 * TokenService
 *
 * Manages persistent login tokens for the Capacitor mobile app.
 *
 * Security model:
 *  - Raw token  : 32 random bytes → 64-char hex string (sent to client once)
 *  - Stored     : SHA-256 hash of the raw token (never the raw value)
 *  - Expiry     : TOKEN_TTL_DAYS days from issuance (default 30)
 *  - Rotation   : old token is revoked and a new one issued on each login
 */
class TokenService
{
    /** Token lifetime in days */
    private const TOKEN_TTL_DAYS = 30;

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Issue a new token for a user.
     * Any existing tokens for this user are revoked first (one-session policy).
     *
     * @return string  Raw 64-char hex token to send to the client.
     */
    public function issue(int $userId, string $deviceHint = ''): string
    {
        $this->revokeAllForUser($userId);

        $raw       = bin2hex(random_bytes(32));          // 64-char hex
        $hash      = hash('sha256', $raw);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_TTL_DAYS . ' days'));

        $stmt = $this->db->prepare(
            'INSERT INTO user_tokens (user_id, token_hash, device_hint, expires_at)
             VALUES (:uid, :hash, :hint, :exp)'
        );
        $stmt->execute([
            ':uid'  => $userId,
            ':hash' => $hash,
            ':hint' => mb_substr($deviceHint, 0, 255),
            ':exp'  => $expiresAt,
        ]);

        return $raw;
    }

    /**
     * Validate a raw token.
     *
     * @return array|null  User row on success, null on failure/expiry.
     */
    public function validate(string $rawToken): ?array
    {
        if (strlen($rawToken) !== 64 || !ctype_xdigit($rawToken)) {
            return null;
        }

        $hash = hash('sha256', $rawToken);

        $stmt = $this->db->prepare(
            'SELECT t.user_id, t.expires_at,
                    u.id, u.name, u.email, u.role, u.status
             FROM   user_tokens t
             JOIN   users u ON u.id = t.user_id
             WHERE  t.token_hash = :hash
               AND  t.expires_at > NOW()
               AND  u.status = \'active\'
             LIMIT  1'
        );
        $stmt->execute([':hash' => $hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $row;
    }

    /**
     * Revoke a single raw token (called on explicit logout).
     */
    public function revoke(string $rawToken): void
    {
        if (strlen($rawToken) !== 64 || !ctype_xdigit($rawToken)) {
            return;
        }

        $hash = hash('sha256', $rawToken);
        $stmt = $this->db->prepare('DELETE FROM user_tokens WHERE token_hash = :hash');
        $stmt->execute([':hash' => $hash]);
    }

    /**
     * Revoke all tokens for a user (called on password change / account lock).
     */
    public function revokeAllForUser(int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM user_tokens WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
    }

    /**
     * Delete all expired tokens (run periodically, e.g. from a cron).
     */
    public function purgeExpired(): int
    {
        $stmt = $this->db->prepare('DELETE FROM user_tokens WHERE expires_at <= NOW()');
        $stmt->execute();
        return (int) $stmt->rowCount();
    }
}
