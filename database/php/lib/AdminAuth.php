<?php

class AdminAuth {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public static function isAdminEmail(string $email): bool {
        $email = strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $conn = Database::connect();
        $stmt = $conn->prepare(
            'SELECT AdminID FROM adminaccounts WHERE LOWER(AdminEmail) = ? AND IsActive = 1 LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $isAdmin = $result && $result->num_rows === 1;
        $stmt->close();

        return $isAdmin;
    }

    public function loginByEmail(string $email, string $password): array {
        $email = strtolower(trim($email));
        if ($email === '' || $password === '') {
            return ['status' => 'error', 'message' => 'Please enter admin email and password.'];
        }

        if (!self::isAdminEmail($email)) {
            return ['status' => 'error', 'message' => 'Invalid admin credentials.', 'count_attempt' => true];
        }

        $stmt = $this->conn->prepare(
            'SELECT AdminID, Username, FullName, PasswordHash
             FROM adminaccounts
             WHERE LOWER(AdminEmail) = ? AND IsActive = 1'
        );
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Unable to process login.'];
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if (!$result || $result->num_rows !== 1) {
            return ['status' => 'error', 'message' => 'Invalid admin credentials.', 'count_attempt' => true];
        }

        $row = $result->fetch_assoc();
        if (!password_verify($password, $row['PasswordHash'])) {
            return ['status' => 'error', 'message' => 'Invalid admin credentials.', 'count_attempt' => true];
        }

        SessionHelper::setAdminSession(
            (int)$row['AdminID'],
            $row['Username'],
            $row['FullName'],
            $email
        );

        return ['status' => 'success', 'message' => 'Admin login successful.', 'role' => 'admin'];
    }
}
