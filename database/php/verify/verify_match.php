<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/MatchVerifier.php');

SessionHelper::requireAdmin();

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: verify_matches.php');
    exit;
}

SessionHelper::requireValidCsrf();

$verifier = new MatchVerifier();
$result   = $verifier->verifyMatch(
    (int)($_POST['lost_id']  ?? 0),
    (int)($_POST['found_id'] ?? 0)
);

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

if ($result['status'] === 'success') {
    header('Location: verify_success.php?lost_id=' . ($_POST['lost_id'] ?? '') . '&found_id=' . ($_POST['found_id'] ?? ''));
} else {
    header('Location: verify_matches.php?error=' . urlencode($result['message']));
}
exit;
