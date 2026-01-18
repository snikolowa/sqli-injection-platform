<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/../includes/layout_bs.php';

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

bs_layout_start('Регистрация');
?>

<div class="row justify-content-center">
  <div class="col-12 col-md-6 col-lg-5">

    <div class="card shadow-sm">
      <div class="card-body">

        <h1 class="h5 fw-bold mb-3">Регистрация</h1>
        <p class="text-secondary">
          Създай акаунт за достъп до учебните модули.
        </p>

        <?php if ($error): ?>
          <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
          </div>
        <?php endif; ?>

        <form method="post" action="process_register.php" autocomplete="off">

          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Име</label>
              <input type="text" name="first_name" class="form-control" maxlength="80">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Фамилия</label>
            <input type="text" name="last_name" class="form-control" maxlength="80">
            </div>
            <div class="col-12">
              <label class="form-label">Имейл</label>
              <input type="email" name="email" class="form-control" required maxlength="190">
            </div>
          </div>  

          <div class="mb-3">
            <label class="form-label">Потребителско име</label>
            <input type="text" name="username" class="form-control" required minlength="3">
          </div>

          <div class="mb-3">
            <label class="form-label">Парола</label>
            <input type="password" name="password" class="form-control" required minlength="6">
          </div>

          <div class="mb-3">
            <label class="form-label">Повтори паролата</label>
            <input type="password" name="password_confirm" class="form-control" required>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-brand">
              Регистрирай се
            </button>

            <a href="login.php" class="btn btn-outline-secondary">
              Имаш акаунт? Вход
            </a>
          </div>

        </form>

      </div>
    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
