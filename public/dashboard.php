<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout_bs.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

$labs = [
  [
    'code' => 'LAB1_AUTH_BYPASS',
    'short' => 'Lab 1',
    'title' => 'Authentication Bypass',
    'path' => '/sqli-platform/labs/lab1/step1.php'
  ],
  [
    'code' => 'LAB2_BOOLEAN_BLIND',
    'short' => 'Lab 2',
    'title' => 'Boolean-based Blind SQLi',
    'path' => '/sqli-platform/labs/lab2/step1.php'
  ],
  [
    'code' => 'LAB3_UNION_BASED',
    'short' => 'Lab 3',
    'title' => 'UNION-based SQLi',
    'path' => '/sqli-platform/labs/lab3/step1.php'
  ],
  [
    'code' => 'LAB4_ERROR_BASED',
    'short' => 'Lab 4',
    'title' => 'Error-based SQLi',
    'path' => '/sqli-platform/labs/lab4/step1.php'
  ],
  [
    'code' => 'LAB5_TIME_BASED',
    'short' => 'Lab 5',
    'title' => 'Time-based Blind SQLi',
    'path' => '/sqli-platform/labs/lab5/step1.php'
  ],
];

// progressMap
$progressMap = [];
$stmt = mysqli_prepare($conn, "SELECT lab_code, completed, completed_at FROM user_progress WHERE user_id = ?");
if ($stmt) {
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
      $progressMap[$row['lab_code']] = $row;
    }
  }
  mysqli_stmt_close($stmt);
}

$totalLabs = count($labs);
$completedCount = 0;
$nextLabPath = $labs[0]['path']; // default
foreach ($labs as $lab) {
  $done = (!empty($progressMap[$lab['code']]) && (int)$progressMap[$lab['code']]['completed'] === 1);
  if ($done) {
    $completedCount++;
  } else {
    // първият незавършен
    $nextLabPath = $lab['path'];
    break;
  }
}
$percent = $totalLabs > 0 ? (int)round(($completedCount / $totalLabs) * 100) : 0;

bs_layout_start('Dashboard');
?>

<div class="p-4 p-md-5 bg-white rounded-4 shadow-sm border">
  <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-start">
    <div>
      <h1 class="h3 fw-bold mb-2">Dashboard</h1>
      <p class="text-secondary mb-0">
        Избери урок или лаборатория. Прогресът се пази в профила ти.
      </p>

      <div class="mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-semibold">Твоят прогрес</span>
          <span class="badge text-bg-primary rounded-pill">
            <?php echo $completedCount; ?> / <?php echo $totalLabs; ?>
          </span>
        </div>

        <div class="progress" role="progressbar" aria-label="Progress" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100" style="height: 14px;">
          <div class="progress-bar" style="width: <?php echo $percent; ?>%"></div>
        </div>

        <div class="small text-secondary mt-2">
          <?php echo $percent; ?>% завършено
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
          <a class="btn btn-brand" href="<?php echo htmlspecialchars($nextLabPath); ?>">Продължи</a>
          <a class="btn btn-outline-secondary" href="/sqli-platform/public/profile.php">Виж профил</a>
        </div>
      </div>
    </div>

    <span class="badge badge-brand rounded-pill px-3 py-2">Training • Local</span>
  </div>
</div>

<div class="row g-3 mt-3">
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h2 class="h5 fw-bold mb-2">Урок 0</h2>
        <p class="text-secondary">
          Въведение в SQL Injection: как се случва, защо е опасно, основни типове и защита.
        </p>
        <a class="btn btn-brand" href="/sqli-platform/labs/lab0/intro.php">Отвори</a>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2 mb-2">
          <div>
            <h2 class="h5 fw-bold mb-1">Labs (по сложност)</h2>
            <p class="text-secondary mb-0">Статусът се обновява автоматично при успешно решаване.</p>
          </div>
        </div>

        <div class="list-group">
          <?php foreach ($labs as $lab): ?>
            <?php
              $done = (!empty($progressMap[$lab['code']]) && (int)$progressMap[$lab['code']]['completed'] === 1);
            ?>
            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
               href="<?php echo htmlspecialchars($lab['path']); ?>">
              <span>
                <strong><?php echo htmlspecialchars($lab['short']); ?>:</strong>
                <?php echo htmlspecialchars($lab['title']); ?>
              </span>

              <?php if ($done): ?>
                <span class="badge text-bg-success rounded-pill">Completed</span>
              <?php else: ?>
                <span class="badge text-bg-primary rounded-pill">Start</span>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<?php bs_layout_end(); ?>
