<?php
$lostId  = $_GET['lost_id']  ?? null;
$foundId = $_GET['found_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verification Success</title>
  <style>
    body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f0f8fa; }
    .message-box { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; max-width: 420px; }
    h1 { margin: 0 0 1rem; color: #4caf50; }
    p { color: #333; line-height: 1.6; }
    a { display: inline-block; margin-top: 1.5rem; color: white; background: #1f7a8c; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: 8px; }
    a:hover { background: #155a6d; }
  </style>
</head>
<body>
  <div class="message-box">
    <h1>✓ Match Verified Successfully!</h1>
    <p>The lost and found items have been matched and moved to history.</p>
    <p>The found item status has been updated to "Claimed".</p>
    <a href="verify_matches.php">Back to Verification</a>
  </div>
</body>
</html>