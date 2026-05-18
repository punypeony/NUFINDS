<?php
require_once 'db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ReportFound.html');
    exit;
}

$studentNumber = trim($_SESSION['StudentNumber'] ?? '');
$location = trim($_POST['Location'] ?? '');
$dateFound = trim($_POST['DateFound'] ?? '');
$category = trim($_POST['Category'] ?? '');
$description = trim($_POST['Description'] ?? '');

if ($studentNumber === '') {
    header('Location: ../../pages/login.html');
    exit;
}

if ($location === '' || $dateFound === '' || $category === '' || $description === '') {
    $error = urlencode('Please fill in all required fields.');
    header('Location: ReportFound.html?error=' . $error);
    exit;
}

$checkStmt = $conn->prepare('SELECT StudentNumber FROM studentinfo WHERE StudentNumber = ?');
$checkStmt->bind_param('s', $studentNumber);
$checkStmt->execute();
$result = $checkStmt->get_result();

if (!$result || $result->num_rows !== 1) {
    $error = urlencode('Student number not found in student records. Please use a registered student number.');
    header('Location: ReportFound.html?error=' . $error);
    exit;
}

$insertStmt = $conn->prepare('INSERT INTO found (StudentNumber, Location, DateFound, Category, Description) VALUES (?, ?, ?, ?, ?)');
$insertStmt->bind_param('sssss', $studentNumber, $location, $dateFound, $category, $description);

if (!$insertStmt->execute()) {
    $error = urlencode('Unable to save the found item report. Please try again later.');
    header('Location: ReportFound.html?error=' . $error);
    exit;
}

$title = 'Found Report Submitted';
$message = 'Your found item report has been successfully submitted.';
include 'report_success.php';
exit;
