<?php
class LoginView {

    public static function renderError(string $message = 'An error occurred. Please try again.'): void {
        $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title>Login Error</title>
            <style>
                body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f8d7da; }
                .message-box { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; max-width: 420px; }
                .message-box h1 { margin: 0 0 1rem; color: #842029; }
                .message-box p { color: #664d54; }
                .message-box a { display: inline-block; margin-top: 1rem; color: #842029; text-decoration: none; border: 1px solid #842029; padding: 0.5rem 1rem; border-radius: 8px; }
            </style>
        </head>
        <body>
            <div class="message-box">
                <h1>Login Failed</h1>
                <p>{$safe}</p>
                <a href="login.html">Try Again</a>
            </div>
        </body>
        </html>
        HTML;
    }

    public static function renderSuccess(string $studentName = 'Student'): void {
        $safe = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title>Login Success</title>
            <style>
                body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f5f5f5; }
                .message-box { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; }
                .message-box h1 { margin: 0 0 1rem; }
                .message-box a { display: inline-block; margin-top: 1rem; color: #333; text-decoration: none; border: 1px solid #333; padding: 0.5rem 1rem; border-radius: 8px; }
            </style>
        </head>
        <body>
            <div class="message-box">
                <h1>Welcome, {$safe}!</h1>
                <p>You have successfully logged in.</p>
                <a href="home.php">Back to Home</a>
            </div>
        </body>
        </html>
        HTML;
    }
}