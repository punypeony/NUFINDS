<?php
require_once 'Database.php';
require_once 'SessionHelper.php';
require_once 'ReportTracker.php';

SessionHelper::start();

$studentNumber     = SessionHelper::get('StudentNumber', '');
$studentEmail      = SessionHelper::get('StudentEmail', '');
$collegeDepartment = SessionHelper::get('CollegeDepartment', '');

$successMessage = $_GET['success'] ?? '';
$errorMessage   = $_GET['error']   ?? '';

// Handle cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_lost'])) {
    $tracker = new ReportTracker();
    $result  = $tracker->cancelLostReport((int)($_POST['lost_id'] ?? 0), $studentNumber);

    if ($result['status'] === 'success') {
        header('Location: TrackReport.php?success=' . urlencode($result['message']));
    } else {
        header('Location: TrackReport.php?error=' . urlencode($result['message']));
    }
    exit;
}

// Not logged in — show lookup form
if ($studentNumber === '') {
    include 'track_lookup.php';
    exit;
}

$tracker           = new ReportTracker();
$studentInfo       = $tracker->getStudentInfo($studentNumber);
$studentEmail      = $studentInfo['StudentEmail']      ?? $studentEmail;
$collegeDepartment = $studentInfo['CollegeDepartment'] ?? $collegeDepartment;
$reports           = $tracker->getReports($studentNumber);

$displayEmail             = htmlspecialchars($studentEmail ?: 'Student',                    ENT_QUOTES, 'UTF-8');
$displayStudentNumber     = htmlspecialchars($studentNumber,                                 ENT_QUOTES, 'UTF-8');
$displayCollegeDepartment = htmlspecialchars($collegeDepartment ?: 'College Department',     ENT_QUOTES, 'UTF-8');
$profileEmail             = htmlspecialchars($studentEmail ?: 'userloggedin@students.national-u.edu.ph', ENT_QUOTES, 'UTF-8');
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

<div class="topbar">
    <img src="../../assets/images/nufindslogo white.png" class="logo-header" alt="NU Finds White Logo">
    <div class="topbar-user"
         data-user-email="<?= $profileEmail ?>"
         data-student-number="<?= $displayStudentNumber ?>"
         data-college-dept="<?= $displayCollegeDepartment ?>">
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

<a href="../../pages/home.php" class="back-icon">
    <img src="../../assets/images/back.png" alt="Back">
</a>

<section class="track-section">
    <div class="building"></div>
    <div class="track-box">
        <div class="track-title">Track Submitted Reports</div>

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
                        <td colspan="6" style="text-align:center; padding:1.5rem;">No reports found for your account.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?= htmlspecialchars($report['TicketNumber'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($report['ReportType'],           ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($report['Category'],             ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= date('F j, Y', strtotime($report['ReportDate'])) ?></td>
                            <td class="status-cell">
                                <span class="status-text"><?= htmlspecialchars($report['Status'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td class="actions-cell">
                                <?php if ($report['ReportType'] === 'Lost'): ?>
                                    <form method="post" action="TrackReport.php" class="cancel-form"
                                          onsubmit="return confirm('Cancel this lost report?');">
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
    const profileToggle   = document.getElementById('profileToggle');
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileToggle && profileDropdown) {
        profileToggle.addEventListener('click', e => {
            e.stopPropagation();
            profileDropdown.classList.toggle('open');
        });
        document.addEventListener('click', () => profileDropdown.classList.remove('open'));
    }
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) logoutBtn.addEventListener('click', () => window.location.href = 'logout.php');
})();
</script>
</body>
</html>