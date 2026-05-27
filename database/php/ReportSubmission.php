<?php
require_once __DIR__ . '/lib/ReportService.php';
require_once __DIR__ . '/lib/SessionHelper.php';
require_once __DIR__ . '/lib/ImageUploader.php';

SessionHelper::requireLogin();
header('Content-Type: application/json; charset=utf-8');

$reportType = strtolower(trim($_POST['report_type'] ?? ''));
$itemImage  = ImageUploader::upload('ItemImage', $reportType === 'lost' ? 'lost' : 'found');

$service = new ReportService();
$result  = $service->submit(
    $reportType,
    SessionHelper::get('StudentNumber', ''),
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
