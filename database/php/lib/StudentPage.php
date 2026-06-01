<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/SessionHelper.php';

function nufinds_load_student_profile(): array
{
    $studentNumber     = SessionHelper::get('StudentNumber', '');
    $studentEmail      = SessionHelper::get('StudentEmail', '');
    $collegeDepartment = SessionHelper::get('CollegeDepartment', '');

    $conn = Database::connect();
    $stmt = $conn->prepare('SELECT StudentEmail, CollegeDepartment FROM studentinfo WHERE StudentNumber = ?');
    if ($stmt) {
        $stmt->bind_param('s', $studentNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $row               = $result->fetch_assoc();
            $studentEmail      = $row['StudentEmail']      ?: $studentEmail;
            $collegeDepartment = $row['CollegeDepartment'] ?: $collegeDepartment;
        }
        $stmt->close();
    }

    return [
        'displayEmail'             => htmlspecialchars($studentEmail ?: 'Student', ENT_QUOTES, 'UTF-8'),
        'displayStudentNumber'     => htmlspecialchars($studentNumber, ENT_QUOTES, 'UTF-8'),
        'displayCollegeDepartment' => htmlspecialchars($collegeDepartment ?: 'College Department', ENT_QUOTES, 'UTF-8'),
        'profileEmail'             => htmlspecialchars($studentEmail ?: 'userloggedin@students.national-u.edu.ph', ENT_QUOTES, 'UTF-8'),
    ];
}
