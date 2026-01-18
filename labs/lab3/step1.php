<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/lab_gate.php';
require_once __DIR__ . '/../../includes/layout_bs.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

// Lab 3 е заключен, ако Lab 2 не е Completed
require_prereq_or_block($conn, $userId, 'LAB2_BOOLEAN_BLIND');

bs_layout_start('Lab 3 – UNION-based SQL Injection (Step 1)');
?>


<div class="card shadow-sm">
  <div class="card-body">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Модул 3: UNION-based SQL Injection</h1>
        <p class="text-secondary mb-0">
          In-band SQLi: извличане на данни директно през резултатите на страницата.
        </p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Модул 3</span>
    </div>

    <hr>

    <div class="btn-group mb-4" role="group">
      <a class="btn btn-primary" href="step1.php">Урок</a>
      <a class="btn btn-outline-primary" href="step2.php">Примери</a>
      <a class="btn btn-outline-success" href="practice.php">Упражнение</a>
    </div>

    <h2 class="h5 fw-bold">1. Какво е UNION-based SQL Injection?</h2>
    <p class="text-secondary">
      UNION-based SQL Injection е техника, при която атакуващият използва SQL оператора
      <strong>UNION</strong>, за да “добави” резултатите от друга SELECT заявка към резултатите,
      които приложението по принцип показва на екрана.
    </p>

    <h2 class="h5 fw-bold mt-4">2. Защо се счита за In-band?</h2>
    <p class="text-secondary">
      Това е <strong>in-band</strong> техника, защото същият канал (уеб страницата),
      който изпраща заявката, връща и данните обратно като видим резултат.
      Например търсачка, която показва списък от продукти.
    </p>

    <h2 class="h5 fw-bold mt-4">3. Кога се получава уязвимост?</h2>
    <ul class="text-secondary">
      <li>когато входът (например търсене) се “слепва” директно в SQL заявка;</li>
      <li>когато резултатите се визуализират (таблица/списък);</li>
      <li>когато няма prepared statements / параметризирани заявки.</li>
    </ul>

    <div class="alert alert-warning mt-4 mb-0">
      <strong>Важно:</strong> В Модул 3 ще имаш уязвима търсачка. Целта е в резултатите да се появи “admin”.
    </div>

    <div class="d-flex justify-content-end mt-4">
      <a class="btn btn-brand" href="step2.php">Продължи към примерите →</a>
    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
