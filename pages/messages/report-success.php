<?php defined('NUFINDS_VIEW') || exit; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $safeTitle ?></title>
    <link rel="stylesheet" href="<?= nufinds_asset('css/message-pages.css') ?>">
</head>
<body class="message-page message-page--report-success">
    <div class="message-box">
        <h1><?= $safeTitle ?></h1>
        <p class="pre-wrap"><?= $safeMessage ?></p>
        <a href="<?= $homeUrl ?>">Back to Home</a>
    </div>
</body>
</html>
