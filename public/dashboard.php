<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout_bs.php';

$userId = (int)($_SESSION['user_id'] ?? 0);


$labs = [
    [
        'code' => 'LAB0_INTRO',
        'short' => 'Модул 0',
        'title' => 'Въведение в SQL Injection',
        'path' => '/sqli-platform/labs/lab0/intro.php',
        'type' => 'intro',
        'prereq' => ''
    ],
    [
        'code' => 'LAB1_AUTH_BYPASS',
        'short' => 'Модул 1',
        'title' => 'Authentication Bypass',
        'path' => '/sqli-platform/labs/lab1/step1.php',
        'prereq' => 'LAB0_INTRO'
    ],
    [
        'code' => 'LAB2_BOOLEAN_BLIND',
        'short' => 'Модул 2',
        'title' => 'Boolean-based Blind SQLi',
        'path' => '/sqli-platform/labs/lab2/step1.php',
        'prereq' => 'LAB1_AUTH_BYPASS'
    ],
    [
        'code' => 'LAB3_UNION_BASED',
        'short' => 'Модул 3',
        'title' => 'UNION-based SQLi',
        'path' => '/sqli-platform/labs/lab3/step1.php',
        'prereq' => 'LAB2_BOOLEAN_BLIND'
    ],
    [
        'code' => 'LAB4_ERROR_BASED',
        'short' => 'Модул 4',
        'title' => 'Error-based SQLi',
        'path' => '/sqli-platform/labs/lab4/step1.php',
        'prereq' => 'LAB3_UNION_BASED'
    ],
    [
        'code' => 'LAB5_TIME_BASED',
        'short' => 'Модул 5',
        'title' => 'Time-based Blind SQLi',
        'path' => '/sqli-platform/labs/lab5/step1.php',
        'prereq' => 'LAB4_ERROR_BASED'
    ],
];

/**
 * Progress map
 */
$progressMap = [];
$stmt = mysqli_prepare(
    $conn,
    "SELECT lab_code, completed FROM user_progress WHERE user_id = ?"
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
 * Progress stats
 */
$totalLabs = count($labs);
$completedCount = 0;
$nextLabPath = $labs[0]['path'];

foreach ($labs as $lab) {
    $done = !empty($progressMap[$lab['code']]) &&
            (int)$progressMap[$lab['code']]['completed'] === 1;

    if ($done) {
        $completedCount++;
    } else {
        $nextLabPath = $lab['path'];
        break;
    }
}

$percent = $totalLabs > 0
    ? (int)round(($completedCount / $totalLabs) * 100)
    : 0;

bs_layout_start('Dashboard');
?>

<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-warning">
    <?php echo htmlspecialchars($_SESSION['flash_error']); ?>
  </div>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="p-4 bg-white rounded-4 shadow-sm border mb-4">
  <h1 class="h3 fw-bold mb-2">Dashboard</h1>
  <p class="text-secondary mb-3">
    Това е курс по SQL Injection. Всеки урок отключва следващия.
  </p>

  <div class="mb-2 d-flex justify-content-between">
    <span class="fw-semibold">Прогрес</span>
    <span class="badge text-bg-primary rounded-pill">
      <?php echo $completedCount; ?> / <?php echo $totalLabs; ?>
    </span>
  </div>

  <div class="progress mb-2" style="height:14px">
    <div class="progress-bar" style="width: <?php echo $percent; ?>%"></div>
  </div>

  <div class="small text-secondary mb-3">
    <?php echo $percent; ?>% завършено
  </div>

  <a class="btn btn-brand" href="<?php echo htmlspecialchars($nextLabPath); ?>">
    Продължи
  </a>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <h2 class="h5 fw-bold mb-3">Уроци</h2>

    <div class="list-group">
      <?php foreach ($labs as $lab): ?>
        <?php
          $done = !empty($progressMap[$lab['code']]) &&
                  (int)$progressMap[$lab['code']]['completed'] === 1;

          $prereq = $lab['prereq'] ?? '';
          $locked = false;

          if ($prereq !== '') {
              $locked = empty($progressMap[$prereq]) ||
                        (int)$progressMap[$prereq]['completed'] !== 1;
          }

          $type = $lab['type'] ?? 'lab';
        ?>

        <?php if ($locked): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center opacity-75">
            <span>
              <strong><?php echo $lab['short']; ?>:</strong>
              <?php echo htmlspecialchars($lab['title']); ?>
            </span>
            <span class="badge text-bg-secondary rounded-pill">Locked 🔒</span>
          </div>
        <?php else: ?>
          <a href="<?php echo htmlspecialchars($lab['path']); ?>"
             class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            <span>
              <strong><?php echo $lab['short']; ?>:</strong>
              <?php echo htmlspecialchars($lab['title']); ?>
            </span>

            <?php if ($done): ?>
              <span class="badge text-bg-success rounded-pill">Завършен</span>
            <?php else: ?>
              <?php if ($type === 'intro'): ?>
                <span class="badge text-bg-secondary rounded-pill">Прочети</span>
              <?php else: ?>
                <span class="badge text-bg-primary rounded-pill">Започни</span>
              <?php endif; ?>
            <?php endif; ?>
          </a>
        <?php endif; ?>

      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php bs_layout_end(); ?>
