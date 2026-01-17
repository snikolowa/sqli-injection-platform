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
  <title>Lab 5 – Time-based Blind SQL Injection (Step 2)</title>
</head>
<body>
  <nav>
    <a href="/sqli-platform/public/dashboard.php">Dashboard</a> |
    <a href="/sqli-platform/labs/lab0/intro.php">Урок 0</a> |
    <a href="step1.php">Step 1</a> |
    <a href="/sqli-platform/public/profile.php">Профил</a> |
    <a href="/sqli-platform/public/logout.php">Logout</a>
  </nav>

  <h1>Lab 5: Time-based Blind SQL Injection</h1>
  <h2>Step 2 — Как времето става “oracle” (TRUE/FALSE)</h2>

  <h3>1) Основната идея</h3>
  <p>
    В този лаб ще използваме SQL логика, която:
  </p>
  <ul>
    <li>ако дадено условие е вярно → извиква функция за забавяне</li>
    <li>ако условието е невярно → не забавя</li>
  </ul>

  <h3>2) Примерна форма на SQL (MySQL)</h3>
  <p>
    В MySQL може да се използва условен израз <code>IF(...)</code> и функция за забавяне
    <code>SLEEP(seconds)</code>. Концептуално:
  </p>

  <pre><code>SELECT IF( (УСЛОВИЕ), SLEEP(2), 0 );</code></pre>

  <p>
    Ако <code>(УСЛОВИЕ)</code> е вярно → заявката се забавя ~2 секунди.<br>
    Ако е невярно → връща веднага.
  </p>

  <h3>3) Какви “въпроси” можем да задаваме?</h3>
  <p>
    Както при boolean-based, можем да проверяваме факти за данните. Например:
  </p>

  <p><strong>Пример A (първи символ):</strong></p>
  <pre><code>SUBSTRING(password, 1, 1) = 'a'</code></pre>

  <p><strong>Пример B (дължина):</strong></p>
  <pre><code>LENGTH(password) = 8</code></pre>

  <p>
    Тук резултатът не е “TRUE/FALSE” на екрана, а “бавен/бърз” отговор.
  </p>

  <h3>4) Как ще работи практиката в нашата платформа?</h3>
  <p>
    В практическата част ще въведеш SQL условие, което приложението ще изпълни в уязвима заявка.
    Платформата ще измери времето за изпълнение и ще ти покаже:
  </p>
  <ul>
    <li><strong>DELAYED</strong> – ако отговорът е забавен</li>
    <li><strong>NO DELAY</strong> – ако няма забавяне</li>
  </ul>

  <p>
    Целта е да потвърдиш конкретен факт (в този лаб: първият символ на admin паролата).
    При успех Lab 5 се отбелязва автоматично като Completed.
  </p>

  <p>
    <a href="practice.php">➡️ Към упражнението (Practice)</a>
  </p>
</body>
</html>
