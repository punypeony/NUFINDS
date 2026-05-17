<?php
$error = $error ?? null;
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

        <div class="lookup-form">
            <h3>Enter Your Student Number</h3>
            <?php if ($error): ?>
                <p style="color: #b00020; margin: 1rem 0;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            
            <form action="TrackReport.php" method="post" style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                <input type="text" name="StudentNumber" placeholder="Enter Student Number (e.g., 2024-1001234)" required style="flex: 1; min-width: 250px; padding: 0.75rem; border: 1px solid #ccc; border-radius: 8px;">
                <button type="submit" style="padding: 0.75rem 1.5rem; background: #1f7a8c; color: white; border: none; border-radius: 8px; cursor: pointer;">Search</button>
            </form>
        </div>

    </div>

</section>

</body>
</html>
