<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

require_once __DIR__ . '/../includes/layout_bs.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

// Дефинираме лабораториите (засега статично; после може да стане от DB)
$labs = [
  [
    'code' => 'LAB1_AUTH_BYPASS',
    'title' => 'Lab 1: Authentication Bypass (SQLi)',
    'path' => '/sqli-platform/labs/lab1/step1.php'
  ],
  [
    'code' => 'LAB2_BOOLEAN_BLIND',
    'title' => 'Lab 2: Boolean-based Blind SQL Injection',
    'path' => '/sqli-platform/labs/lab2/step1.php'
  ],
  [
    'code' => 'LAB3_UNION_BASED',
    'title' => 'Lab 3: UNION-based SQL Injection',
    'path' => '/sqli-platform/labs/lab3/step1.php'
  ],
  [
    'code' => 'LAB4_ERROR_BASED',
    'title' => 'Lab 4: Error-based SQL Injection',
    'path' => '/sqli-platform/labs/lab4/step1.php'
  ],
  [
    'code' => 'LAB5_TIME_BASED',
    'title' => 'Lab 5: Time-based Blind SQL Injection',
    'path' => '/sqli-platform/labs/lab5/step1.php'
  ],
];

// Взимаме прогреса за потребителя
$progressMap = []; // lab_code => ['completed'=>1, 'completed_at'=>...]
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

// Изчисляваме прогрес %
$totalLabs = count($labs);
$completedCount = 0;
foreach ($labs as $lab) {
    if (!empty($progressMap[$lab['code']]) && (int)$progressMap[$lab['code']]['completed'] === 1) {
        $completedCount++;
    }
}
$percent = $totalLabs > 0 ? (int)round(($completedCount / $totalLabs) * 100) : 0;

bs_layout_start('Профил');
?>

<div class="row g-3">
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="h4 fw-bold mb-2">Профил</h1>
        <p class="text-secondary mb-3">
          Потребител: <strong><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></strong>
        </p>

        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-semibold">Прогрес</span>
          <span class="badge text-bg-primary rounded-pill">
            <?php echo $completedCount; ?> / <?php echo $totalLabs; ?>
          </span>
        </div>

        <div class="progress" role="progressbar" aria-label="Progress" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100">
          <div class="progress-bar" style="width: <?php echo $percent; ?>%"><?php echo $percent; ?>%</div>
        </div>

        <div class="small text-secondary mt-2">
          Завършени лаборатории: <?php echo $completedCount; ?> от <?php echo $totalLabs; ?>.
        </div>
      </div>
    </div>

    <div class="card shadow-sm mt-3">
      <div class="card-body">
        <h2 class="h6 fw-bold mb-2">Бързи действия</h2>
        <div class="d-grid gap-2">
          <a class="btn btn-brand" href="/sqli-platform/public/dashboard.php">Към Dashboard</a>
          <a class="btn btn-outline-secondary" href="/sqli-platform/labs/lab0/intro.php">Урок 0</a>
          <a class="btn btn-outline-danger" href="/sqli-platform/public/logout.php">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2 mb-3">
          <div>
            <h2 class="h5 fw-bold mb-1">Лаборатории</h2>
            <p class="text-secondary mb-0">Статус и достъп до упражненията.</p>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Лаборатория</th>
                <th class="text-nowrap">Статус</th>
                <th class="text-nowrap">Дата</th>
                <th class="text-nowrap">Действие</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($labs as $lab): ?>
              <?php
                $isCompleted = (!empty($progressMap[$lab['code']]) && (int)$progressMap[$lab['code']]['completed'] === 1);
                $completedAt = $isCompleted ? ($progressMap[$lab['code']]['completed_at'] ?? '') : '';
              ?>
              <tr>
                <td class="fw-semibold"><?php echo htmlspecialchars($lab['title']); ?></td>
                <td>
                  <?php if ($isCompleted): ?>
                    <span class="badge text-bg-success">Completed</span>
                  <?php else: ?>
                    <span class="badge text-bg-secondary">Not completed</span>
                  <?php endif; ?>
                </td>
                <td class="text-secondary">
                  <?php echo $completedAt ? htmlspecialchars($completedAt) : "-"; ?>
                </td>
                <td>
                  <?php if ($lab['path'] !== '#'): ?>
                    <a class="btn btn-sm <?php echo $isCompleted ? 'btn-outline-primary' : 'btn-primary'; ?>"
                       href="<?php echo htmlspecialchars($lab['path']); ?>">
                      <?php echo $isCompleted ? "Повтори" : "Start"; ?>
                    </a>
                  <?php else: ?>
                    <span class="text-secondary">Предстои</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

<?php bs_layout_end(); ?>
