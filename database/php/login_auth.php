<?php
require_once 'db_connect.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$studentNumber = trim($_POST['StudentNumber'] ?? '');
$department = trim($_POST['CollegeDepartment'] ?? '');
$studentEmail = trim($_POST['StudentEmail'] ?? '');

if (empty($studentNumber) || empty($department) || empty($studentEmail)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields.']);
    exit;
}

if (!filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
    exit;
}

$sql = "SELECT StudentNumber, CollegeDepartment, StudentEmail FROM studentinfo WHERE StudentNumber = ? AND CollegeDepartment = ? AND StudentEmail = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}

$stmt->bind_param('sss', $studentNumber, $department, $studentEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    session_start();
    $_SESSION['StudentNumber'] = $row['StudentNumber'];
    $_SESSION['CollegeDepartment'] = $row['CollegeDepartment'];
    $_SESSION['StudentEmail'] = $row['StudentEmail'];

<<<<<<< Updated upstream
    echo json_encode(['status' => 'success', 'message' => 'Login successful.']);
=======
    header('Location: ../../pages/home.php');
>>>>>>> Stashed changes
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Login failed. Please check your credentials.']);
    exit;
}
?>
