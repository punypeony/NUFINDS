<?php
require_once 'db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ReportLost.html');
    exit;
}

$studentNumber = trim($_SESSION['StudentNumber'] ?? '');
$location = trim($_POST['Location'] ?? '');
$dateLost = trim($_POST['DateLost'] ?? '');
$category = trim($_POST['Category'] ?? '');
$description = trim($_POST['Description'] ?? '');

if ($studentNumber === '') {
    header('Location: ../../pages/login.html');
    exit;
}

if ($location === '' || $dateLost === '' || $category === '' || $description === '') {
    $error = urlencode('Please fill in all required fields.');
    header('Location: ReportLost.html?error=' . $error);
    exit;
}

$checkStmt = $conn->prepare('SELECT StudentNumber FROM studentinfo WHERE StudentNumber = ?');
$checkStmt->bind_param('s', $studentNumber);
$checkStmt->execute();
$result = $checkStmt->get_result();

if (!$result || $result->num_rows !== 1) {
    $error = urlencode('Student number not found in student records. Please use a registered student number.');
    header('Location: ReportLost.html?error=' . $error);
    exit;
}

$ticketResult = $conn->query('SELECT MAX(LostID) AS maxId FROM lost');
$ticketRow = $ticketResult ? $ticketResult->fetch_assoc() : null;
$nextId = ($ticketRow['maxId'] ?? 0) + 1;
$ticketNumber = 'NU-' . str_pad(1000 + $nextId, 4, '0', STR_PAD_LEFT);

$insertStmt = $conn->prepare('INSERT INTO lost (TicketNumber, StudentNumber, Location, DateLost, Category, Description) VALUES (?, ?, ?, ?, ?, ?)');
$insertStmt->bind_param('ssssss', $ticketNumber, $studentNumber, $location, $dateLost, $category, $description);

if (!$insertStmt->execute()) {
    $error = urlencode('Unable to save the lost item report. Please try again later.');
    header('Location: ReportLost.html?error=' . $error);
    exit;
}

$title = 'Lost Report Submitted';
$message = 'Your lost item report has been successfully submitted.\nTicket Number: ' . $ticketNumber;
include 'report_success.php';
exit;
