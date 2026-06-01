<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
header('Location: ' . nufinds_pages_url('login.html'), true, 302);
exit;
