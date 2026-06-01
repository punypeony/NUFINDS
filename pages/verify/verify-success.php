<?php defined('NUFINDS_VIEW') || exit; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verification Success</title>
  <link rel="stylesheet" href="<?= nufinds_asset('css/message-pages.css') ?>">
</head>
<body class="message-page message-page--verify-success">
  <div class="message-box">
    <h1>✓ Match Verified Successfully!</h1>
    <p>The lost and found items have been matched and moved to history.</p>
    <p>The found item status has been updated to "Claimed".</p>
    <a href="<?= nufinds_php_url('verify/verify_matches.php') ?>">Back to Verification</a>
  </div>
</body>
</html>
