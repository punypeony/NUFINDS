<?php

require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/Database.php');
nufinds_require('lib/AdminReportService.php');

SessionHelper::requireAdmin();

$service = new AdminReportService();
$grouped = $service->getLostGroupedByDepartment();

return [
    'adminName'     => htmlspecialchars(SessionHelper::get('AdminName', 'Admin'), ENT_QUOTES, 'UTF-8'),
    'adminEmail'    => htmlspecialchars(SessionHelper::get('AdminEmail', ''), ENT_QUOTES, 'UTF-8'),
    'grouped'       => $grouped,
    'totalCount'    => array_sum(array_map('count', $grouped)),
    'categories'    => AdminReportService::categories(),
    'reportsApiUrl' => nufinds_php_url('admin/reports_api.php'),
];
