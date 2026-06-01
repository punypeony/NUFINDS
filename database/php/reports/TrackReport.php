<?php
require_once dirname(__DIR__) . '/lib/bootstrap.php';
header('Location: ' . nufinds_student_page('trackreport.html'), true, 302);
exit;
