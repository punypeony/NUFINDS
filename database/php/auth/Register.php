<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/Database.php');
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/AdminAuth.php');

class Register {
    private const DEPARTMENTS = [
        'COLLEGE OF ALLIED HEALTH',
        'COLLEGE OF ARCHITECTURE',
        'COLLEGE OF BUSINESS AND ACCOUNTANCY',
        'COLLEGE OF COMPUTING AND INFORMATION TECHNOLOGIES',
        'COLLEGE OF EDUCATION ARTS AND SCIENCES',
        'COLLEGE OF ENGINEERING',
        'COLLEGE OF TOURISM AND HOSPITALITY MANAGEMENT',
    ];

    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::connect();
    }

    public function register(
        string $studentNumber,
        string $department,
        string $email,
        string $password,
        string $confirmPassword
    ): array {
        $studentNumber = trim($studentNumber);
        $department    = trim($department);
        $email         = strtolower(trim($email));

        if ($studentNumber === '' || $department === '' || $email === '' || $password === '' || $confirmPassword === '') {
            return ['status' => 'error', 'message' => 'Please fill in all fields.'];
        }

        if (strlen($studentNumber) > 20) {
            return ['status' => 'error', 'message' => 'Student number must be 20 characters or fewer.'];
        }

        if (!in_array($department, self::DEPARTMENTS, true)) {
            return ['status' => 'error', 'message' => 'Please select a valid college department.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => 'Please enter a valid email address.'];
        }

        $emailSuffix = '@students.national-u.edu.ph';
        if (strlen($email) <= strlen($emailSuffix) || substr($email, -strlen($emailSuffix)) !== $emailSuffix) {
            return ['status' => 'error', 'message' => 'Use your @students.national-u.edu.ph email address.'];
        }

        if (AdminAuth::isAdminEmail($email)) {
            return ['status' => 'error', 'message' => 'This email is reserved for admin access.'];
        }

        if (strlen($password) < 6) {
            return ['status' => 'error', 'message' => 'Password must be at least 6 characters.'];
        }

        if ($password !== $confirmPassword) {
            return ['status' => 'error', 'message' => 'Passwords do not match.'];
        }

        if ($this->studentNumberExists($studentNumber)) {
            return ['status' => 'error', 'message' => 'This student number is already registered.'];
        }

        if ($this->emailExists($email)) {
            return ['status' => 'error', 'message' => 'This email is already registered.'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare(
            'INSERT INTO studentinfo (StudentNumber, CollegeDepartment, StudentEmail, PasswordHash)
             VALUES (?, ?, ?, ?)'
        );
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Unable to create account. Please try again.'];
        }

        $stmt->bind_param('ssss', $studentNumber, $department, $email, $passwordHash);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            return ['status' => 'error', 'message' => 'Unable to create account. Please try again.'];
        }

        SessionHelper::setStudentSession($studentNumber, $department, $email);

        return ['status' => 'success', 'message' => 'Account created successfully.', 'role' => 'student'];
    }

    private function studentNumberExists(string $studentNumber): bool {
        $stmt = $this->conn->prepare('SELECT StudentNumber FROM studentinfo WHERE StudentNumber = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $studentNumber);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows === 1;
        $stmt->close();

        return $exists;
    }

    private function emailExists(string $email): bool {
        $stmt = $this->conn->prepare('SELECT StudentEmail FROM studentinfo WHERE LOWER(StudentEmail) = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows === 1;
        $stmt->close();

        return $exists;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $register = new Register();
    $result   = $register->register(
        trim($_POST['StudentNumber'] ?? ''),
        trim($_POST['CollegeDepartment'] ?? ''),
        trim($_POST['StudentEmail'] ?? ''),
        $_POST['StudentPassword'] ?? '',
        $_POST['ConfirmPassword'] ?? ''
    );

    echo json_encode($result);
    exit;
}
