<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/layout_bs.php';

$loggedIn = !empty($_SESSION['user_id']);
$base = '/sqli-platform';

bs_layout_start('Лаборатории – SQL Injection');
?>

<div class="p-4 p-md-5 bg-white rounded-4 shadow-sm border">
  <h1 class="h3 fw-bold mb-2">Модули</h1>
  <p class="text-secondary mb-4">
    Тази страница съдържа описания на модулите.  
    За да стартираш упражненията и да следиш прогреса си, е необходим вход.
  </p>

  <div class="d-flex flex-wrap gap-2 mb-4">
    <?php if ($loggedIn): ?>
      <a class="btn btn-brand" href="<?php echo $base; ?>/public/dashboard.php">
        Към таблото
      </a>
    <?php else: ?>
      <a class="btn btn-brand" href="<?php echo $base; ?>/public/login.php">
        Вход
      </a>
    <?php endif; ?>
  </div>

  <div class="row g-3">

      <!-- Lab 0 -->
    <div class="col-12 col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h2 class="h5 fw-bold">Въведение</h2>
        </div>
      </div>
    </div>

    <!-- Lab 1 -->
    <div class="col-12 col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h2 class="h5 fw-bold">Модул 1: Authentication Bypass</h2>
          <ul class="text-secondary mb-0">
            <li>Тип: In-band SQL Injection</li>
            <li>Цел: заобикаляне на логин механизъм</li>
            <li>Фокус: OR логика, SQL коментари</li>
            <li>Защита: prepared statements</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Lab 2 -->
    <div class="col-12 col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h2 class="h5 fw-bold">Модул 2: Boolean-based Blind SQLi</h2>
          <ul class="text-secondary mb-0">
            <li>Тип: Inferential (Blind)</li>
            <li>Цел: Извличане на информация чрез TRUE/FALSE</li>
            <li>Фокус: Логически условия</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Lab 3 -->
    <div class="col-12 col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h2 class="h5 fw-bold">Модул 3: UNION-based SQL Injection</h2>
          <ul class="text-secondary mb-0">
            <li>Тип: In-band / UNION</li>
            <li>Цел: Извличане на данни чрез UNION SELECT</li>
            <li>Фокус: Брой колони, типове</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Lab 4 -->
    <div class="col-12 col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h2 class="h5 fw-bold">Модул 4: Error-based SQL Injection</h2>
          <ul class="text-secondary mb-0">
            <li>Тип: Error-based</li>
            <li>Цел: Извличане на информация чрез SQL грешки</li>
            <li>Фокус: Database(), updatexml()</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Lab 5 -->
    <div class="col-12 col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h2 class="h5 fw-bold">Модул 5: Time-based Blind SQL Injection</h2>
          <ul class="text-secondary mb-0">
            <li>Тип: Time-based Blind</li>
            <li>Цел: Извличане чрез време за отговор</li>
            <li>Фокус: IF(), SLEEP()</li>
          </ul>
        </div>
      </div>
    </div>

  </div>

  <div class="alert alert-warning mt-4 mb-0">
    <strong>Важно:</strong> Всички лаборатории са умишлено уязвими и са предназначени
    <strong>само за учебни цели</strong> в контролирана среда.
  </div>
</div>

<?php bs_layout_end(); ?>
