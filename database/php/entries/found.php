<?php

require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/Database.php');
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/StudentPage.php');

SessionHelper::requireStudent();

$profile = nufinds_load_student_profile();
$profile['todayDate']     = date('Y-m-d');
$profile['minReportDate'] = date('Y-m-d', strtotime('-1 year'));

return $profile;
