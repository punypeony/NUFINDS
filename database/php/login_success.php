<?php
require_once '../database/php/SessionHelper.php';
require_once '../database/php/LoginView.php';

SessionHelper::requireLogin();
LoginView::renderSuccess(SessionHelper::get('StudentName', 'Student'));