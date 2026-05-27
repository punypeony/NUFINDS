<?php
require_once __DIR__ . '/lib/SessionHelper.php';
require_once __DIR__ . '/lib/LoginView.php';

SessionHelper::requireLogin();
LoginView::renderSuccess(SessionHelper::get('StudentName', 'Student'));