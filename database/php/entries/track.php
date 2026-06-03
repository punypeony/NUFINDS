<?php

require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/Database.php');
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/ReportTracker.php');

SessionHelper::requireStudent();

$studentNumber     = trim((string) SessionHelper::get('StudentNumber', ''));
$studentEmail      = SessionHelper::get('StudentEmail', '');
$collegeDepartment = SessionHelper::get('CollegeDepartment', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_lost'])) {
    SessionHelper::requireValidCsrf();

    $tracker = new ReportTracker();
    $result  = $tracker->cancelLostReport((int)($_POST['lost_id'] ?? 0), $studentNumber);

    $param = $result['status'] === 'success' ? 'success' : 'error';
    header('Location: ' . nufinds_student_page('trackreport.html') . '?' . $param . '=' . urlencode($result['message']));
    exit;
}

$tracker     = new ReportTracker();
$studentInfo = $tracker->getStudentInfo($studentNumber) ?? [];
$studentEmail      = $studentInfo['StudentEmail']      ?? $studentEmail;
$collegeDepartment = $studentInfo['CollegeDepartment'] ?? $collegeDepartment;
$reports           = $tracker->getReports($studentNumber);
if (!is_array($reports)) {
    $reports = [];
}

return [
    'displayEmail'             => htmlspecialchars($studentEmail ?: 'Student', ENT_QUOTES, 'UTF-8'),
    'displayStudentNumber'     => htmlspecialchars($studentNumber, ENT_QUOTES, 'UTF-8'),
    'displayCollegeDepartment' => htmlspecialchars($collegeDepartment ?: 'College Department', ENT_QUOTES, 'UTF-8'),
    'profileEmail'             => htmlspecialchars($studentEmail ?: 'userloggedin@students.national-u.edu.ph', ENT_QUOTES, 'UTF-8'),
    'successMessage'           => $_GET['success'] ?? '',
    'errorMessage'             => $_GET['error']   ?? '',
    'reports'                  => $reports,
];
