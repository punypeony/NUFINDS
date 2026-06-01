<?php

class LoginView {

    public static function renderError(string $message = 'An error occurred. Please try again.'): void {
        require_once __DIR__ . '/bootstrap.php';
        nufinds_require('lib/View.php');
        nufinds_render('messages/login-error.php', [
            'safeMessage' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
            'loginUrl'    => nufinds_pages_url('login.html'),
        ]);
    }

    public static function renderSuccess(string $studentName = 'Student'): void {
        require_once __DIR__ . '/bootstrap.php';
        nufinds_require('lib/View.php');
        nufinds_render('messages/login-success.php', [
            'safeName' => htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'),
            'homeUrl'  => nufinds_student_page('home.html'),
        ]);
    }
}
