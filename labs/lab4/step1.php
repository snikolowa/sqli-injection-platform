<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/layout_bs.php';
bs_layout_start('Lab 1 – Step 2');
?>

<div class="card shadow-sm">
  <div class="card-body">
    <h1 class="h4 fw-bold">Lab 1: Step 2</h1>
    <p class="text-secondary">...</p>
  </div>
</div>

<?php bs_layout_end(); ?>

<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8" />
  <title>Lab 4 – Error-based SQL Injection (Step 1)</title>
</head>
<body>
  <nav>
    <a href="/sqli-platform/public/dashboard.php">Dashboard</a> |
    <a href="/sqli-platform/labs/lab0/intro.php">Урок 0</a> |
    <a href="/sqli-platform/public/profile.php">Профил</a> |
    <a href="/sqli-platform/public/logout.php">Logout</a>
  </nav>

  <h1>Lab 4: Error-based SQL Injection</h1>
  <h2>Step 1 — Какво е Error-based SQLi?</h2>

  <h3>1) Какво означава “error-based”?</h3>
  <p>
    Error-based SQL Injection е техника, при която атакуващият използва
    <strong>съобщенията за грешки</strong>, които базата данни връща,
    за да получи информация за структурата на заявката, базата, таблици/колони и др.
  </p>

  <h3>2) Защо грешките са проблем?</h3>
  <p>
    В реални приложения грешките често “издават” твърде много:
  </p>
  <ul>
    <li>SQL синтаксис и част от заявката</li>
    <li>имена на таблици/колони</li>
    <li>типове данни</li>
    <li>понякога дори стойности (при специфични функции)</li>
  </ul>

  <h3>3) Кога се среща най-често?</h3>
  <p>
    Най-често при:
  </p>
  <ul>
    <li>стари приложения</li>
    <li>debug режим, оставен включен в продукция</li>
    <li>неправилна обработка на грешки (напр. echo на mysqli_error)</li>
    <li>динамично “слепване” на входа в SQL</li>
  </ul>

  <h3>4) Каква ще е задачата в този лаб?</h3>
  <p>
    В практическата част ще имаш поле за вход (параметър),
    който се използва в SQL заявка. Приложението умишлено показва
    SQL грешките (учебна среда).
  </p>

  <p>
    Целта ще бъде чрез error-based подход да предизвикаш
    грешка, която да “изведе” конкретна информация (в този лаб: името на текущата база).
  </p>

  <p>
    <a href="step2.php">➡️ Next (Step 2: Пример със заявка и как се “извлича” информация)</a>
  </p>
</body>
</html>
