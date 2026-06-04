<?php

require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/NotificationService.php');

SessionHelper::requireAdmin();

return [
    'adminName'           => htmlspecialchars(SessionHelper::get('AdminName', 'Admin'), ENT_QUOTES, 'UTF-8'),
    'adminEmail'          => htmlspecialchars(SessionHelper::get('AdminEmail', ''), ENT_QUOTES, 'UTF-8'),
    'notificationsApiUrl' => nufinds_php_url('admin/notifications_api.php'),
];
