<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

echo json_encode(['csrf_token' => SessionHelper::generateCsrfToken()]);
