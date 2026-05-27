<?php
require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/SessionHelper.php';
require_once __DIR__ . '/lib/LoginView.php';

class Auth {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public function login(string $studentNumber, string $department, string $email): array {
        if (empty($studentNumber) || empty($department) || empty($email)) {
            return ['status' => 'error', 'message' => 'Please fill in all fields.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => 'Please enter a valid email address.'];
        }

        $sql = "SELECT StudentNumber, CollegeDepartment, StudentEmail 
                FROM studentinfo 
                WHERE StudentNumber = ? AND CollegeDepartment = ? AND StudentEmail = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Query preparation failed.'];
        }

        $stmt->bind_param('sss', $studentNumber, $department, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            SessionHelper::set('StudentNumber', $row['StudentNumber']);
            SessionHelper::set('CollegeDepartment', $row['CollegeDepartment']);
            SessionHelper::set('StudentEmail', $row['StudentEmail']);

            return ['status' => 'success', 'message' => 'Login successful.'];
        }

        return ['status' => 'error', 'message' => 'Login failed. Please check your credentials.'];
    }

    public function logout(): void {
        SessionHelper::destroy();
        header('Location: ../../pages/login.html');
        exit;
    }
}

// Entry point
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $auth = new Auth();
    $result = $auth->login(
        trim($_POST['StudentNumber'] ?? ''),
        trim($_POST['CollegeDepartment'] ?? ''),
        trim($_POST['StudentEmail'] ?? '')
    );
    echo json_encode($result);
    exit;
}