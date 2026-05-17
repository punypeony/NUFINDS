<?php
session_start();
if (empty($_SESSION['StudentNumber'])) {
    header('Location: ../../pages/login.html');
    exit;
}
$studentName = htmlspecialchars($_SESSION['StudentName'] ?? 'Student', ENT_QUOTES, 'UTF-8');
?>
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
    <h1>Welcome, <?= $studentName ?>!</h1>
    <p>You have successfully logged in.</p>
    <a href="../../pages/home.html">Back to Home</a>
  </div>
</body>
</html>
