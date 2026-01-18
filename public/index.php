<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/layout_bs.php';

$base = '/sqli-platform';
$loggedIn = !empty($_SESSION['user_id']);

bs_layout_start('SQLi Training Platform');
?>

<div class="p-4 p-md-5 bg-white rounded-4 shadow-sm border">
  <div class="row g-4 align-items-center">
    <div class="col-12 col-lg-8">
      <h1 class="display-6 fw-bold mb-3">
        Уеб базирана платформа за обучение по SQL Injection
      </h1>

      <p class="text-secondary mb-4">
        Платформата предоставя структурирани уроци и практическа среда за тестване на SQL Injection
        в контролирана (локална) среда. Всеки lab отключва следващия.
      </p>

      <div class="d-flex flex-wrap gap-2">
        <?php if ($loggedIn): ?>
          <a class="btn btn-brand" href="<?php echo $base; ?>/public/dashboard.php">Табло</a>
          <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/profile.php">Профил</a>
        <?php else: ?>
          <a class="btn btn-brand" href="<?php echo $base; ?>/public/login.php">Вход</a>
              <a href="/sqli-platform/public/register.php"class="btn btn-outline-primary btn-lg">Регистрация</a>
        <?php endif; ?>
      </div>

      <div class="alert alert-warning mt-4 mb-0">
        <strong>Важно:</strong> Платформата е предназначена само за обучение и тестване в локална среда.
        Не използвай техники извън контролирана среда.
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h2 class="h5 fw-bold mb-2">Как работи?</h2>
          <ul class="text-secondary mb-0">
            <li>След вход получаваш достъп до урок + практика.</li>
            <li>При успешно решаване прогресът се записва автоматично.</li>
            <li>Следващият lab се отключва след като предишният е Completed.</li>
          </ul>
        </div>
      </div>
    </div>

  </div>
</div>

<div class="row g-3 mt-3">
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h3 class="h6 fw-bold mb-2">Учебен фокус</h3>
        <p class="text-secondary mb-0">
          Демонстрации на уязвимостите: Authentication bypass, Blind SQLi, UNION, Error-based и Time-based техники.
        </p>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h3 class="h6 fw-bold mb-2">Контролирана среда</h3>
        <p class="text-secondary mb-0">
          Лабовете са умишлено уязвими и са предназначени за безопасно обучение.
        </p>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h3 class="h6 fw-bold mb-2">Проследяване на прогрес</h3>
        <p class="text-secondary mb-0">
          Прогресът се пази в профила ти и се вижда в Dashboard.
        </p>
      </div>
    </div>
  </div>
</div>

<?php bs_layout_end(); ?>
