<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../pages/login.html');
    exit;
}

$studentNumber = trim($_POST['StudentNumber'] ?? '');
$department = trim($_POST['CollegeDepartment'] ?? '');
$studentName = trim($_POST['StudentName'] ?? '');

if (empty($studentNumber) || empty($department) || empty($studentName)) {
    $error = urlencode('Please fill in all fields.');
    header('Location: login.html?error=' . $error);
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

    header('Location: login_success.php');
    exit;
} else {
    $error = urlencode('Login failed. Please check your student number and department.');
    header('Location: ../../pages/login.html?error=' . $error);
    exit;
}
?>
