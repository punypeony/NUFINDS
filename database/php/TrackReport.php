<?php
session_start();
require_once 'db_connect.php';

$studentNumber = $_SESSION['StudentNumber'] ?? null;
$studentName = $_SESSION['StudentName'] ?? null;
$studentEmail = $_SESSION['StudentEmail'] ?? '';
$collegeDepartment = $_SESSION['CollegeDepartment'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel_lost'])) {
        $lostId = (int)($_POST['lost_id'] ?? 0);
        $studentNumber = $_SESSION['StudentNumber'] ?? '';

        if ($lostId <= 0 || $studentNumber === '') {
            header('Location: TrackReport.php?error=' . urlencode('Unable to cancel the lost report.')); 
            exit;
        }

        $deleteStmt = $conn->prepare('DELETE FROM lost WHERE LostID = ? AND StudentNumber = ?');
        $deleteStmt->bind_param('is', $lostId, $studentNumber);
        $deleteStmt->execute();

        if ($deleteStmt->affected_rows > 0) {
            header('Location: TrackReport.php?success=' . urlencode('Lost report canceled successfully.'));
        } else {
            header('Location: TrackReport.php?error=' . urlencode('No matching lost report found or it has already been removed.'));
        }
        exit;
    }

    $studentNumber = trim($_POST['StudentNumber'] ?? '');
    
    $checkStmt = $conn->prepare('SELECT StudentNumber FROM studentinfo WHERE StudentNumber = ?');
    $checkStmt->bind_param('s', $studentNumber);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if (!$result || $result->num_rows !== 1) {
        $error = 'Student number not found. Please enter a valid student number.';
    }
}

$successMessage = $_GET['success'] ?? '';
$errorMessage = $_GET['error'] ?? '';

if (!$studentNumber) {
    include 'track_lookup.php';
    exit;
}

$stmt = $conn->prepare('SELECT StudentEmail, CollegeDepartment FROM studentinfo WHERE StudentNumber = ?');
if ($stmt) {
    $stmt->bind_param('s', $studentNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $studentEmail = $row['StudentEmail'] ?: $studentEmail;
        $collegeDepartment = $row['CollegeDepartment'] ?: $collegeDepartment;
    }
    $stmt->close();
}

$displayEmail = htmlspecialchars($studentEmail ?: $studentName ?: 'Student', ENT_QUOTES, 'UTF-8');
$displayStudentNumber = htmlspecialchars($studentNumber, ENT_QUOTES, 'UTF-8');
$displayCollegeDepartment = htmlspecialchars($collegeDepartment ?: 'College Department', ENT_QUOTES, 'UTF-8');
$profileEmail = htmlspecialchars($studentEmail ?: $studentName ?: 'userloggedin@students.national-u.edu.ph', ENT_QUOTES, 'UTF-8');

$sql = "SELECT LostID, TicketNumber, Category, DateLost AS ReportDate, 'Lost' AS ReportType, 'Submitted' AS Status FROM lost WHERE StudentNumber = ? UNION ALL SELECT NULL AS LostID, NULL AS TicketNumber, Category, DateFound AS ReportDate, 'Found' AS ReportType, Status AS Status FROM found WHERE StudentNumber = ? ORDER BY ReportDate DESC";
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
    <img src="../../assets/images/nufindslogo white.png" class="logo-header" alt="NU Finds White Logo">
    <div class="topbar-user" data-user-email="<?= $profileEmail ?>" data-student-number="<?= $displayStudentNumber ?>" data-college-dept="<?= $displayCollegeDepartment ?>">
        <div class="user-info">Hi, <span class="user-email"><?= $displayEmail ?></span></div>
        <div class="profile-menu">
            <img src="../../assets/images/profileicon.png" alt="Profile icon" class="profile-icon" id="profileToggle">
            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dropdown-header">Account</div>
                <div class="profile-dropdown-item"><span>Student No</span><span class="dropdown-student-number"><?= $displayStudentNumber ?></span></div>
                <div class="profile-dropdown-item"><span>Email</span><span class="dropdown-email"><?= $profileEmail ?></span></div>
                <div class="profile-dropdown-item"><span>College Dept</span><span class="dropdown-college-dept"><?= $displayCollegeDepartment ?></span></div>
                <button class="logout-btn" id="logoutBtn">Logout</button>
            </div>
        </div>
    </div>
</div>

<!-- BACK ICON BUTTON -->
<a href="../../pages/home.php" class="back-icon">
    <img src="../../assets/images/back.png" alt="Back">
</a>

<!-- TRACK SECTION -->
<section class="track-section">

    <div class="building"></div>

    <div class="track-box">

        <div class="track-title">
            Track Submitted Reports
        </div>

        <?php if (!empty($successMessage)): ?>
            <div class="alert success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMessage)): ?>
            <div class="alert error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>Ticket Number</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th></th>
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
                                <td class="status-cell"><span class="status-text"><?= htmlspecialchars($report['Status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="actions-cell">
                                    <?php if ($report['ReportType'] === 'Lost'): ?>
                                        <form method="post" action="TrackReport.php" class="cancel-form" onsubmit="return confirm('Cancel this lost report?');">
                                            <input type="hidden" name="lost_id" value="<?= (int)$report['LostID'] ?>">
                                            <button type="submit" name="cancel_lost" class="cancel-btn">Cancel</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="found-label">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</section>

<script>
(function () {
  const profileDropdown = document.getElementById('profileDropdown');
  const profileToggle = document.getElementById('profileToggle');

  if (profileToggle && profileDropdown) {
    profileToggle.addEventListener('click', function (event) {
      event.stopPropagation();
      profileDropdown.classList.toggle('open');
    });

    document.addEventListener('click', function () {
      profileDropdown.classList.remove('open');
    });
  }

  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function () {
      window.location.href = 'logout.php';
    });
  }
})();
</script>

</body>
</html>
