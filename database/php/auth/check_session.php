<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

SessionHelper::start();

if (SessionHelper::isAdmin()) {
    echo json_encode(['logged_in' => true, 'role' => 'admin']);
    exit;
}

if (SessionHelper::isStudent()) {
    echo json_encode(['logged_in' => true, 'role' => 'student']);
    exit;
}

echo json_encode(['logged_in' => false]);
