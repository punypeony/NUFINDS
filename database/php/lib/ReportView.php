<?php
class ReportView {

    public static function renderError(string $message = 'An unexpected error occurred. Please try again.'): void {
        $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title>Report Error</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #fff1f1; }
                .message-box { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 20px 50px rgba(0,0,0,0.08); max-width: 420px; width: 100%; text-align: center; }
                h1 { margin: 0 0 1rem; color: #b00020; }
                p { line-height: 1.6; color: #333; }
                a { display: inline-block; margin-top: 1.5rem; text-decoration: none; color: white; background: #b00020; padding: 0.85rem 1.5rem; border-radius: 10px; }
            </style>
        </head>
        <body>
            <div class="message-box">
                <h1>Unable to Submit Report</h1>
                <p>{$safe}</p>
                <a href="javascript:history.back()">Go Back</a>
            </div>
        </body>
        </html>
        HTML;
    }

    public static function renderSuccess(string $title = 'Report Submitted', string $message = 'Your report has been submitted successfully.'): void {
        $safeTitle   = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title>{$safeTitle}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f4f8fb; }
                .message-box { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 20px 50px rgba(0,0,0,0.08); max-width: 420px; width: 100%; text-align: center; }
                h1 { margin: 0 0 1rem; color: #073b4c; }
                p { line-height: 1.6; color: #333; white-space: pre-wrap; }
                a { display: inline-block; margin-top: 1.5rem; text-decoration: none; color: white; background: #1f7a8c; padding: 0.85rem 1.5rem; border-radius: 10px; }
            </style>
        </head>
        <body>
            <div class="message-box">
                <h1>{$safeTitle}</h1>
                <p>{$safeMessage}</p>
                <a href="home.php">Back to Home</a>
            </div>
        </body>
        </html>
        HTML;
    }
}