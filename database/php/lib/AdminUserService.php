<?php

require_once __DIR__ . '/Database.php';

class AdminUserService {
    public const DEPARTMENTS = [
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

    public static function hasIsActiveColumn(mysqli $conn): bool {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $result = $conn->query("SHOW COLUMNS FROM studentinfo LIKE 'IsActive'");
        $cached = $result && $result->num_rows > 0;

        return $cached;
    }

    public function listUsers(string $query = '', string $status = 'all'): array {
        $query  = trim($query);
        $status = strtolower(trim($status));
        $hasActive = self::hasIsActiveColumn($this->conn);

        $sql    = 'SELECT StudentNumber, CollegeDepartment, StudentEmail';
        $sql   .= $hasActive ? ', IsActive' : '';
        $sql   .= ' FROM studentinfo WHERE 1=1';
        $types  = '';
        $params = [];

        if ($query !== '') {
            $like = '%' . $query . '%';
            $sql .= ' AND (StudentNumber LIKE ? OR StudentEmail LIKE ? OR CollegeDepartment LIKE ?)';
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($hasActive && $status === 'active') {
            $sql .= ' AND IsActive = 1';
        } elseif ($hasActive && $status === 'inactive') {
            $sql .= ' AND IsActive = 0';
        }

        $sql .= ' ORDER BY StudentNumber ASC';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = [];
        while ($row = $result->fetch_assoc()) {
            if (!$hasActive) {
                $row['IsActive'] = 1;
            } else {
                $row['IsActive'] = (int)$row['IsActive'];
            }
            $rows[] = $row;
        }
        $stmt->close();

        return $rows;
    }

    public function updateUser(string $studentNumber, string $email, string $department): array {
        $studentNumber = trim($studentNumber);
        $email         = strtolower(trim($email));
        $department    = trim($department);

        if ($studentNumber === '') {
            return ['status' => 'error', 'message' => 'Student number is required.'];
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => 'Please enter a valid email address.'];
        }

        if (!in_array($department, self::DEPARTMENTS, true)) {
            return ['status' => 'error', 'message' => 'Please select a valid college department.'];
        }

        $stmt = $this->conn->prepare(
            'SELECT StudentNumber FROM studentinfo WHERE LOWER(StudentEmail) = ? AND StudentNumber <> ? LIMIT 1'
        );
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Could not verify email.'];
        }
        $stmt->bind_param('ss', $email, $studentNumber);
        $stmt->execute();
        $dup = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($dup) {
            return ['status' => 'error', 'message' => 'That email is already used by another student.'];
        }

        $stmt = $this->conn->prepare(
            'UPDATE studentinfo SET CollegeDepartment = ?, StudentEmail = ? WHERE StudentNumber = ?'
        );
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Could not update student.'];
        }
        $stmt->bind_param('sss', $department, $email, $studentNumber);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok || $this->conn->affected_rows === 0) {
            $check = $this->conn->prepare('SELECT StudentNumber FROM studentinfo WHERE StudentNumber = ? LIMIT 1');
            $check->bind_param('s', $studentNumber);
            $check->execute();
            $exists = $check->get_result()->fetch_assoc();
            $check->close();
            if (!$exists) {
                return ['status' => 'error', 'message' => 'Student not found.'];
            }
        }

        return ['status' => 'success', 'message' => 'Student updated.'];
    }

    public function setActive(string $studentNumber, bool $active): array {
        if (!self::hasIsActiveColumn($this->conn)) {
            return [
                'status'  => 'error',
                'message' => 'Deactivate is not available yet. Import database/student_is_active.sql in phpMyAdmin.',
            ];
        }

        $studentNumber = trim($studentNumber);
        if ($studentNumber === '') {
            return ['status' => 'error', 'message' => 'Student number is required.'];
        }

        $value = $active ? 1 : 0;
        $stmt  = $this->conn->prepare('UPDATE studentinfo SET IsActive = ? WHERE StudentNumber = ?');
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Could not update account status.'];
        }
        $stmt->bind_param('is', $value, $studentNumber);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok || $this->conn->affected_rows === 0) {
            return ['status' => 'error', 'message' => 'Student not found or status unchanged.'];
        }

        return [
            'status'  => 'success',
            'message' => $active ? 'Account reactivated.' : 'Account deactivated.',
            'isActive' => $active,
        ];
    }
}
