<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/NotificationService.php');

SessionHelper::requireStudent();
header('Content-Type: application/json; charset=utf-8');

$studentNumber = SessionHelper::get('StudentNumber', '');
if ($studentNumber === '') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Not signed in.']);
    exit;
}

$service = new NotificationService();
$method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    switch ($action) {
        case 'poll':
            $sinceId = (int)($_GET['since_id'] ?? 0);
            $poll    = $service->pollForStudent($studentNumber, $sinceId);
            echo json_encode([
                'status'      => 'success',
                'unreadCount' => $poll['unreadCount'],
                'latestId'    => $poll['latestId'],
                'new'         => $poll['new'],
            ]);
            break;

        case 'unread_count':
            echo json_encode([
                'status'      => 'success',
                'unreadCount' => $service->unreadCount($studentNumber),
            ]);
            break;

        case 'list':
        default:
            echo json_encode([
                'status'        => 'success',
                'notifications' => $service->listForStudent($studentNumber),
                'unreadCount'   => $service->unreadCount($studentNumber),
            ]);
            break;
    }
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? $_POST;
SessionHelper::requireValidCsrf($payload);
$action = $payload['action'] ?? '';

switch ($action) {
    case 'mark_read':
        echo json_encode($service->markRead($studentNumber, (int)($payload['id'] ?? 0)));
        break;

    case 'mark_all_read':
        echo json_encode($service->markAllRead($studentNumber));
        break;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
}
