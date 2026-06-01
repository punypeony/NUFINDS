<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
nufinds_require('lib/SessionHelper.php');
nufinds_require('lib/LoginView.php');

SessionHelper::requireLogin();
LoginView::renderSuccess(SessionHelper::get('StudentName', 'Student'));
