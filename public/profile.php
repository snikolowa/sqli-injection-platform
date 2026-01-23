<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout_bs.php';
require_once __DIR__ . '/../includes/modules.php';
require_once __DIR__ . '/../includes/points.php';

$base = base_url();

$userId = (int)($_SESSION['user_id'] ?? 0);
$isAdmin = function_exists('is_admin') ? is_admin() : false;

// --- User basic data ---
$userRow = null;
$stmt = mysqli_prepare($conn, "SELECT username, first_name, last_name, email, COALESCE(role,'user') AS role FROM users WHERE id = ?");
if ($stmt) {
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $userRow = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);
}

$username = (string)($userRow['username'] ?? '');
$email = (string)($userRow['email'] ?? '');
$role = (string)($userRow['role'] ?? 'user');
$fullName = trim(((string)($userRow['first_name'] ?? '')) . ' ' . ((string)($userRow['last_name'] ?? '')));
if ($fullName === '') $fullName = '—';

// --- Progress (only meaningful for normal users) ---
$mainLabCodes = [
  'LAB1_AUTH_BYPASS',
  'LAB2_BOOLEAN_BLIND',
  'LAB3_UNION_BASED',
  'LAB4_ERROR_BASED',
  'LAB5_TIME_BASED',
];

$completedMain = 0;
$progressPct = 0;

if (!$isAdmin) {
  $placeholders = implode(',', array_fill(0, count($mainLabCodes), '?'));
  $sql = "SELECT COUNT(DISTINCT lab_code) AS c
          FROM user_progress
          WHERE user_id = ? AND completed = 1 AND lab_code IN ($placeholders)";
  $stmtP = mysqli_prepare($conn, $sql);
  if ($stmtP) {
    $types = 'i' . str_repeat('s', count($mainLabCodes));
    $params = array_merge([$userId], $mainLabCodes);
    $bind = [];
    $bind[] = $types;
    foreach ($params as $k => $v) $bind[] = &$params[$k];
    call_user_func_array([$stmtP, 'bind_param'], $bind);

    mysqli_stmt_execute($stmtP);
    $r = mysqli_stmt_get_result($stmtP);
    if ($r && ($row = mysqli_fetch_assoc($r))) {
      $completedMain = (int)($row['c'] ?? 0);
    }
    mysqli_stmt_close($stmtP);
  }

  $progressPct = (int)round(($completedMain / max(1, count($mainLabCodes))) * 100);
}

// --- Attempts aggregates ---
$attemptsTotal = 0;
$successTotal = 0;
$lastAttemptAt = null;
$lastSuccessAt = null;

$stmtA = mysqli_prepare($conn, "SELECT attempts_total, success_total, last_attempt_at, last_success_at FROM attempts_agg_user WHERE user_id = ? LIMIT 1");
if ($stmtA) {
  mysqli_stmt_bind_param($stmtA, 'i', $userId);
  mysqli_stmt_execute($stmtA);
  $r = mysqli_stmt_get_result($stmtA);
  if ($r && ($row = mysqli_fetch_assoc($r))) {
    $attemptsTotal = (int)($row['attempts_total'] ?? 0);
    $successTotal = (int)($row['success_total'] ?? 0);
    $lastAttemptAt = $row['last_attempt_at'] ?? null;
    $lastSuccessAt = $row['last_success_at'] ?? null;
  }
  mysqli_stmt_close($stmtA);
}

// --- Points (REAL) ---
$points = points_get_user_points($conn, $userId);

bs_layout_start('Профил');
?>

