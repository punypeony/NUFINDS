<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/AdminUserService.php');
nufinds_require('lib/NotificationService.php');

SessionHelper::requireAdmin();
header('Content-Type: application/json; charset=utf-8');

$payload = json_decode(file_get_contents('php://input'), true) ?? $_POST;
SessionHelper::requireValidCsrf($payload);
$action  = $payload['action'] ?? '';

$users       = new AdminUserService();
$notify      = new NotificationService();
$adminId     = (int)SessionHelper::get('AdminID', 0);
$adminId     = $adminId > 0 ? $adminId : null;

switch ($action) {
    case 'update':
        echo json_encode($users->updateUser(
            trim($payload['studentNumber'] ?? ''),
            trim($payload['email'] ?? ''),
            trim($payload['department'] ?? '')
        ));
        break;

    case 'deactivate':
        echo json_encode($users->setActive(trim($payload['studentNumber'] ?? ''), false));
        break;

    case 'reactivate':
        echo json_encode($users->setActive(trim($payload['studentNumber'] ?? ''), true));
        break;

    case 'notify':
        $studentNumber = trim($payload['studentNumber'] ?? '');
        $title         = trim($payload['title'] ?? '');
        $message       = trim($payload['message'] ?? '');
        echo json_encode($notify->sendToStudent($studentNumber, $title, $message, $adminId));
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
