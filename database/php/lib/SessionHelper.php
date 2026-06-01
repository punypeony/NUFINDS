<?php
class SessionHelper {

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            self::applyCookieParams();
            session_start();
        }
    }

    private static function applyCookieParams(): void {
        $script = trim(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/');
        $path   = '/';

        if ($script !== '') {
            $segments = explode('/', $script);
            if ($segments[0] !== '') {
                $path = '/' . $segments[0] . '/';
            }
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $path,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function set(string $key, mixed $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function isAdmin(): bool {
        self::start();
        return ($_SESSION['UserRole'] ?? '') === 'admin';
    }

    public static function isStudent(): bool {
        self::start();
        return ($_SESSION['UserRole'] ?? '') === 'student' && !empty($_SESSION['StudentNumber']);
    }

    public static function isLoggedIn(): bool {
        self::start();
        return self::isStudent() || self::isAdmin();
    }

    public static function setStudentSession(string $studentNumber, string $department, string $email): void {
        self::start();
        $_SESSION['UserRole']           = 'student';
        $_SESSION['StudentNumber']      = trim($studentNumber);
        $_SESSION['CollegeDepartment'] = $department;
        $_SESSION['StudentEmail']       = $email;
        unset($_SESSION['AdminID'], $_SESSION['AdminUsername'], $_SESSION['AdminName']);
    }

    public static function setAdminSession(int $adminId, string $username, string $fullName, string $email = ''): void {
        self::start();
        $_SESSION['UserRole']      = 'admin';
        $_SESSION['AdminID']       = $adminId;
        $_SESSION['AdminUsername'] = $username;
        $_SESSION['AdminName']     = $fullName;
        $_SESSION['AdminEmail']    = $email;
        unset($_SESSION['StudentNumber'], $_SESSION['CollegeDepartment'], $_SESSION['StudentEmail']);
    }

    public static function requireLogin(?string $redirect = null): void {
        if (!function_exists('nufinds_pages_url')) {
            require_once __DIR__ . '/bootstrap.php';
        }

        if (!self::isLoggedIn()) {
            $redirect ??= nufinds_pages_url('login.html');
            header("Location: $redirect");
            exit;
        }
    }

    public static function requireStudent(?string $redirect = null): void {
        if (!function_exists('nufinds_pages_url')) {
            require_once __DIR__ . '/bootstrap.php';
        }

        if (self::isAdmin()) {
            header('Location: ' . nufinds_admin_page('home.html'));
            exit;
        }

        if (!self::isStudent()) {
            $redirect ??= nufinds_pages_url('login.html');
            header("Location: $redirect");
            exit;
        }
    }

    public static function requireAdmin(?string $redirect = null): void {
        if (!function_exists('nufinds_pages_url')) {
            require_once __DIR__ . '/bootstrap.php';
        }

        if (!self::isAdmin()) {
            $redirect ??= nufinds_pages_url('login.html');
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