<div class="row g-3">

  <div class="col-12 col-lg-4">

    <div class="card shadow-sm">
      <div class="card-body">

        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-dark-subtle d-flex align-items-center justify-content-center"
               style="width: 46px; height: 46px;">
            <span class="fw-bold text-secondary">
              <?php echo htmlspecialchars(mb_strtoupper(mb_substr($username, 0, 1, 'UTF-8'), 'UTF-8')); ?>
            </span>
          </div>

          <div class="flex-grow-1">
            <div class="h6 fw-bold mb-0"><?php echo htmlspecialchars($fullName); ?></div>
            <div class="small text-secondary"><?php echo htmlspecialchars($email ?: '—'); ?></div>
          </div>

          <span class="badge <?php echo $isAdmin ? 'text-bg-danger' : 'text-bg-secondary'; ?> rounded-pill">
            <?php echo $isAdmin ? 'ADMIN' : 'USER'; ?>
          </span>
        </div>

        <hr class="my-3">

        <div class="row g-2 small">
          <div class="col-12">
            <div class="text-secondary">Потребителско име</div>
            <div class="fw-semibold"><?php echo htmlspecialchars($username); ?></div>
          </div>
          <div class="col-12 mt-2">
            <div class="text-secondary">Роля</div>
            <div class="fw-semibold"><?php echo htmlspecialchars($role); ?></div>
          </div>
        </div>

        <div class="d-grid mt-3">
          <a class="btn btn-outline-primary" href="<?php echo $base; ?>/public/edit_profile.php">
            Редакция на профил
          </a>
        </div>

      </div>
    </div>

    <div class="card shadow-sm mt-3">
      <div class="card-body">

        <h2 class="h6 fw-bold mb-3">Статистика</h2>

        <div class="d-flex justify-content-between">
          <span class="text-secondary small">Точки</span>
          <span class="fw-semibold"><?php echo (int)$points; ?></span>
        </div>

        <div class="d-flex justify-content-between mt-1">
          <span class="text-secondary small">Общо опити</span>
          <span class="fw-semibold"><?php echo (int)$attemptsTotal; ?></span>
        </div>

        <div class="d-flex justify-content-between mt-1">
          <span class="text-secondary small">Успешни</span>
          <span class="fw-semibold"><?php echo (int)$successTotal; ?></span>
        </div>

        <hr class="my-3">

        <div class="small text-secondary">
          Последен опит: <strong><?php echo $lastAttemptAt ? htmlspecialchars((string)$lastAttemptAt) : '—'; ?></strong><br>
          Последен успех: <strong><?php echo $lastSuccessAt ? htmlspecialchars((string)$lastSuccessAt) : '—'; ?></strong>
        </div>

      </div>
    </div>

    <div class="card shadow-sm mt-3">
      <div class="card-body">
        <h2 class="h6 fw-bold mb-3">Бързи действия</h2>

        <div class="d-grid gap-2">
          <?php if ($isAdmin): ?>
            <a class="btn btn-brand" href="<?php echo $base; ?>/public/admin/index.php">Към админ панела</a>
            <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/admin/users.php">Потребители</a>
            <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/admin/export.php">Експорт</a>
          <?php else: ?>
            <a class="btn btn-brand" href="<?php echo $base; ?>/public/dashboard.php">Към таблото</a>
            <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/ctf.php">CTF • Flags</a>
          <?php endif; ?>
          <a class="btn btn-outline-danger" href="<?php echo $base; ?>/public/logout.php">Изход</a>
        </div>
      </div>
    </div>

  </div>

  <div class="col-12 col-lg-8">

    <?php if ($isAdmin): ?>
      <div class="card shadow-sm">
        <div class="card-body">
          <h2 class="h5 fw-bold mb-1">Админ профил</h2>
          <p class="text-secondary mb-3">
            Като администратор нямаш уроци и упражнения. Тук виждаш профил и бързи линкове към статистики/експорт.
          </p>

          <div class="row g-3">
            <div class="col-12 col-md-4">
              <div class="p-3 rounded-4 border bg-light h-100">
                <div class="fw-semibold mb-1">Статистики</div>
                <div class="text-secondary small mb-2">Общ преглед на активност и трудност.</div>
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo $base; ?>/public/admin/index.php">Отвори</a>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="p-3 rounded-4 border bg-light h-100">
                <div class="fw-semibold mb-1">Потребители</div>
                <div class="text-secondary small mb-2">Прогрес и детайли по потребител.</div>
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo $base; ?>/public/admin/users.php">Отвори</a>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="p-3 rounded-4 border bg-light h-100">
                <div class="fw-semibold mb-1">Експорт</div>
                <div class="text-secondary small mb-2">CSV по период + отчети.</div>
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo $base; ?>/public/admin/export.php">Отвори</a>
              </div>
            </div>
          </div>

          <hr class="my-3">

          <div class="alert alert-info rounded-4 mb-0">
            <strong>Идея:</strong> после можем да добавим “Admin adjustments” за точки (ръчно +/–).
          </div>
        </div>
      </div>

    <?php else: ?>
      <div class="card shadow-sm">
        <div class="card-body">

          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h2 class="h5 fw-bold mb-1">Твоят прогрес</h2>
              <p class="text-secondary mb-0">
                Завършени основни модули: <strong><?php echo (int)$completedMain; ?></strong> / <?php echo (int)count($mainLabCodes); ?>
              </p>
            </div>
            <span class="badge text-bg-primary rounded-pill">
              <?php echo (int)$progressPct; ?>%
            </span>
          </div>

          <div class="progress mt-3" style="height: 14px;">
            <div class="progress-bar" style="width: <?php echo (int)$progressPct; ?>%"></div>
          </div>

          <div class="row g-3 mt-3">
            <div class="col-12 col-md-6">
              <div class="p-3 rounded-4 border bg-light h-100">
                <div class="fw-semibold mb-1">Точки (CTF-style)</div>
                <div class="text-secondary small">
                  Текущи точки: <strong><?php echo (int)$points; ?></strong><br>
                  Въвеждай флагове след решения.
                </div>
                <a class="btn btn-sm btn-outline-secondary mt-2" href="<?php echo $base; ?>/public/ctf.php">Отвори CTF</a>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <div class="p-3 rounded-4 border bg-light h-100">
                <div class="fw-semibold mb-1">Badges – coming soon</div>
                <div class="text-secondary small">
                  “First blood”, “No hints”, “Streak”, “Time-based master”.
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    <?php endif; ?>

  </div>

</div>

<?php bs_layout_end(); ?>
