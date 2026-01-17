<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8" />
  <title>SQLi Training Platform</title>
</head>
<body>
  <nav>
    <a href="index.php">Начало</a> |
    <a href="labs.php">Лаборатории</a> |
    <?php if (!empty($_SESSION['user_id'])): ?>
      <a href="dashboard.php">Dashboard</a> |
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Login</a>
    <?php endif; ?>
  </nav>

  <h1>Уеб базирана платформа за обучение и тестване на SQL Injection</h1>
  <p>
    Платформата предоставя структурирани уроци и практическа среда за тестване на SQL Injection атаки
    в контролирана (локална) среда.
  </p>

  <h3>Как работи?</h3>
  <ul>
    <li>Гостите могат да разглеждат описанията на лабораториите.</li>
    <li>След вход, потребителят получава достъп до урок + практика.</li>
    <li>След успешно решаване се показва <strong>flag</strong>, който се въвежда за отбелязване на прогрес.</li>
  </ul>

  <p><strong>Важно:</strong> Платформата е предназначена само за обучение и тестване в локална среда.</p>
</body>
</html>

