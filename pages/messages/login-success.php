<?php defined('NUFINDS_VIEW') || exit; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Success</title>
    <link rel="stylesheet" href="<?= nufinds_asset('css/message-pages.css') ?>">
</head>
<body class="message-page message-page--login-success">
    <div class="message-box">
        <h1>Welcome, <?= $safeName ?>!</h1>
        <p>You have successfully logged in.</p>
        <a href="<?= $homeUrl ?>">Back to Home</a>
    </div>
</body>
</html>
