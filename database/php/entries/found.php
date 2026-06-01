<?php

require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/Database.php');
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/StudentPage.php');

SessionHelper::requireStudent();

$profile = nufinds_load_student_profile();
$profile['todayDate']     = nufinds_report_date_max();
$profile['minReportDate'] = nufinds_report_date_min();

return $profile;
