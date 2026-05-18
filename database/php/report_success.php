<?php
$title = $title ?? 'Report Submitted';
$message = $message ?? 'Your report has been submitted successfully.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
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
    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) ?></p>
    <a href="../../pages/home.php">Back to Home</a>
  </div>
</body>
</html>
