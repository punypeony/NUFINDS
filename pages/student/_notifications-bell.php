<?php defined('NUFINDS_VIEW') || exit; ?>
<div class="notifications-menu" id="notificationsMenu">
  <button type="button" class="notifications-bell" id="notificationsToggle" aria-label="Notifications" aria-expanded="false">
    <span class="notifications-bell-icon" aria-hidden="true"></span>
    <span class="notifications-badge hidden" id="notificationsBadge">0</span>
  </button>
  <div class="notifications-panel hidden" id="notificationsPanel" role="dialog" aria-label="Your notifications">
    <div class="notifications-panel-header">
      <h2>Notifications</h2>
      <button type="button" class="notifications-mark-all" id="notificationsMarkAll">Mark all read</button>
    </div>
    <ul class="notifications-list" id="notificationsList"></ul>
    <p class="notifications-empty hidden" id="notificationsEmpty">No notifications yet.</p>
  </div>
</div>
<div class="notification-toast hidden" id="notificationToast" role="status" aria-live="polite">
  <strong id="notificationToastTitle"></strong>
  <span id="notificationToastMessage"></span>
</div>
