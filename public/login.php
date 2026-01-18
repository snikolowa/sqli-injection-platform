<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$justRegistered = !empty($_SESSION['register_success']);
unset($_SESSION['register_success']);


if (!empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/../includes/layout_bs.php';

$error = isset($_GET['error']) ? $_GET['error'] : '';

bs_layout_start('Login');
?>

<div class="row justify-content-center">
  <div class="col-12 col-md-8 col-lg-5">

    <div class="card shadow-sm">
      <div class="card-body">

        <!-- Header -->
        <div class="text-center mb-4">
          <h1 class="h4 fw-bold mb-1">Вход в платформата</h1>
          <?php if ($justRegistered): ?>
            <div class="alert alert-success">
              ✅ Регистрацията е успешна!
            </div>
          <?php endif; ?>

          <p class="text-secondary mb-0">
            SQL Injection Training Platform
          </p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger">
            ❌ Невалидно потребителско име или парола.
          </div>
        <?php endif; ?>

        <!-- Login form -->
        <form method="post" action="process_login.php" autocomplete="off">
          <div class="mb-3">
            <label class="form-label">Потребителско име</label>
            <input
              type="text"
              name="username"
              class="form-control"
              required
              autofocus
            >
          </div>

          <div class="mb-3">
            <label class="form-label">Парола</label>
            <input
              type="password"
              name="password"
              class="form-control"
              required
            >
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-brand">
              Вход
            </button>
          </div>
        </form>

        <hr class="my-4">

        <div class="text-center">
          <a href="register.php" class="btn btn-outline-primary">
              Създай акаунт
          </a>
        </div>

        <div class="text-center mt-4">
          <a href="/sqli-platform/public/index.php" class="text-decoration-none">
            ← Обратно към началната страница
          </a>
        </div>

      </div>
    </div>

    <div class="small text-secondary text-center mt-3">
      Само за учебни цели • локална среда
    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
