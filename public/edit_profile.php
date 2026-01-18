<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout_bs.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$error = $_GET['error'] ?? '';
$ok = $_GET['ok'] ?? '';

$userRow = null;
$stmt = mysqli_prepare($conn, "SELECT username, first_name, last_name, email FROM users WHERE id = ?");
if ($stmt) {
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $userRow = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);
}

bs_layout_start('Редакция на профил');
?>

<div class="row justify-content-center">
  <div class="col-12 col-md-7 col-lg-6">

    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="h5 fw-bold mb-2">Редакция на профил</h1>
        <p class="text-secondary mb-3">
          Потребителското име и имейлът не могат да се променят.
        </p>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($ok): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($ok); ?></div>
        <?php endif; ?>

        <form method="post" action="process_update_profile.php" autocomplete="off">
          <div class="mb-3">
            <label class="form-label">Потребителско име</label>
            <input class="form-control" value="<?php echo htmlspecialchars($userRow['username'] ?? ''); ?>" disabled>
          </div>

          <div class="mb-3">
            <label class="form-label">Имейл</label>
            <input class="form-control" value="<?php echo htmlspecialchars($userRow['email'] ?? ''); ?>" disabled>
          </div>

          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Име</label>
              <input type="text" name="first_name" class="form-control" maxlength="80"
                     value="<?php echo htmlspecialchars($userRow['first_name'] ?? ''); ?>">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Фамилия</label>
              <input type="text" name="last_name" class="form-control" maxlength="80"
                     value="<?php echo htmlspecialchars($userRow['last_name'] ?? ''); ?>">
            </div>
          </div>

          <div class="d-flex gap-2 mt-4">
            <button class="btn btn-brand" type="submit">Запази</button>
            <a class="btn btn-outline-secondary" href="/sqli-platform/public/profile.php">Отказ</a>
          </div>
        </form>

      </div>
    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
