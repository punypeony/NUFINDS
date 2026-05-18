<?php
require_once 'db_connect.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$studentNumber = trim($_POST['StudentNumber'] ?? '');
$department = trim($_POST['CollegeDepartment'] ?? '');
$studentName = trim($_POST['StudentName'] ?? '');

if (empty($studentNumber) || empty($department) || empty($studentName)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields.']);
    exit;
}

$sql = "SELECT StudentNumber, CollegeDepartment, StudentEmail FROM studentinfo WHERE StudentNumber = ? AND CollegeDepartment = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}

$stmt->bind_param('ss', $studentNumber, $department);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    session_start();
    $_SESSION['StudentNumber'] = $row['StudentNumber'];
    $_SESSION['CollegeDepartment'] = $row['CollegeDepartment'];
    $_SESSION['StudentEmail'] = $row['StudentEmail'];
    $_SESSION['StudentName'] = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');

    echo json_encode(['status' => 'success', 'message' => 'Login successful.']);
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Login failed. Please check your student number and department.']);
    exit;
}
?>
