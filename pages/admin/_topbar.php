<?php defined('NUFINDS_VIEW') || exit; ?>
<div class="topbar">
  <img src="<?= nufinds_asset('assets/images/nufindslogo white.png') ?>" alt="NU Finds White Logo" class="logo-header">
  <div class="topbar-user">
    <div class="user-info">Hi, <span class="user-email"><?= $adminName ?? 'Admin' ?></span></div>
    <div class="profile-menu">
      <img src="<?= nufinds_asset('assets/images/profileicon.png') ?>" alt="Profile icon" class="profile-icon" id="profileToggle">
      <div class="profile-dropdown" id="profileDropdown">
        <div class="profile-dropdown-header">Admin Account</div>
        <div class="profile-dropdown-item"><span>Name</span><span><?= $adminName ?? 'Admin' ?></span></div>
        <?php if (!empty($adminEmail)): ?>
          <div class="profile-dropdown-item"><span>Email</span><span><?= $adminEmail ?></span></div>
        <?php endif; ?>
        <button type="button" class="logout-btn" id="logoutBtn" data-logout-url="<?= nufinds_php_url('auth/logout.php') ?>">Logout</button>
      </div>
    </div>
  </div>
</div>
