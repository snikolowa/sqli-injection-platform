<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/lab_gate.php';
require_once __DIR__ . '/../../includes/layout_bs.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

// Lab 5 е заключен, ако Lab 4 не е Completed
require_prereq_or_block($conn, $userId, 'LAB4_ERROR_BASED');

bs_layout_start('Lab 5 – Time-based Blind SQL Injection (Step 1)');
?>


<div class="card shadow-sm">
  <div class="card-body">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Модул 5: Time-based Blind SQL Injection</h1>
        <p class="text-secondary mb-0">
          Какво представлява time-based (blind) SQLi?
        </p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Модул 5</span>
    </div>

    <hr>

    <!-- Navigation -->
    <div class="btn-group mb-4" role="group" aria-label="Lab navigation">
      <a class="btn btn-primary" href="step1.php">Урок</a>
      <a class="btn btn-outline-primary" href="step2.php">Примери</a>
      <a class="btn btn-outline-success" href="practice.php">Упражнение</a>
    </div>

    <!-- Content -->
    <div>

      <h2 class="h5 fw-bold">1. Защо “time-based”?</h2>
      <p class="text-secondary">
        В реални приложения често няма видим резултат (не виждаш данни) и няма грешки (не виждаш error),
        но приложението все пак прави заявки към базата данни.
      </p>
      <p class="text-secondary">
        При <strong>time-based blind SQL injection</strong> се използва
        <strong>времето за отговор</strong> като “канал” за информация. Идеята е:
      </p>
      <ul class="text-secondary">
        <li>Ако условието е вярно → заявката умишлено “забавя” отговора</li>
        <li>Ако условието е невярно → отговорът идва бързо</li>
      </ul>

      <h2 class="h5 fw-bold mt-4">2. Какво получава атакуващият?</h2>
      <p class="text-secondary">
        Само наблюдение: дали страницата се зарежда “нормално” или “осезаемо по-бавно”.
        Това е достатъчно, за да се извлича информация като поредица от да/не проверки —
        но вместо текст TRUE/FALSE се използва време.
      </p>

      <h2 class="h5 fw-bold mt-4">3. Кога се среща?</h2>
      <ul class="text-secondary">
        <li>когато грешките са скрити</li>
        <li>когато данните не се извеждат</li>
        <li>но приложението е уязвимо (динамичен SQL вход)</li>
      </ul>

      <h2 class="h5 fw-bold mt-4">4. Как се предотвратява?</h2>
      <ul class="text-secondary">
        <li>Prepared statements / параметризирани заявки</li>
        <li>валидация на входа (особено при условия/филтри)</li>
        <li>никога да не се позволява “SQL условие” от потребителя</li>
        <li>rate limiting + мониторинг за забавени заявки (анти-абуз мерки)</li>
      </ul>

      <div class="alert alert-warning mt-4">
        <strong>Важно:</strong> Този модул е умишлено уязвим и е предназначен само за учебни цели в контролирана среда.
      </div>

      <div class="d-flex justify-content-end mt-4">
        <a class="btn btn-brand" href="step2.php">Продължи към примерите →</a>
      </div>

    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
