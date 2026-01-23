<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/lab_gate.php';
require_once __DIR__ . '/../../includes/layout_bs.php';
require_once __DIR__ . '/../../includes/modules.php';
require_once __DIR__ . '/../../includes/attempt_logger.php';
require_once __DIR__ . '/../../includes/points.php';

$LAB_CODE = "LAB4_ERROR_BASED";

$message = "";
$completedNow = false;
$next = get_next_module($LAB_CODE);

$userId = (int)($_SESSION['user_id'] ?? 0);
$usernameSess = (string)($_SESSION['username'] ?? '');

require_prereq_or_block($conn, $userId, 'LAB3_UNION_BASED');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $q = $_POST['q'] ?? '';

    // intentionally vulnerable (error-based)
    $sql = "SELECT id FROM users WHERE username = '$q'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $message = "🎉 Успешно! Получи резултат.";
        $completedNow = true;
    } else {
        $message = "Няма резултат. Опитай пак.";
    }

    $lab = "lab4_practice";
    $successInt = $completedNow ? 1 : 0;
    log_attempt($conn, $userId, $usernameSess, $lab, $successInt, (string)$q);

    if ($completedNow && $userId > 0) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO user_progress (user_id, lab_code, completed, completed_at)
            VALUES (?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE completed = 1, completed_at = NOW()
        ");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "is", $userId, $LAB_CODE);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        $awarded = points_award_for_lab_completion($conn, $userId, $LAB_CODE);
        if ($awarded > 0) {
            $message .= " (+{$awarded} точки)";
        }
    }
}

bs_layout_start('Lab 4 – Practice');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Модул 4: Practice – Error-based SQLi</h1>
        <p class="text-secondary mb-0">
          Цел: да разчиташ/използваш грешки (в уязвима среда).
        </p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Модул 4</span>
    </div>

    <hr>

    <div class="btn-group mb-3" role="group">
      <a class="btn btn-outline-primary" href="step1.php">Урок</a>
      <a class="btn btn-outline-primary" href="step2.php">Примери</a>
      <a class="btn btn-success" href="practice.php">Упражнение</a>
    </div>

    <?php if ($message): ?>
      <div class="alert <?php echo $completedNow ? 'alert-success' : 'alert-secondary'; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <div id="exercise-status" data-solved="<?php echo $completedNow ? '1' : '0'; ?>"></div>

    <form method="post" class="row g-3 mt-2" autocomplete="off">
      <div class="col-12">
        <label class="form-label">Username</label>
        <input type="text" name="q" class="form-control" required>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-brand">Провери</button>
      </div>
    </form>

  </div>
</div>

<?php bs_layout_end(); ?>
