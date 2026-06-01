<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/Database.php');
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/AdminAuth.php');

class Auth {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public function loginStudent(string $studentNumber, string $department, string $email): array {
        if (empty($studentNumber) || empty($department) || empty($email)) {
            return ['status' => 'error', 'message' => 'Please fill in all fields.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => 'Please enter a valid email address.'];
        }

        if (AdminAuth::isAdminEmail($email)) {
            return ['status' => 'error', 'message' => 'Use your admin password for this email.'];
        }

        $sql = 'SELECT StudentNumber, CollegeDepartment, StudentEmail
                FROM studentinfo
                WHERE StudentNumber = ? AND CollegeDepartment = ? AND StudentEmail = ?';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Query preparation failed.'];
        }

        $stmt->bind_param('sss', $studentNumber, $department, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            SessionHelper::setStudentSession(
                $row['StudentNumber'],
                $row['CollegeDepartment'],
                $row['StudentEmail']
            );

            return ['status' => 'success', 'message' => 'Login successful.', 'role' => 'student'];
        }

        return ['status' => 'error', 'message' => 'Login failed. Please check your credentials.'];
    }

    public function logout(): void {
        SessionHelper::destroy();
        header('Location: ' . nufinds_pages_url('login.html'));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $loginType = trim($_POST['LoginType'] ?? 'student');
    $email     = trim($_POST['StudentEmail'] ?? '');

    if ($loginType === 'admin' || AdminAuth::isAdminEmail($email)) {
        $adminAuth = new AdminAuth();
        $result    = $adminAuth->loginByEmail($email, $_POST['AdminPassword'] ?? '');
        echo json_encode($result);
        exit;
    }

    $auth   = new Auth();
    $result = $auth->loginStudent(
        trim($_POST['StudentNumber'] ?? ''),
        trim($_POST['CollegeDepartment'] ?? ''),
        $email
    );
    echo json_encode($result);
    exit;
}
