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

        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $path,
            'httponly' => true,
            'secure'   => $isSecure,
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

    public static function generateCsrfToken(): string {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(string $token): bool {
        self::start();
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    public static function requireValidCsrf(?array $jsonPayload = null): void {
        self::start();

        $token = trim($_POST['csrf_token'] ?? '');
        if ($token === '' && $jsonPayload !== null) {
            $token = trim($jsonPayload['csrf_token'] ?? '');
        }
        if ($token === '' && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = trim($_SERVER['HTTP_X_CSRF_TOKEN']);
        }

        if (!self::verifyCsrfToken($token)) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid or missing security token. Please refresh the page.',
            ]);
            exit;
        }
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
        session_regenerate_id(true);
        $_SESSION['UserRole']           = 'student';
        $_SESSION['StudentNumber']      = trim($studentNumber);
        $_SESSION['CollegeDepartment'] = $department;
        $_SESSION['StudentEmail']       = $email;
        unset($_SESSION['AdminID'], $_SESSION['AdminUsername'], $_SESSION['AdminName']);
    }

    public static function setAdminSession(int $adminId, string $username, string $fullName, string $email = ''): void {
        self::start();
        session_regenerate_id(true);
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
