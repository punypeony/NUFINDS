<?php
class SessionHelper {

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set(string $key, mixed $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function isLoggedIn(): bool {
        self::start();
        return !empty($_SESSION['StudentNumber']);
    }

    public static function requireLogin(string $redirect = '../../pages/login.html'): void {
        if (!self::isLoggedIn()) {
            header("Location: $redirect");
            exit;
        }
    }

    public static function destroy(): void {
        self::start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();
    }
}