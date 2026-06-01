<?php

class ReportView {

    public static function renderError(string $message = 'An unexpected error occurred. Please try again.'): void {
        require_once __DIR__ . '/bootstrap.php';
        nufinds_require('lib/View.php');
        nufinds_render('messages/report-error.php', [
            'safeMessage' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
        ]);
    }

    public static function renderSuccess(
        string $title = 'Report Submitted',
        string $message = 'Your report has been submitted successfully.'
    ): void {
        require_once __DIR__ . '/bootstrap.php';
        nufinds_require('lib/View.php');
        nufinds_render('messages/report-success.php', [
            'safeTitle'   => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'safeMessage' => nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')),
            'homeUrl'     => nufinds_student_page('home.html'),
        ]);
    }
}
