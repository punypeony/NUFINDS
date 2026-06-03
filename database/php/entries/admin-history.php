<?php

require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/HistoryService.php');
nufinds_require('lib/AdminReportService.php');

SessionHelper::requireAdmin();

$service     = new HistoryService();
$searchQuery = trim($_GET['q'] ?? '');
$groups      = $searchQuery !== ''
    ? $service->searchArchivedMatches($searchQuery)
    : $service->getArchivedMatches();

return [
    'adminName'     => htmlspecialchars(SessionHelper::get('AdminName', 'Admin'), ENT_QUOTES, 'UTF-8'),
    'adminEmail'    => htmlspecialchars(SessionHelper::get('AdminEmail', ''), ENT_QUOTES, 'UTF-8'),
    'searchQuery'   => $searchQuery,
    'searchAction'  => '',
    'searchClearUrl'=> 'history.html',
    'matchGroups'   => $groups,
    'totalCount'    => $searchQuery !== '' ? count($groups) : $service->getTotalCount(),
    'categories'    => AdminReportService::categories(),
    'reportsApiUrl' => nufinds_php_url('admin/reports_api.php'),
];
