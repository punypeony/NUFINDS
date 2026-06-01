<?php defined('NUFINDS_VIEW') || exit; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Error</title>
    <link rel="stylesheet" href="<?= nufinds_asset('css/message-pages.css') ?>">
</head>
<body class="message-page message-page--login-error">
    <div class="message-box">
        <h1>Login Failed</h1>
        <p><?= $safeMessage ?></p>
        <a href="<?= $loginUrl ?>">Try Again</a>
    </div>
</body>
</html>
