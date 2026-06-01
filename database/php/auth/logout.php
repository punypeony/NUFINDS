<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
require_once __DIR__ . '/Auth.php';

(new Auth())->logout();
