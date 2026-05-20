<?php
require_once 'db_connect.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$studentNumber = trim($_SESSION['StudentNumber'] ?? '');
$location      = trim($_POST['Location']      ?? '');
$dateFound     = trim($_POST['DateFound']     ?? '');
$category      = trim($_POST['Category']      ?? '');
$description   = trim($_POST['Description']   ?? '');

if ($studentNumber === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please log in before submitting a report.']);
    exit;
}

if ($location === '' || $dateFound === '' || $category === '' || $description === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
    exit;
}

$checkStmt = $conn->prepare('SELECT StudentNumber FROM studentinfo WHERE StudentNumber = ?');
$checkStmt->bind_param('s', $studentNumber);
$checkStmt->execute();
$result = $checkStmt->get_result();

if (!$result || $result->num_rows !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'Student number not found in student records. Please use a registered student number.']);
    exit;
}

// ── Image upload ──────────────────────────────────────────────
$imagePath = null;
if (isset($_FILES['ItemImage']) && $_FILES['ItemImage']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../../uploads/found/';
$imagePath = 'uploads/found/' . $filename;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $ext = strtolower(pathinfo($_FILES['ItemImage']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $filename = 'found_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['ItemImage']['tmp_name'], $uploadDir . $filename)) {
            $imagePath = 'uploads/' . $filename;
        }
    }
}
// ─────────────────────────────────────────────────────────────

$insertStmt = $conn->prepare('INSERT INTO found (StudentNumber, Location, DateFound, Category, Description, Image) VALUES (?, ?, ?, ?, ?, ?)');
$insertStmt->bind_param('ssssss', $studentNumber, $location, $dateFound, $category, $description, $imagePath);

if (!$insertStmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Unable to save the found item report. Please try again later.']);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'Your found item report has been successfully submitted.']);
exit;