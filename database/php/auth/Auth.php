<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/Database.php');
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/AdminAuth.php');
nufinds_require('lib/LoginAttemptLimiter.php');

class Auth {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public function loginStudent(string $email, string $password): array {
        $email = strtolower(trim($email));
        if ($email === '' || $password === '') {
            return ['status' => 'error', 'message' => 'Please enter your email and password.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => 'Please enter a valid email address.'];
        }

        if (AdminAuth::isAdminEmail($email)) {
            return ['status' => 'error', 'message' => 'Use your admin password for this email.'];
        }

        $sql = 'SELECT StudentNumber, CollegeDepartment, StudentEmail, PasswordHash
                FROM studentinfo
                WHERE LOWER(StudentEmail) = ?';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Query preparation failed.'];
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if (!$result || $result->num_rows !== 1) {
            return ['status' => 'error', 'message' => 'Login failed. Please check your credentials.', 'count_attempt' => true];
        }

        $row = $result->fetch_assoc();
        if (empty($row['PasswordHash']) || !password_verify($password, $row['PasswordHash'])) {
            return ['status' => 'error', 'message' => 'Login failed. Please check your credentials.', 'count_attempt' => true];
        }

        SessionHelper::setStudentSession(
            $row['StudentNumber'],
            $row['CollegeDepartment'],
            $row['StudentEmail']
        );

        return ['status' => 'success', 'message' => 'Login successful.', 'role' => 'student'];
    }

    public function logout(): void {
        SessionHelper::destroy();
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Location: ' . nufinds_pages_url('login.html'), true, 303);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $loginType = trim($_POST['LoginType'] ?? 'student');
    $email     = trim($_POST['StudentEmail'] ?? '');

    $lockout = LoginAttemptLimiter::checkLocked($email);
    if ($lockout !== null) {
        echo json_encode($lockout);
        exit;
    }

    if ($loginType === 'admin' || AdminAuth::isAdminEmail($email)) {
        $adminAuth = new AdminAuth();
        $result    = $adminAuth->loginByEmail($email, $_POST['AdminPassword'] ?? '');
    } else {
        $auth   = new Auth();
        $result = $auth->loginStudent($email, $_POST['StudentPassword'] ?? '');
    }

    if ($result['status'] === 'success') {
        LoginAttemptLimiter::clear($email);
    } elseif (!empty($result['count_attempt'])) {
        $result = LoginAttemptLimiter::recordFailure($email, $result);
        unset($result['count_attempt']);
    }

    echo json_encode($result);
    exit;
}
