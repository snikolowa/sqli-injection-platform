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
  <title>Lab 4 – Error-based SQL Injection (Step 2)</title>
</head>
<body>
  <nav>
    <a href="/sqli-platform/public/dashboard.php">Dashboard</a> |
    <a href="/sqli-platform/labs/lab0/intro.php">Урок 0</a> |
    <a href="step1.php">Step 1</a> |
    <a href="/sqli-platform/public/profile.php">Профил</a> |
    <a href="/sqli-platform/public/logout.php">Logout</a>
  </nav>

  <h1>Lab 4: Error-based SQL Injection</h1>
  <h2>Step 2 — Как грешките “издават” информация</h2>

  <h3>1) Уязвима заявка (пример)</h3>
  <p>
    Да разгледаме типична уязвима заявка, в която входът <code>$id</code>
    се използва директно:
  </p>

  <pre><code>SELECT id, name, description
FROM products
WHERE id = $id;</code></pre>

  <p>
    Ако <code>$id</code> не се валидира като число, потребителят може да въведе
    стойност, която променя заявката или предизвиква грешка.
  </p>

  <hr>

  <h3>2) “Обикновени” грешки (информационен теч)</h3>
  <p>
    Дори само синтактична грешка може да помогне на атакуващия да разбере:
  </p>
  <ul>
    <li>дали параметърът се използва в SQL</li>
    <li>дали заявката очаква число или текст</li>
    <li>какъв е SQL диалектът/грешката</li>
  </ul>

  <p><strong>Пример (концептуално):</strong> входът предизвиква SQL syntax error.</p>

  <hr>

  <h3>3) Error-based извличане на стойности (по-интересната част)</h3>
  <p>
    В някои бази данни (вкл. MySQL) съществуват функции, които при определени аргументи
    могат да върнат грешка, съдържаща част от подадените данни.
    В учебна среда това позволява да “извадим” стойност чрез грешка.
  </p>

  <p><strong>Примерна идея (форма):</strong></p>
  <pre><code>... AND updatexml(1, concat('~', DATABASE(), '~'), 1)</code></pre>

  <p>
    Тук <code>DATABASE()</code> връща името на текущата база.
    Идеята е грешката да съдържа текста, който е построен с <code>concat</code>.
  </p>

  <p>
    Забележка: Това е демонстрация в контролирана среда за обучение.
  </p>

  <hr>

  <h3>4) Как да се предотврати?</h3>
  <ul>
    <li><strong>Не показвай SQL грешки</strong> към крайния потребител (логвай ги отделно)</li>
    <li>Използвай <strong>prepared statements</strong> / параметризирани заявки</li>
    <li>Валидирай входа (например <code>id</code> да е число)</li>
    <li>Минимални привилегии за DB потребителя</li>
  </ul>

  <hr>

  <h3>Практическа част</h3>
  <p>
    В практиката ще имаш поле за <code>id</code>. Целта е да предизвикаш грешка,
    която показва името на текущата база между символи <code>~</code>.
    При успех Lab 4 се отбелязва автоматично като Completed.
  </p>

  <p>
    <a href="practice.php">➡️ Към упражнението (Practice)</a>
  </p>
</body>
</html>
