<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/ReportService.php');
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/ImageUploader.php');

SessionHelper::requireStudent();
header('Content-Type: application/json; charset=utf-8');

SessionHelper::requireValidCsrf();

$reportType = strtolower(trim($_POST['report_type'] ?? ''));
$itemImage  = ImageUploader::upload('ItemImage', $reportType === 'lost' ? 'lost' : 'found');

$service = new ReportService();
$result  = $service->submit(
    $reportType,
    trim((string) SessionHelper::get('StudentNumber', '')),
    [
        'Location'    => trim($_POST['Location']    ?? ''),
        'Date'        => trim($_POST['DateLost']    ?? $_POST['DateFound'] ?? ''),
        'Category'    => trim($_POST['Category']    ?? ''),
        'Description' => trim($_POST['Description'] ?? ''),
    ],
    $itemImage,
    !empty($_POST['force_submit'])
);

echo json_encode($result);
exit;
