<?php
$error = $error ?? 'An unexpected error occurred. Please try again.';
?>
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
    <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <a href="javascript:history.back()">Go Back</a>
  </div>
</body>
</html>
