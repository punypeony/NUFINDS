<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/NotificationService.php');

SessionHelper::requireAdmin();
header('Content-Type: application/json; charset=utf-8');

$payload = json_decode(file_get_contents('php://input'), true) ?? $_POST;
SessionHelper::requireValidCsrf($payload);
$action  = $payload['action'] ?? '';

$service = new NotificationService();
$adminId = (int)SessionHelper::get('AdminID', 0);
if ($adminId <= 0) {
    $adminId = null;
}

switch ($action) {
    case 'send':
        $audience = $payload['audience'] ?? 'one';
        $title    = trim($payload['title'] ?? '');
        $message  = trim($payload['message'] ?? '');

        if ($audience === 'all') {
            echo json_encode($service->sendToAll($title, $message, $adminId));
            break;
        }

        $target = trim($payload['target'] ?? '');
        $number = $service->resolveStudentNumber($target);
        if ($number === null) {
            echo json_encode(['status' => 'error', 'message' => 'Student not found. Enter student ID or email.']);
            break;
        }
        echo json_encode($service->sendToStudent($number, $title, $message, $adminId));
        break;

    case 'recent':
        echo json_encode([
            'status' => 'success',
            'items'  => $service->listRecentSent(30),
        ]);
        break;

    case 'presets':
        echo json_encode([
            'status'  => 'success',
            'presets' => NotificationService::presetsForUi(),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
}
