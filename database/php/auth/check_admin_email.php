<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/Database.php');
nufinds_require('lib/AdminAuth.php');

header('Content-Type: application/json; charset=utf-8');

$email = trim($_GET['email'] ?? '');
echo json_encode(['is_admin' => AdminAuth::isAdminEmail($email)]);
