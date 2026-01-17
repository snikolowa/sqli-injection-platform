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
  <title>Lab 5 – Time-based Blind SQL Injection (Step 1)</title>
</head>
<body>
  <nav>
    <a href="/sqli-platform/public/dashboard.php">Dashboard</a> |
    <a href="/sqli-platform/labs/lab0/intro.php">Урок 0</a> |
    <a href="/sqli-platform/public/profile.php">Профил</a> |
    <a href="/sqli-platform/public/logout.php">Logout</a>
  </nav>

  <h1>Lab 5: Time-based Blind SQL Injection</h1>
  <h2>Step 1 — Какво представлява time-based (blind) SQLi?</h2>

  <h3>1) Защо “time-based”?</h3>
  <p>
    В реални приложения често няма видим резултат (не виждаш данни) и няма грешки (не виждаш error).
    Но приложението все пак прави заявки към базата данни.
  </p>
  <p>
    При <strong>time-based blind SQL injection</strong> атакуващият използва
    <strong>времето за отговор</strong> като “канал” за информация.
    Идеята е:
  </p>
  <ul>
    <li>Ако условието е вярно → заявката умишлено “забавя” отговора</li>
    <li>Ако условието е невярно → отговорът идва бързо</li>
  </ul>

  <h3>2) Какво получава атакуващият?</h3>
  <p>
    Само наблюдение: дали страницата се зарежда “нормално” или “осезаемо по-бавно”.
    Това е достатъчно, за да се извлича информация бит по бит (да/не) — подобно на Lab 2,
    но вместо текст TRUE/FALSE, използваме време.
  </p>

  <h3>3) Кога се среща?</h3>
  <ul>
    <li>когато грешките са скрити</li>
    <li>когато данните не се извеждат</li>
    <li>но приложението е уязвимо (динамичен SQL вход)</li>
  </ul>

  <h3>4) Как се предотвратява?</h3>
  <ul>
    <li>Prepared statements / параметризирани заявки</li>
    <li>валидация на входа (особено при условия/филтри)</li>
    <li>никога да не се позволява “SQL условие” от потребителя</li>
    <li>rate limiting + мониторинг за забавени заявки (анти-абуз мерки)</li>
  </ul>

  <p>
    <a href="step2.php">➡️ Next (Step 2: Как се използва забавяне като TRUE/FALSE)</a>
  </p>
</body>
</html>
