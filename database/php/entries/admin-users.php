<?php

require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/Database.php');
nufinds_require('lib/AdminUserService.php');

SessionHelper::requireAdmin();

$service     = new AdminUserService();
$searchQuery = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all');
$users       = $service->listUsers($searchQuery, $statusFilter);

return [
    'adminName'      => htmlspecialchars(SessionHelper::get('AdminName', 'Admin'), ENT_QUOTES, 'UTF-8'),
    'adminEmail'     => htmlspecialchars(SessionHelper::get('AdminEmail', ''), ENT_QUOTES, 'UTF-8'),
    'searchQuery'    => $searchQuery,
    'statusFilter'   => $statusFilter,
    'searchAction'   => 'users.html',
    'searchClearUrl' => 'users.html',
    'users'          => $users,
    'totalCount'     => count($users),
    'departments'    => AdminUserService::DEPARTMENTS,
    'hasIsActive'    => AdminUserService::hasIsActiveColumn(Database::connect()),
    'usersApiUrl'    => nufinds_php_url('admin/users_api.php'),
    'notificationsApiUrl' => nufinds_php_url('admin/notifications_api.php'),
];
