<?php
session_start();
require_once __DIR__ . '/../database/php/db_connect.php';

if (empty($_SESSION['StudentNumber'])) {
    header('Location: login.html');
    exit;
}

$studentNumber = $_SESSION['StudentNumber'];
$studentName = $_SESSION['StudentName'] ?? '';
$studentEmail = $_SESSION['StudentEmail'] ?? '';
$collegeDepartment = $_SESSION['CollegeDepartment'] ?? '';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NU Finds</title>
    <link rel="stylesheet" href="../css/home.css">
</head>
<body>
<div class="container">
    <!-- TOPBAR -->
    <div class="topbar">
        <img src="../assets/images/nufindslogo white.png" alt="NU Finds White Logo" class="logo-header">
        <div class="topbar-user" data-user-email="<?= $profileEmail ?>" data-student-number="<?= $displayStudentNumber ?>" data-college-dept="<?= $displayCollegeDepartment ?>">
            <div class="user-info">Hi, <span class="user-email"><?= $displayEmail ?></span></div>
            <div class="profile-menu">
                <img src="../assets/images/profileicon.png" alt="Profile icon" class="profile-icon" id="profileToggle">
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

    <!-- HERO -->
    <section class="hero">
        <div class="left-content">
            <div class="logo-title">
                <img src="../assets/images/nufindslogo blue.png" alt="NU Finds Blue Logo" class="logo-hero">
                <p>   What’s Lost Finds Its Way</p>
            </div>

            <div class="hero-buttons">
                <button class="btn lost" onclick="window.location.href='ReportLost.php'">LOST</button>
                <button class="btn found" onclick="window.location.href='ReportFound.php'">FOUND</button>
                <button class="btn track" onclick="window.location.href='../database/php/TrackReport.php'">TRACK</button>
            </div>

            <small>   For Nationalians use only.</small>
        </div>

        <!-- BUILDING -->
        <div class="building"></div>
    </section>

<!-- HOW IT WORKS -->
<section class="home-section">
    <div class="home-title">
        HOW <span>NU FINDS</span> WORKS
    </div>

    <div class="steps">
        <div class="step-box">
            <img class="step-icon" src="../assets/images/report.png" alt="Report icon">
            <h3>Find What Matters</h3>
            <p>
                Fill out the report form and provide details such as
                item description, date, time, and location.
            </p>
        </div>

        <div class="step-box">
            <img class="step-icon" src="../assets/images/verify.png" alt="Verify icon">
            <h3>Confirm and Verify</h3>
            <p>
                Once a possible match is detected, ownership verification follows.
            </p>
        </div>

        <div class="step-box">
            <img class="step-icon" src="../assets/images/retrieve.png" alt="Retrieve icon">
            <h3>Retrieve With Ease</h3>
            <p>
                After successful verification, users may retrieve the item easily.
            </p>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="site-footer">
    <div class="footer-left">
        <img src="../assets/images/nufindslogo white.png" alt="NU Finds White Logo" class="logo-header">
    </div>

    <div class="footer-middle">
        <h4>ABOUT NU FINDS</h4>
        <p>
            NU Finds is a centralized campus lost-and-found system designed
            to help students and staff recover belongings efficiently.
        </p>
    </div>

    <div class="footer-right">
        <h4>CONTACT US</h4>
        <p><img class="contact-icon" src="../assets/images/location.png" alt="Location icon"> National University Manila</p>
        <p><img class="contact-icon" src="../assets/images/call.png" alt="Phone icon"> 0945-123-6767</p>
        <p><img class="contact-icon" src="../assets/images/email.png" alt="Email icon"> nufindshelpdesk@gmail.com</p>
        <p><img class="contact-icon" src="../assets/images/time.png" alt="Hours icon"> Monday to Friday (8:30AM - 5:30PM)</p>
    </div>
</footer>
</div>

<script>
(function () {
  const profileDropdown = document.getElementById('profileDropdown');
  const profileToggle = document.getElementById('profileToggle');

  profileToggle.addEventListener('click', function (event) {
    event.stopPropagation();
    profileDropdown.classList.toggle('open');
  });

  document.addEventListener('click', function () {
    profileDropdown.classList.remove('open');
  });

  document.getElementById('logoutBtn').addEventListener('click', function () {
    window.location.href = '../database/php/logout.php';
  });
})();
</script>
</body>
</html>
