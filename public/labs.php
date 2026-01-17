<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8" />
  <title>Лаборатории</title>
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

  <h1>Лаборатории (описания)</h1>
  <p>За да стартираш упражненията, трябва да влезеш в системата.</p>

  <h3>Lab 1: Authentication Bypass (SQLi)</h3>
  <ul>
    <li>Тип: In-band SQL Injection</li>
    <li>Цел: заобикаляне на вход чрез манипулиране на входните данни</li>
    <li>Защита: prepared statements</li>
  </ul>

  <h3>Lab 2: Search SQL Injection</h3>
  <ul>
    <li>Тип: In-band / UNION (в следващ етап)</li>
    <li>Цел: извличане на допълнителна информация през търсене</li>
  </ul>

  <h3>Lab 3: Error-based SQL Injection</h3>
  <ul>
    <li>Тип: Error-based</li>
    <li>Цел: демонстрация на изтичане на информация чрез грешки</li>
  </ul>

</body>
</html>
