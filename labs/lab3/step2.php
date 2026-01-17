<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/layout_bs.php';
bs_layout_start('Lab 3 – UNION-based SQLi (Step 2)');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Lab 3: UNION-based SQL Injection</h1>
        <p class="text-secondary mb-0">Step 2 — Правила за UNION и методология</p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Lab 3</span>
    </div>

    <hr>

    <div class="btn-group mb-4" role="group">
      <a class="btn btn-outline-primary" href="step1.php">Step 1</a>
      <a class="btn btn-primary" href="step2.php">Step 2</a>
      <a class="btn btn-outline-success" href="practice.php">Practice</a>
    </div>

    <h2 class="h5 fw-bold">1. Примерна уязвима търсачка</h2>
    <p class="text-secondary">
      Много приложения имат търсачка, която търси по име:
    </p>

    <pre class="bg-light border rounded p-3 mb-0"><code>SELECT name, description
FROM products
WHERE name LIKE '%$q%';</code></pre>

    <p class="text-secondary mt-3">
      Ако <code>$q</code> се “вгражда” директно, потребителят може да промени заявката.
    </p>

    <h2 class="h5 fw-bold mt-4">2. Основното правило на UNION</h2>
    <p class="text-secondary">
      За да работи <strong>UNION</strong>, двете SELECT заявки трябва да връщат:
    </p>
    <ul class="text-secondary">
      <li><strong>един и същ брой колони</strong></li>
      <li><strong>съвместими типове</strong> (например текст към текст)</li>
    </ul>

    <div class="alert alert-info">
      В нашата търсачка се визуализират <strong>2 колони</strong> (Name и Description).
      Това означава, че UNION частта трябва също да върне <strong>2 колони</strong>.
    </div>

    <h2 class="h5 fw-bold mt-4">3. Учебна методология</h2>
    <ol class="text-secondary">
      <li><strong>Baseline:</strong> провери нормално търсене (примерно “Phone”), за да видиш как изглеждат резултатите.</li>
      <li><strong>Определи структурата:</strong> виждаш 2 колони → значи и UNION частта трябва да е с 2 колони.</li>
      <li><strong>Цел:</strong> да добавиш ред в резултатите, който съдържа информация от друга таблица (например users).</li>
      <li><strong>Успех:</strong> в резултатите да се появи “admin”.</li>
    </ol>

    <h2 class="h5 fw-bold mt-4">4. Чести причини “да не стане”</h2>
    <ul class="text-secondary">
      <li>грешен брой колони (най-често)</li>
      <li>несъвместими типове (число срещу текст)</li>
      <li>редът се “губи” сред много резултати → пробвай по-специфично търсене</li>
      <li>филтриране/санитизация (в този lab няма, но в реални системи има)</li>
    </ul>

    <h2 class="h5 fw-bold mt-4">5. Защита</h2>
    <ul class="text-secondary">
      <li>Prepared statements</li>
      <li>Валидация/ограничаване на входа (allowlist при search)</li>
      <li>Минимални DB привилегии</li>
    </ul>

    <div class="d-flex justify-content-between mt-4">
      <a class="btn btn-outline-secondary" href="step1.php">← Back</a>
      <a class="btn btn-brand" href="practice.php">Към упражнението →</a>
    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
