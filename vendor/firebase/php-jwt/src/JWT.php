<?php
namespace Firebase\JWT;

class JWT {
    public static function encode($payload, $key, $alg = 'HS256') {
        $header = json_encode(['typ' => 'JWT', 'alg' => $alg]);
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $key, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    public static function decode($jwt, $key) {
        $parts = explode('.', $jwt);
        if (count($parts) != 3) {
            throw new \Exception('Invalid JWT');
        }
        
        list($header, $payload, $signature) = $parts;
        
        $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $header)));
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)));
        
        $validSignature = str_replace(['+', '/', '='], ['-', '_', ''], 
            base64_encode(hash_hmac('sha256', $parts[0] . "." . $parts[1], $key->getKeyMaterial(), true)));
        
        if ($signature !== $validSignature) {
            throw new \Exception('Invalid signature');
        }
        
        if (isset($payload->exp) && $payload->exp < time()) {
            throw new \Exception('Token expired');
        }
        
        return $payload;
    }
}
?>