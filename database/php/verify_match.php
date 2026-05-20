<?php
require_once 'db_connect.php';

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: verify_matches.php');
    exit;
}

$lostId = (int)($_POST['lost_id'] ?? 0);
$foundId = (int)($_POST['found_id'] ?? 0);

if ($lostId === 0 || $foundId === 0) {
    $message = 'Invalid request.';
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }
    $error = urlencode($message);
    header('Location: verify_matches.php?error=' . $error);
    exit;
}

$conn->begin_transaction();

try {
    $lostStmt = $conn->prepare('SELECT TicketNumber, StudentNumber, Location, DateLost, Category, Description FROM lost WHERE LostID = ?');
    $lostStmt->bind_param('i', $lostId);
    $lostStmt->execute();
    $lostResult = $lostStmt->get_result();
    $lostData = $lostResult->fetch_assoc();

    $foundStmt = $conn->prepare('SELECT StudentNumber, DateFound FROM found WHERE FoundID = ?');
    $foundStmt->bind_param('i', $foundId);
    $foundStmt->execute();
    $foundResult = $foundStmt->get_result();
    $foundData = $foundResult->fetch_assoc();

    if (!$lostData || !$foundData) {
        throw new Exception('Lost or Found record not found.');
    }

    $historyStmt = $conn->prepare('INSERT INTO history (ReportType, OriginalReportID, TicketNumber, StudentNumber, Location, ReportDate, Category, Description, FinalStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $reportType = 'Lost';
    $finalStatus = 'Retrieved';
    $historyStmt->bind_param('ssissssss', $reportType, $lostId, $lostData['TicketNumber'], $lostData['StudentNumber'], $lostData['Location'], $lostData['DateLost'], $lostData['Category'], $lostData['Description'], $finalStatus);
    $historyStmt->execute();

    $updateFoundStmt = $conn->prepare('UPDATE found SET Status = ? WHERE FoundID = ?');
    $claimedStatus = 'Claimed';
    $updateFoundStmt->bind_param('si', $claimedStatus, $foundId);
    $updateFoundStmt->execute();

    $conn->commit();

    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Match verified successfully.']);
        exit;
    }

    header('Location: verify_success.php?lost_id=' . $lostId . '&found_id=' . $foundId);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $message = 'Error verifying match: ' . $e->getMessage();
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }
    $error = urlencode($message);
    header('Location: verify_matches.php?error=' . $error);
    exit;
}
?>
