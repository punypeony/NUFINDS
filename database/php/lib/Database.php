<?php
class Database {
    private static ?mysqli $instance = null;
    private static bool $configLoaded = false;

    private static string $host = '127.0.0.1';
    private static string $user = 'root';
    private static string $password = '';
    private static string $dbname = 'nufindsdb';

    private function __construct() {}
    private function __clone() {}

    private static function loadConfig(): void {
        if (self::$configLoaded) {
            return;
        }
        self::$configLoaded = true;

        $candidates = [
            dirname(__DIR__, 3) . '/config.php',
            dirname(dirname(__DIR__, 3)) . '/nufinds_config.php',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                require_once $path;
                break;
            }
        }

        if (defined('DB_HOST')) {
            self::$host = DB_HOST;
        }
        if (defined('DB_USER')) {
            self::$user = DB_USER;
        }
        if (defined('DB_PASS')) {
            self::$password = DB_PASS;
        }
        if (defined('DB_NAME')) {
            self::$dbname = DB_NAME;
        }
    }

    private static function isProduction(): bool {
        return defined('NUFINDS_ENV') && NUFINDS_ENV === 'production';
    }

    public static function connect(): mysqli {
        if (self::$instance === null) {
            self::loadConfig();

            self::$instance = new mysqli(
                self::$host,
                self::$user,
                self::$password,
                self::$dbname
            );

            if (self::$instance->connect_error) {
                error_log('NUFINDS database connection failed: ' . self::$instance->connect_error);

                $message = self::isProduction()
                    ? 'Database connection failed. Please try again later.'
                    : 'Database connection failed. Copy config.example.php to config.php and check MySQL.';

                die(json_encode([
                    'status' => 'error',
                    'message' => $message,
                ]));
            }
        }

        return self::$instance;
    }
}
