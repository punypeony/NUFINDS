<?php

require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');

if (SessionHelper::isAdmin()) {
    header('Location: ' . nufinds_admin_page('home.html'));
    exit;
}

SessionHelper::requireStudent();

nufinds_require('lib/StudentPage.php');

return nufinds_load_student_profile();
