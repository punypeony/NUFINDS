<?php
session_start();
require_once 'db_connect.php';

$studentNumber = $_SESSION['StudentNumber'] ?? null;
$studentName = $_SESSION['StudentName'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentNumber = trim($_POST['StudentNumber'] ?? '');
    
    $checkStmt = $conn->prepare('SELECT StudentNumber FROM studentinfo WHERE StudentNumber = ?');
    $checkStmt->bind_param('s', $studentNumber);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if (!$result || $result->num_rows !== 1) {
        $error = 'Student number not found. Please enter a valid student number.';
    }
}

if (!$studentNumber) {
    include 'track_lookup.php';
    exit;
}

$sql = "SELECT TicketNumber, Category, DateLost AS ReportDate, 'Lost' AS ReportType, 'Submitted' AS Status FROM lost WHERE StudentNumber = ? UNION ALL SELECT NULL AS TicketNumber, Category, DateFound AS ReportDate, 'Found' AS ReportType, Status AS Status FROM found WHERE StudentNumber = ? ORDER BY ReportDate DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $studentNumber, $studentNumber);
$stmt->execute();
$result = $stmt->get_result();
$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NU Finds - Track Reports</title>
<link rel="stylesheet" href="../../css/TrackReport.css">
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <img src="../../assets/images/nu-logo.png" class="logo-img" alt="NU Logo">
    <div class="site-title">NU Finds</div>
</div>

<!-- BACK ICON BUTTON -->
<a href="../../pages/home.html" class="back-icon">
    <img src="../../assets/images/back.png" alt="Back">
</a>

<!-- TRACK SECTION -->
<section class="track-section">

    <div class="track-box">

        <div class="track-title">
            Track Submitted Reports
        </div>

        <div class="user-summary">
            Logged in as: <strong><?= htmlspecialchars($studentName ?: $studentNumber, ENT_QUOTES, 'UTF-8') ?></strong>
        </div>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>Ticket Number</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($reports) === 0): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding: 1.5rem;">No reports found for your account.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?= htmlspecialchars($report['TicketNumber'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($report['ReportType'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($report['Category'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= date('F j, Y', strtotime($report['ReportDate'])) ?></td>
                            <td><?= htmlspecialchars($report['Status'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</section>

</body>
</html>
