<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout_bs.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

$modules = [
  ['code' => 'LAB0_INTRO',         'label' => 'Модул 0', 'title' => 'Въведение в SQL Injection'],
  ['code' => 'LAB1_AUTH_BYPASS',   'label' => 'Модул 1', 'title' => 'Authentication Bypass'],
  ['code' => 'LAB2_BOOLEAN_BLIND', 'label' => 'Модул 2', 'title' => 'Boolean-based Blind SQLi'],
  ['code' => 'LAB3_UNION_BASED',   'label' => 'Модул 3', 'title' => 'UNION-based SQLi'],
  ['code' => 'LAB4_ERROR_BASED',   'label' => 'Модул 4', 'title' => 'Error-based SQLi'],
  ['code' => 'LAB5_TIME_BASED',    'label' => 'Модул 5', 'title' => 'Time-based Blind SQLi'],
];

$modulePaths = [
  'LAB0_INTRO'         => '/sqli-platform/labs/lab0/intro.php',
  'LAB1_AUTH_BYPASS'   => '/sqli-platform/labs/lab1/step1.php',
  'LAB2_BOOLEAN_BLIND' => '/sqli-platform/labs/lab2/step1.php',
  'LAB3_UNION_BASED'   => '/sqli-platform/labs/lab3/step1.php',
  'LAB4_ERROR_BASED'   => '/sqli-platform/labs/lab4/step1.php',
  'LAB5_TIME_BASED'    => '/sqli-platform/labs/lab5/step1.php',
];

/**
 * Progress map
 */
$progressMap = [];
$stmt = mysqli_prepare(
  $conn,
  "SELECT lab_code, completed, completed_at
   FROM user_progress
   WHERE user_id = ?"
);
if ($stmt) {
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  while ($row = mysqli_fetch_assoc($res)) {
    $progressMap[$row['lab_code']] = $row;
  }
  mysqli_stmt_close($stmt);
}

$firstLockedFound = false;
$accessMap = [];
foreach ($modules as $m) {
  if (!$firstLockedFound) {
    $accessMap[$m['code']] = true;
    if (empty($progressMap[$m['code']]) || (int)$progressMap[$m['code']]['completed'] !== 1) {
      $firstLockedFound = true;
    }
  } else {
    $accessMap[$m['code']] = false;
  }
}

/**
 * User data
 */
$userRow = null;
$stmt = mysqli_prepare($conn, "SELECT username, first_name, last_name, email FROM users WHERE id = ?");
if ($stmt) {
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $userRow = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);
}

$username = $userRow['username'] ?? '';
$email = $userRow['email'] ?? '';
$fullName = trim(($userRow['first_name'] ?? '') . ' ' . ($userRow['last_name'] ?? ''));
if ($fullName === '') $fullName = '—';

/**
 * Progress
 */
$total = count($modules);
$completedCount = 0;
foreach ($modules as $m) {
  if (!empty($progressMap[$m['code']]) && (int)$progressMap[$m['code']]['completed'] === 1) {
    $completedCount++;
  }
}
$percent = $total > 0 ? (int)round(($completedCount / $total) * 100) : 0;

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
            <div class="small text-secondary"><?php echo htmlspecialchars($email); ?></div>
          </div>
        </div>

        <hr class="my-3">

        <div class="row g-2 small">
          <div class="col-12">
            <div class="text-secondary">Потребителско име</div>
            <div class="fw-semibold"><?php echo htmlspecialchars($username); ?></div>
          </div>
        </div>

        <div class="d-grid mt-3">
          <a class="btn btn-outline-primary" href="/sqli-platform/public/edit_profile.php">
            Редакция на профил
          </a>
        </div>

      </div>
    </div>

    <div class="card shadow-sm mt-3">
      <div class="card-body">

        <div class="d-flex justify-content-between align-items-start">
          <div>
            <h2 class="h6 fw-bold mb-1">Напредък</h2>
            <p class="small text-secondary mb-0">Общо завършени модули</p>
          </div>
          <span class="badge text-bg-primary rounded-pill">
            <?php echo $completedCount; ?> / <?php echo $total; ?>
          </span>
        </div>

        <div class="progress mt-3" style="height: 14px;">
          <div class="progress-bar" style="width: <?php echo $percent; ?>%"></div>
        </div>

        <div class="d-flex justify-content-between small text-secondary mt-2">
          <span><?php echo $percent; ?>% завършено</span>
          <span><?php echo ($percent === 100) ? '✅ Готово' : '⏳ В процес'; ?></span>
        </div>

      </div>
    </div>

    <div class="card shadow-sm mt-3">
      <div class="card-body">
        <h2 class="h6 fw-bold mb-3">Бързи действия</h2>

        <div class="d-grid gap-2">
          <a class="btn btn-brand" href="/sqli-platform/public/dashboard.php">Към таблото</a>
          <a class="btn btn-outline-danger" href="/sqli-platform/public/logout.php">Изход</a>
        </div>
      </div>
    </div>

  </div>

  <div class="col-12 col-lg-8">

    <div class="card shadow-sm">
      <div class="card-body">

        <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
          <div>
            <h2 class="h5 fw-bold mb-1">Модули</h2>
            <p class="text-secondary mb-0">
              Модулите се отключват последователно. Кликни върху достъпните.
            </p>
          </div>
        </div>

        <div class="list-group mt-3">

          <?php foreach ($modules as $m): ?>
            <?php
              $done = !empty($progressMap[$m['code']]) && (int)$progressMap[$m['code']]['completed'] === 1;
              $allowed = $accessMap[$m['code']];
              $path = $modulePaths[$m['code']] ?? '#';

              if ($done) {
                $badge = '<span class="badge text-bg-success rounded-pill">Завършен</span>';
                $icon = '✅';
              } elseif ($allowed) {
                $badge = '<span class="badge text-bg-primary rounded-pill">Достъпен</span>';
                $icon = '▶️';
              } else {
                $badge = '<span class="badge text-bg-secondary rounded-pill">Заключен</span>';
                $icon = '🔒';
              }
            ?>

            <?php if ($allowed): ?>
              <a href="<?php echo htmlspecialchars($path); ?>"
                 class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                  <span><?php echo $icon; ?></span>
                  <span>
                    <strong><?php echo htmlspecialchars($m['label']); ?>:</strong>
                    <?php echo htmlspecialchars($m['title']); ?>
                  </span>
                </div>
                <?php echo $badge; ?>
              </a>
            <?php else: ?>
              <div class="list-group-item d-flex justify-content-between align-items-center text-muted">
                <div class="d-flex align-items-center gap-2">
                  <span><?php echo $icon; ?></span>
                  <span>
                    <strong><?php echo htmlspecialchars($m['label']); ?>:</strong>
                    <?php echo htmlspecialchars($m['title']); ?>
                  </span>
                </div>
                <?php echo $badge; ?>
              </div>
            <?php endif; ?>

          <?php endforeach; ?>

        </div>

      </div>
    </div>

  </div>

</div>

<?php bs_layout_end(); ?>
