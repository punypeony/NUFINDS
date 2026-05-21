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
$dateLost      = trim($_POST['DateLost']      ?? '');
$category      = trim($_POST['Category']      ?? '');
$description   = trim($_POST['Description']   ?? '');

if ($studentNumber === '') {
    echo json_encode(['status' => 'error', 'message' => 'Please log in before submitting a report.']);
    exit;
}

if ($location === '' || $dateLost === '' || $category === '' || $description === '') {
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

$ticketResult = $conn->query('SELECT MAX(LostID) AS maxId FROM lost');
$ticketRow    = $ticketResult ? $ticketResult->fetch_assoc() : null;
$nextId       = ($ticketRow['maxId'] ?? 0) + 1;
$ticketNumber = 'NU-' . str_pad(1000 + $nextId, 4, '0', STR_PAD_LEFT);

// ── Image upload ──────────────────────────────────────────────
$imagePath = null;
if (isset($_FILES['ItemImage']) && $_FILES['ItemImage']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../../uploads/lost/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $ext = strtolower(pathinfo($_FILES['ItemImage']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $filename = 'lost_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['ItemImage']['tmp_name'], $uploadDir . $filename)) {
            $imagePath = 'uploads/lost/' . $filename;
        }
    }
}
// ─────────────────────────────────────────────────────────────

$insertStmt = $conn->prepare('INSERT INTO lost (TicketNumber, StudentNumber, Location, DateLost, Category, Description, Image) VALUES (?, ?, ?, ?, ?, ?, ?)');
$insertStmt->bind_param('sssssss', $ticketNumber, $studentNumber, $location, $dateLost, $category, $description, $imagePath);

if (!$insertStmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Unable to save the lost item report. Please try again later.']);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'Your lost item report has been successfully submitted. Ticket Number: ' . $ticketNumber]);
exit;