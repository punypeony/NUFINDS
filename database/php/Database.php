<?php
class Database {
    private static ?mysqli $instance = null;

    private static string $host = '127.0.0.1';
    private static string $user = 'root';
    private static string $password = '';
    private static string $dbname = 'nufindsdb';

    private function __construct() {}
    private function __clone() {}

    public static function connect(): mysqli {
        if (self::$instance === null) {
            self::$instance = new mysqli(
                self::$host,
                self::$user,
                self::$password,
                self::$dbname
            );

            if (self::$instance->connect_error) {
                die(json_encode([
                    'status' => 'error',
                    'message' => 'Database connection failed: ' . self::$instance->connect_error
                ]));
            }
        }

        return self::$instance;
    }
}