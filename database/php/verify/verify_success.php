<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/View.php');

SessionHelper::requireAdmin();

nufinds_render('verify/verify-success.php');
