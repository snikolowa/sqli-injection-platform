<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8" />
  <title>Login</title>
</head>
<body>
  <nav>
    <a href="index.php">Начало</a> |
    <a href="labs.php">Лаборатории</a> |
    <a href="login.php">Login</a>
  </nav>

  <h1>Вход в платформата</h1>

  <?php if ($error): ?>
    <p style="color:red;">Невалидно потребителско име или парола.</p>
  <?php endif; ?>

  <form method="post" action="process_login.php">
    <label>Потребителско име:</label><br>
    <input type="text" name="username" required><br><br>

    <label>Парола:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Вход</button>
  </form>

  <p style="margin-top:16px;">
    <a href="index.php">← Обратно към началната страница</a>
  </p>
</body>
</html>
