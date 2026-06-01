<?php defined('NUFINDS_VIEW') || exit; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Error</title>
    <link rel="stylesheet" href="<?= nufinds_asset('css/message-pages.css') ?>">
</head>
<body class="message-page message-page--report-error">
    <div class="message-box">
        <h1>Unable to Submit Report</h1>
        <p><?= $safeMessage ?></p>
        <a href="javascript:history.back()">Go Back</a>
    </div>
</body>
</html>
