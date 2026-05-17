<?php
$error = $error ?? 'An error occurred. Please try again.';
?>
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
    <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <a href="login.html">Try Again</a>
  </div>
</body>
</html>
