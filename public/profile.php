<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout_bs.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

/**
 * Модули (включително Модул 0)
 */
$modules = [
    ['code' => 'LAB0_INTRO', 'label' => 'Модул 0', 'title' => 'Въведение в SQL Injection'],
    ['code' => 'LAB1_AUTH_BYPASS', 'label' => 'Модул 1', 'title' => 'Authentication Bypass'],
    ['code' => 'LAB2_BOOLEAN_BLIND', 'label' => 'Модул 2', 'title' => 'Boolean-based Blind SQLi'],
    ['code' => 'LAB3_UNION_BASED', 'label' => 'Модул 3', 'title' => 'UNION-based SQLi'],
    ['code' => 'LAB4_ERROR_BASED', 'label' => 'Модул 4', 'title' => 'Error-based SQLi'],
    ['code' => 'LAB5_TIME_BASED', 'label' => 'Модул 5', 'title' => 'Time-based Blind SQLi'],
];

/**
 * Пътища към модулите
 */
$modulePaths = [
    'LAB0_INTRO' => '/sqli-platform/labs/lab0/intro.php',
    'LAB1_AUTH_BYPASS' => '/sqli-platform/labs/lab1/step1.php',
    'LAB2_BOOLEAN_BLIND' => '/sqli-platform/labs/lab2/step1.php',
    'LAB3_UNION_BASED' => '/sqli-platform/labs/lab3/step1.php',
    'LAB4_ERROR_BASED' => '/sqli-platform/labs/lab4/step1.php',
    'LAB5_TIME_BASED' => '/sqli-platform/labs/lab5/step1.php',
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

/**
 * Изчисляване на прогрес
 */
$total = count($modules);
$completedCount = 0;

foreach ($modules as $m) {
    if (!empty($progressMap[$m['code']]) &&
        (int)$progressMap[$m['code']]['completed'] === 1) {
        $completedCount++;
    }
}

$percent = $total > 0 ? (int)round(($completedCount / $total) * 100) : 0;

/**
 * Кои модули са достъпни (поредно отключване)
 */
$firstLockedFound = false;
$accessMap = [];

foreach ($modules as $m) {
    if (!$firstLockedFound) {
        $accessMap[$m['code']] = true;
        if (empty($progressMap[$m['code']]) ||
            (int)$progressMap[$m['code']]['completed'] !== 1) {
            $firstLockedFound = true;
        }
    } else {
        $accessMap[$m['code']] = false;
    }
}
$userRow = null;
$stmt = mysqli_prepare($conn, "SELECT username, first_name, last_name, email FROM users WHERE id = ?");
if ($stmt) {
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $userRow = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);
}

bs_layout_start('Профил');
?>

<div class="row g-3">

  <!-- ЛЯВА КОЛОНА -->
  <div class="col-12 col-lg-4">

    <!-- Профил -->
    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="h5 fw-bold mb-2">Потребителски профил</h1>
          <div class="mt-3 small">
            <div class="mb-2">
            <div class="text-secondary">Потребителско име</div>
            <div class="fw-semibold"><?php echo htmlspecialchars($userRow['username'] ?? ''); ?></div>
          </div>
          <div class="mb-2">
            <div class="text-secondary">Имейл</div>
            <div class="fw-semibold"><?php echo htmlspecialchars($userRow['email'] ?? ''); ?></div>
          </div>
            <div class="mb-2">
              <div class="text-secondary">Име и фамилия</div>
              <div class="fw-semibold">
                <?php echo htmlspecialchars(trim(($userRow['first_name'] ?? '') . ' ' . ($userRow['last_name'] ?? ''))); ?>
            </div>
          </div>

          <a class="btn btn-outline-primary btn-sm mt-2" href="/sqli-platform/public/edit_profile.php">
            Редактирай профил
          </a>
        </div>

        <p class="text-secondary mb-3">
          Преглед на напредъка ти в курса.
        </p>

        <!-- Прогрес -->
        <div class="mb-2 d-flex justify-content-between align-items-center">
          <span class="fw-semibold">Общ напредък</span>
          <span class="badge text-bg-primary rounded-pill">
            <?php echo $completedCount; ?> / <?php echo $total; ?>
          </span>
        </div>

        <div class="progress" style="height: 14px;">
          <div class="progress-bar" style="width: <?php echo $percent; ?>%"></div>
        </div>

        <div class="small text-secondary mt-2">
          <?php echo $percent; ?>% завършено
        </div>
      </div>
    </div>

    <!-- Бързи действия -->
    <div class="card shadow-sm mt-3">
      <div class="card-body">
        <h2 class="h6 fw-bold mb-3">Бързи действия</h2>

        <div class="d-grid gap-2">
          <a class="btn btn-brand" href="/sqli-platform/public/dashboard.php">
            Към таблото
          </a>

          <a class="btn btn-outline-danger" href="/sqli-platform/public/logout.php">
            Изход
          </a>
        </div>
      </div>
    </div>

  </div>

  <!-- ДЯСНА КОЛОНА -->
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5 fw-bold mb-3">Напредък по модули</h2>

        <div class="list-group">

          <?php foreach ($modules as $m): ?>
            <?php
              $done = !empty($progressMap[$m['code']]) &&
                      (int)$progressMap[$m['code']]['completed'] === 1;

              $allowed = $accessMap[$m['code']];
              $path = $modulePaths[$m['code']] ?? '#';

              $badge = $done
                ? '<span class="badge text-bg-success rounded-pill">Завършен</span>'
                : ($allowed
                    ? '<span class="badge text-bg-primary rounded-pill">Достъпен</span>'
                    : '<span class="badge text-bg-secondary rounded-pill">Заключен</span>');
            ?>

            <?php if ($allowed): ?>
              <a href="<?php echo htmlspecialchars($path); ?>"
                 class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <span>
                  <strong><?php echo htmlspecialchars($m['label']); ?>:</strong>
                  <?php echo htmlspecialchars($m['title']); ?>
                </span>
                <?php echo $badge; ?>
              </a>
            <?php else: ?>
              <div class="list-group-item d-flex justify-content-between align-items-center text-muted">
                <span>
                  <strong><?php echo htmlspecialchars($m['label']); ?>:</strong>
                  <?php echo htmlspecialchars($m['title']); ?>
                </span>
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
