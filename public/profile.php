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
$error = $_GET['error'] ?? '';
$ok = $_GET['ok'] ?? '';

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

$labTitles = [
  'LAB0_INTRO' => 'SQL Injection Basics',
  'LAB1_AUTH_BYPASS' => 'Authentication Bypass',
  'LAB2_BOOLEAN_BLIND' => 'Boolean-based Blind SQLi',
  'LAB3_UNION_BASED' => 'UNION-based SQLi',
  'LAB4_ERROR_BASED' => 'Error-based SQLi',
  'LAB5_TIME_BASED' => 'Time-based Blind SQLi',
];

// --- Progress (only meaningful for normal users) ---
$mainLabCodes = [
  'LAB0_INTRO',
  'LAB1_AUTH_BYPASS',
  'LAB2_BOOLEAN_BLIND',
  'LAB3_UNION_BASED',
  'LAB4_ERROR_BASED',
  'LAB5_TIME_BASED',
];

$completedMain = 0;
$progressPct = 0;
$progressMap = [];
$rewardsMap = [];
$attemptsLabMap = [];

if (!$isAdmin) {
  $stmtProg = mysqli_prepare($conn, "SELECT lab_code, completed, completed_at FROM user_progress WHERE user_id = ?");
  if ($stmtProg) {
    mysqli_stmt_bind_param($stmtProg, 'i', $userId);
    mysqli_stmt_execute($stmtProg);
    $rp = mysqli_stmt_get_result($stmtProg);
    while ($rp && ($row = mysqli_fetch_assoc($rp))) {
      $progressMap[(string)$row['lab_code']] = $row;
    }
    mysqli_stmt_close($stmtProg);
  }

  $stmtAttempts = mysqli_prepare($conn, "SELECT lab, attempts_count FROM attempts_agg_user_lab WHERE user_id = ?");
  if ($stmtAttempts) {
    mysqli_stmt_bind_param($stmtAttempts, 'i', $userId);
    mysqli_stmt_execute($stmtAttempts);
    $ra = mysqli_stmt_get_result($stmtAttempts);
    while ($ra && ($row = mysqli_fetch_assoc($ra))) {
      $attemptsLabMap[(string)$row['lab']] = (int)($row['attempts_count'] ?? 0);
    }
    mysqli_stmt_close($stmtAttempts);
  }

  $stmtRewards = mysqli_prepare($conn, "SELECT lab_code, points_awarded FROM user_lab_rewards WHERE user_id = ?");
  if ($stmtRewards) {
    mysqli_stmt_bind_param($stmtRewards, 'i', $userId);
    mysqli_stmt_execute($stmtRewards);
    $rr = mysqli_stmt_get_result($stmtRewards);
    while ($rr && ($row = mysqli_fetch_assoc($rr))) {
      $rewardsMap[(string)$row['lab_code']] = (int)($row['points_awarded'] ?? 0);
    }
    mysqli_stmt_close($stmtRewards);
  }

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

// --- Points (AUTO) ---
$points = points_get_user_points($conn, $userId);

bs_layout_start('Профил');
?>

<?php if ($error): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($ok): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($ok); ?></div>
<?php endif; ?>

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
            <div class="d-grid gap-2 mt-3">
          <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#editProfileModal">
            Редакция на профил
          </button>
          <a class="btn btn-outline-danger" href="<?php echo $base; ?>/public/logout.php">Изход</a>
        </div>
          </div>
        </div>

      </div>
    </div>

    <div class="card shadow-sm mt-3">
      <div class="card-body">
        <h2 class="h6 fw-bold mb-3">Значки</h2>
        <div class="d-flex flex-wrap gap-2">
          <?php
            $lab1Attempts = (int)($attemptsLabMap['lab1_practice'] ?? 0);
            $lab3Attempts = (int)($attemptsLabMap['lab3_practice'] ?? 0);
            $lab5Attempts = (int)($attemptsLabMap['lab5_practice'] ?? 0);
            $badge1 = !empty($progressMap['LAB1_AUTH_BYPASS']) && $lab1Attempts > 0 && $lab1Attempts <= 3;
            $badge2 = !empty($progressMap['LAB3_UNION_BASED']) && $lab3Attempts > 0 && $lab3Attempts <= 3;
            $badge3 = !empty($progressMap['LAB5_TIME_BASED']) && $lab5Attempts > 0 && $lab5Attempts <= 3;
          ?>
          <?php if ($badge1): ?>
            <span class="badge text-bg-success rounded-pill"
                  data-bs-toggle="tooltip"
                  title="LAB1: завършен с максимум 3 опита.">Бърз старт</span>
          <?php endif; ?>
          <?php if ($badge2): ?>
            <span class="badge text-bg-info rounded-pill"
                  data-bs-toggle="tooltip"
                  title="LAB3: завършен с максимум 3 опита.">UNION експерт</span>
          <?php endif; ?>
          <?php if ($badge3): ?>
            <span class="badge text-bg-warning rounded-pill"
                  data-bs-toggle="tooltip"
                  title="LAB5: завършен с максимум 3 опита.">Blind нинджа</span>
          <?php endif; ?>
          <?php if (!$badge1 && !$badge2 && !$badge3): ?>
            <span class="text-secondary small">Още няма спечелени значки.</span>
          <?php endif; ?>
        </div>
        <div class="text-secondary small mt-2">Максимум значки: 3</div>
      </div>
    </div>

  </div>

  <div class="col-12 col-lg-8">

    <?php if ($isAdmin): ?>
      <div class="card shadow-sm">
        <div class="card-body">
          <h2 class="h5 fw-bold mb-1">Админ профил</h2>
          <p class="text-secondary mb-0">
            Админите не решават упражнения. Тук е само профил + линкове към админ панела.
          </p>
        </div>
      </div>

    <?php else: ?>
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h2 class="h6 fw-bold mb-1">Прогрес и точки</h2>
              <div class="text-secondary small">Точки и статус по модули</div>
            </div>
            <span class="badge text-bg-primary rounded-pill">
              <?php echo (int)$progressPct; ?>%
            </span>
          </div>

          <div class="mb-3">
            <div class="text-secondary small">
              Завършени основни модули: <strong><?php echo (int)$completedMain; ?></strong> / <?php echo (int)count($mainLabCodes); ?>
            </div>
            <div class="progress mt-2" style="height: 12px;">
              <div class="progress-bar" style="width: <?php echo (int)$progressPct; ?>%"></div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr class="text-secondary small">
                  <th>Упражнение</th>
                  <th>Статус</th>
                  <th class="text-center">Опити</th>
                  <th class="text-center">Точки</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $modules = get_modules_ordered();
                  $prevDone = true;
                ?>
                <?php foreach ($modules as $m): ?>
                  <?php
                    $code = (string)($m['code'] ?? '');
                    $done = !empty($progressMap[$code]) && (int)($progressMap[$code]['completed'] ?? 0) === 1;
                    $locked = !$prevDone && !$done;
                    $pointsRow = ($code === 'LAB0_INTRO') ? 0 : (int)($rewardsMap[$code] ?? 0);
                    $statusLabel = $done ? 'Завършен' : ($locked ? 'Заключен' : 'В процес');
                    $statusClass = $done ? 'text-bg-success' : ($locked ? 'text-bg-danger' : 'text-bg-warning');
                    $attemptKey = function_exists('points_attempt_lab_key') ? points_attempt_lab_key($code) : null;
                    $attemptsRow = $attemptKey ? (int)($attemptsLabMap[$attemptKey] ?? 0) : 0;
                    if ($code === 'LAB0_INTRO') $attemptsRow = 1;
                    $prevDone = $done;
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($labTitles[$code] ?? ($m['label'] ?? $code)); ?></td>
                    <td><span class="badge <?php echo $statusClass; ?> rounded-pill"><?php echo $statusLabel; ?></span></td>
                    <td class="text-center"><?php echo (int)$attemptsRow; ?></td>
                    <td class="text-center"><?php echo (int)$pointsRow; ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-secondary small">Общо точки</span>
            <span class="fw-semibold"><?php echo (int)$points; ?></span>
          </div>

          <div class="mt-3 p-3 rounded-4 border bg-light">
            <div class="fw-semibold mb-1">Как се начисляват точките?</div>
            <div class="text-secondary small">
              Точки се дават автоматично при <strong>завършен модул</strong>. Има penalty при много опити:
              <strong>-2</strong> за всеки допълнителен опит (минимум 30% от базовите точки) и
              <strong>+10 бонус</strong> при успех от първия опит.
            </div>
          </div>
        </div>
      </div>

    <?php endif; ?>

  </div>

</div>

<?php bs_layout_end(); ?>

<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5 fw-bold" id="editProfileLabel">Редакция на профил</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Затвори"></button>
      </div>
      <div class="modal-body">
        <p class="text-secondary small mb-3">
          Потребителското име и имейлът не могат да се променят.
        </p>
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
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Отказ</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  (function(){
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (!tooltips.length || typeof bootstrap === 'undefined') return;
    tooltips.forEach((el) => new bootstrap.Tooltip(el));
  })();
</script>
