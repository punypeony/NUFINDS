<?php
require_once __DIR__ . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');
SessionHelper::start();
$target = SessionHelper::isAdmin()
    ? nufinds_admin_page('home.html')
    : nufinds_student_page('home.html');
header('Location: ' . $target, true, 302);
exit;
