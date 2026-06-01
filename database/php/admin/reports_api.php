<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/AdminReportService.php');
nufinds_require('lib/HistoryService.php');

SessionHelper::requireAdmin();
header('Content-Type: application/json; charset=utf-8');

$payload = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action  = $payload['action'] ?? '';

$reports = new AdminReportService();
$history = new HistoryService();

try {
    switch ($action) {
        case 'update_lost':
            echo json_encode($reports->updateLost((int)($payload['id'] ?? 0), [
                'TicketNumber'  => trim($payload['TicketNumber'] ?? ''),
                'StudentNumber' => trim($payload['StudentNumber'] ?? ''),
                'Location'      => trim($payload['Location'] ?? ''),
                'DateLost'      => trim($payload['DateLost'] ?? ''),
                'Category'      => trim($payload['Category'] ?? ''),
                'Description'   => trim($payload['Description'] ?? ''),
            ]));
            break;

        case 'delete_lost':
            echo json_encode($reports->deleteLost((int)($payload['id'] ?? 0)));
            break;

        case 'update_found':
            echo json_encode($reports->updateFound((int)($payload['id'] ?? 0), [
                'StudentNumber' => trim($payload['StudentNumber'] ?? ''),
                'Location'      => trim($payload['Location'] ?? ''),
                'DateFound'     => trim($payload['DateFound'] ?? ''),
                'Category'      => trim($payload['Category'] ?? ''),
                'Description'   => trim($payload['Description'] ?? ''),
                'Status'        => trim($payload['Status'] ?? 'Unclaimed'),
            ]));
            break;

        case 'delete_found':
            echo json_encode($reports->deleteFound((int)($payload['id'] ?? 0)));
            break;

        case 'update_history':
            echo json_encode($history->updateHistory((int)($payload['id'] ?? 0), [
                'TicketNumber'  => trim($payload['TicketNumber'] ?? ''),
                'StudentNumber' => trim($payload['StudentNumber'] ?? ''),
                'Location'      => trim($payload['Location'] ?? ''),
                'ReportDate'    => trim($payload['ReportDate'] ?? ''),
                'Category'      => trim($payload['Category'] ?? ''),
                'Description'   => trim($payload['Description'] ?? ''),
                'FinalStatus'   => trim($payload['FinalStatus'] ?? ''),
            ]));
            break;

        case 'delete_history':
            echo json_encode($history->deleteHistory((int)($payload['id'] ?? 0)));
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
