<?php defined('NUFINDS_VIEW') || exit; ?>
<div class="topbar">
  <img src="<?= nufinds_asset('assets/images/nufindslogo white.png') ?>" alt="NU Finds White Logo" class="logo-header">
  <div class="topbar-user"
       data-user-email="<?= $profileEmail ?? '' ?>"
       data-student-number="<?= $displayStudentNumber ?? '' ?>"
       data-college-dept="<?= $displayCollegeDepartment ?? '' ?>">
    <div class="user-info">Hi, <span class="user-email"><?= $displayEmail ?? 'Student' ?></span></div>
    <?php require __DIR__ . '/_notifications-bell.php'; ?>
    <div class="profile-menu">
      <img src="<?= nufinds_asset('assets/images/profileicon.png') ?>" alt="Profile icon" class="profile-icon" id="profileToggle">
      <div class="profile-dropdown" id="profileDropdown">
        <div class="profile-dropdown-header">Account</div>
        <div class="profile-dropdown-item"><span>Student No</span><span class="dropdown-student-number"><?= $displayStudentNumber ?? '' ?></span></div>
        <div class="profile-dropdown-item"><span>Email</span><span class="dropdown-email"><?= $profileEmail ?? '' ?></span></div>
        <div class="profile-dropdown-item"><span>College Dept</span><span class="dropdown-college-dept"><?= $displayCollegeDepartment ?? '' ?></span></div>
        <button type="button" class="logout-btn" id="logoutBtn" data-logout-url="<?= nufinds_php_url('auth/logout.php') ?>">Logout</button>
      </div>
    </div>
  </div>
</div>
