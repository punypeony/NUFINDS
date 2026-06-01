<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
header('Location: ' . nufinds_student_page('lost.html'), true, 302);
exit;
