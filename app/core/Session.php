<?php
class Session {

    public static function getCorrectDomain(): string {
        return self::cookieDomain();
    }

    public static function isHttpsPublic(): bool {
        return self::isHttps();
    }

    private static function cookieDomain(): string {
        $host  = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
        $parts = explode('.', $host);
        $count = count($parts);

        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return '';
        } elseif ($count >= 3 && $parts[$count - 1] === 'in' && $parts[$count - 2] === 'co') {
            return '.' . implode('.', array_slice($parts, -3));
        } elseif ($count >= 2) {
            return '.' . implode('.', array_slice($parts, -2));
        }
        return '';
    }

    private static function isHttps(): bool {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    }

    public static function init(): void {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.gc_maxlifetime', '28800');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');

            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => self::cookieDomain(),
                'secure'   => self::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            session_start();
        }

        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }

    public static function set($key, $value): void {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        return $_SESSION[$key] ?? null;
    }

    public static function destroy(): void {
        if (session_status() === PHP_SESSION_NONE) {
            self::init();
        }

        $domain   = self::cookieDomain();
        $https    = self::isHttps();
        $name     = session_name();
        $bare     = ltrim($domain, '.');
        $host     = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));

        session_unset();

        // Expire the session cookie under every domain variant the browser may have stored
        $domains = array_unique(array_filter([$domain, $bare, $host]));
        foreach ($domains as $d) {
            foreach ([$name, 'PHPSESSID'] as $cn) {
                setcookie($cn, '', [
                    'expires'  => time() - 3600,
                    'path'     => '/',
                    'domain'   => $d,
                    'secure'   => $https,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
        }

        session_destroy();
    }

    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    public static function getUser(): array {
        return [
            'id'    => $_SESSION['user_id']    ?? null,
            'name'  => $_SESSION['user_name']  ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'role'  => $_SESSION['role']       ?? null,
        ];
    }
}
