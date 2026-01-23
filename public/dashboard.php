<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout_bs.php';
require_once __DIR__ . '/../includes/modules.php';

$base = base_url();
$userId = (int)($_SESSION['user_id'] ?? 0);

// Ако е админ – показваме кратък “admin view” (без уроци/упражнения)
$isAdmin = function_exists('is_admin') ? is_admin() : false;

// ---- Modules (use central list) ----
$modules = get_modules_ordered();

// Friendly titles for UI (you can tweak text freely)
$titles = [
  'LAB0_INTRO' => 'SQL Injection Basics',
  'LAB1_AUTH_BYPASS' => 'Authentication Bypass',
  'LAB2_BOOLEAN_BLIND' => 'Boolean-based Blind SQLi',
  'LAB3_UNION_BASED' => 'UNION-based SQLi',
  'LAB4_ERROR_BASED' => 'Error-based SQLi',
  'LAB5_TIME_BASED' => 'Time-based Blind SQLi',
];

$difficulty = [
  'LAB0_INTRO' => 'Въведение',
  'LAB1_AUTH_BYPASS' => 'Лесно',
  'LAB2_BOOLEAN_BLIND' => 'Средно',
  'LAB3_UNION_BASED' => 'Средно',
  'LAB4_ERROR_BASED' => 'Трудно',
  'LAB5_TIME_BASED' => 'Трудно',
];

$goals = [
  'LAB0_INTRO' => 'Започни с основите и отключи модулите.',
  'LAB1_AUTH_BYPASS' => 'Влез като админ чрез логически bypass.',
  'LAB2_BOOLEAN_BLIND' => 'Потвърди факт чрез true/false отговори.',
  'LAB3_UNION_BASED' => 'Извлечи данни чрез UNION заявки.',
  'LAB4_ERROR_BASED' => 'Използвай грешки за извличане на информация.',
  'LAB5_TIME_BASED' => 'Потвърди условие чрез време за отговор.',
];

// Build labs array with prereq chain (based on order)
$labs = [];
for ($i = 0; $i < count($modules); $i++) {
  $code = (string)($modules[$i]['code'] ?? '');
  $labs[] = [
    'code' => $code,
    'short' => (string)($modules[$i]['label'] ?? ('Модул ' . $i)),
    'title' => (string)($titles[$code] ?? $code),
    'path' => (string)($modules[$i]['path'] ?? ($base . '/public/index.php')),
    'type' => ($code === 'LAB0_INTRO') ? 'intro' : 'lab',
    'prereq' => ($i === 0) ? '' : (string)($modules[$i - 1]['code'] ?? ''),
  ];
}

// ---- Progress map ----
$progressMap = [];
$stmt = mysqli_prepare($conn, "SELECT lab_code, completed FROM user_progress WHERE user_id = ?");
if ($stmt) {
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  while ($row = mysqli_fetch_assoc($res)) {
    $progressMap[(string)$row['lab_code']] = $row;
  }
  mysqli_stmt_close($stmt);
}

// ---- Progress stats ----
$totalLabs = count($labs);
$completedCount = 0;
$nextLabPath = $labs[0]['path'] ?? ($base . '/public/index.php');

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

$percent = $totalLabs > 0 ? (int)round(($completedCount / $totalLabs) * 100) : 0;
$currentCode = null;
foreach ($labs as $lab) {
  $done = !empty($progressMap[$lab['code']]) &&
          (int)$progressMap[$lab['code']]['completed'] === 1;
  if (!$done) {
    $currentCode = $lab['code'];
    break;
  }
}

// ---- User aggregates (attempts) ----
$attemptsTotal = 0;
$successTotal = 0;
$lastAttemptAt = null;

$stmtA = mysqli_prepare($conn, "SELECT attempts_total, success_total, last_attempt_at FROM attempts_agg_user WHERE user_id = ? LIMIT 1");
if ($stmtA) {
  mysqli_stmt_bind_param($stmtA, 'i', $userId);
  mysqli_stmt_execute($stmtA);
  $ra = mysqli_stmt_get_result($stmtA);
  if ($ra && ($row = mysqli_fetch_assoc($ra))) {
    $attemptsTotal = (int)($row['attempts_total'] ?? 0);
    $successTotal = (int)($row['success_total'] ?? 0);
    $lastAttemptAt = $row['last_attempt_at'] ?? null;
  }
  mysqli_stmt_close($stmtA);
}

// ---- Last solved lab ----
$lastSolved = null;
$stmtLast = mysqli_prepare($conn, "
  SELECT lab_code, completed_at
  FROM user_progress
  WHERE user_id = ? AND completed = 1
  ORDER BY completed_at DESC
  LIMIT 1
");
if ($stmtLast) {
  mysqli_stmt_bind_param($stmtLast, 'i', $userId);
  mysqli_stmt_execute($stmtLast);
  $rs = mysqli_stmt_get_result($stmtLast);
  if ($rs && ($row = mysqli_fetch_assoc($rs))) {
    $lastSolved = $row;
  }
  mysqli_stmt_close($stmtLast);
}

// ---- Last points award ----
$lastReward = null;
$stmtReward = mysqli_prepare($conn, "
  SELECT delta, note, created_at
  FROM user_points_ledger
  WHERE user_id = ?
  ORDER BY created_at DESC
  LIMIT 1
");
if ($stmtReward) {
  mysqli_stmt_bind_param($stmtReward, 'i', $userId);
  mysqli_stmt_execute($stmtReward);
  $rr = mysqli_stmt_get_result($stmtReward);
  if ($rr && ($row = mysqli_fetch_assoc($rr))) {
    $lastReward = $row;
  }
  mysqli_stmt_close($stmtReward);
}
// ---- Per-lab aggregates (for a small “most practiced” insight) ----
$mostTriedLab = null;
$mostTriedCount = 0;

$stmtL = mysqli_prepare($conn, "
  SELECT lab, attempts_count
  FROM attempts_agg_user_lab
  WHERE user_id = ?
  ORDER BY attempts_count DESC
  LIMIT 1
");
if ($stmtL) {
  mysqli_stmt_bind_param($stmtL, 'i', $userId);
  mysqli_stmt_execute($stmtL);
  $rl = mysqli_stmt_get_result($stmtL);
  if ($rl && ($row = mysqli_fetch_assoc($rl))) {
    $mostTriedLab = (string)($row['lab'] ?? '');
    $mostTriedCount = (int)($row['attempts_count'] ?? 0);
  }
  mysqli_stmt_close($stmtL);
}

bs_layout_start('Табло');
?>

<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-warning">
    <?php echo htmlspecialchars($_SESSION['flash_error']); ?>
  </div>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<?php if ($isAdmin): ?>
  <div class="p-4 bg-white rounded-4 shadow-sm border mb-4">
    <h1 class="h3 fw-bold mb-2">Табло</h1>
    <p class="text-secondary mb-3">
      Влязъл си като <strong>админ</strong>. Админите не решават уроци/упражнения — те наблюдават прогреса на потребителите.
    </p>

    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-brand" href="<?php echo $base; ?>/public/admin/index.php">Към админ статистики</a>
      <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/admin/users.php">Потребители</a>
      <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/admin/export.php">Експорт</a>
      <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/profile.php">Моят профил</a>
    </div>
  </div>

<?php else: ?>

  <div class="p-4 bg-white rounded-4 shadow-sm border mb-4">
    <div class="d-flex align-items-start justify-content-between gap-3">
      <div>
        <h1 class="h3 fw-bold mb-2">Табло</h1>
        <p class="text-secondary mb-3">
          Всеки модул отключва следващия. Прогресът се записва автоматично.
        </p>
      </div>
      <div class="text-end">
        <div class="badge text-bg-primary rounded-pill px-3 py-2">
          <?php echo $completedCount; ?> / <?php echo $totalLabs; ?>
        </div>
        <div class="small text-secondary mt-1"><?php echo $percent; ?>% завършено</div>
      </div>
    </div>

    <?php if (empty($progressMap['LAB0_INTRO'])): ?>
      <div class="alert alert-warning rounded-4">
        👋 Започни с Intro, за да отключиш платформата.
      </div>
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-12">
        <div class="p-3 rounded-4 border bg-light h-100">
          <div class="d-flex justify-content-between mb-2">
            <span class="fw-semibold">Общ прогрес</span>
            <span class="text-secondary small"><?php echo $percent; ?>%</span>
          </div>
          <div class="progress mb-2" style="height:14px">
            <div class="progress-bar" style="width: <?php echo $percent; ?>%"></div>
          </div>
          <div class="text-secondary small">
            Последно решено:
            <strong>
              <?php echo $lastSolved ? htmlspecialchars($titles[$lastSolved['lab_code']] ?? (string)$lastSolved['lab_code']) : '—'; ?>
            </strong>
          </div>
          <div class="text-secondary small mt-1">
            Последна активност: <strong><?php echo $lastAttemptAt ? htmlspecialchars((string)$lastAttemptAt) : '—'; ?></strong>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
      <a class="btn btn-brand" href="<?php echo htmlspecialchars($nextLabPath); ?>">Продължи</a>
    </div>
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h5 fw-bold mb-0">Модули</h2>
        <span class="small text-secondary">Заключено докато не завършиш предишния модул</span>
      </div>

      <div class="row g-3">
        <?php foreach ($labs as $lab): ?>
          <?php
            $done = !empty($progressMap[$lab['code']]) &&
                    (int)$progressMap[$lab['code']]['completed'] === 1;

            $prereq = $lab['prereq'] ?? '';
            $locked = false;

            if ($prereq !== '') {
              $locked = empty($progressMap[$prereq]) ||
                        (int)($progressMap[$prereq]['completed'] ?? 0) !== 1;
            }

            $isCurrent = ($lab['code'] ?? '') === $currentCode;
            $status = $done ? 'Завършен' : ($locked ? 'Заключен' : 'В процес');
            $statusBadge = $done ? 'text-bg-success' : ($locked ? 'text-bg-danger' : 'text-bg-warning');
            $progressLab = $done ? 100 : ($locked ? 0 : 45);
          ?>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm module-card <?php echo $locked ? 'opacity-75' : ''; ?>">
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2 module-header">
                  <div>
                    <div class="text-secondary small"><?php echo htmlspecialchars($lab['short']); ?></div>
                    <div class="fw-semibold module-title"><?php echo htmlspecialchars($lab['title']); ?></div>
                  </div>
                  <span class="badge <?php echo $statusBadge; ?> rounded-pill"><?php echo $status; ?><?php echo $locked ? ' 🔒' : ''; ?></span>
                </div>

                <div class="text-secondary small">Упражнения: <?php echo $done ? '1/1' : '0/1'; ?></div>
                <div class="progress mt-2" style="height: 8px;">
                  <div class="progress-bar <?php echo $done ? 'bg-success' : ($isCurrent ? 'bg-warning' : ''); ?>" style="width: <?php echo (int)$progressLab; ?>%"></div>
                </div>

                <div class="mt-3 module-actions">
                  <?php if ($locked): ?>
                    <button class="btn btn-outline-secondary btn-compact" disabled>Заключено</button>
                  <?php else: ?>
                    <a class="btn <?php echo $done ? 'btn-success' : ($isCurrent ? 'btn-warning' : 'btn-brand'); ?> btn-compact" href="<?php echo htmlspecialchars($lab['path']); ?>">
                      <?php echo $done ? 'Преглед' : 'Започни'; ?>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Extra section at the end (so it doesn't feel empty) -->
  <div class="row g-3">
    <div class="col-12">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h3 class="h6 fw-bold mb-2">🧠 Съвети</h3>
          <ul class="text-secondary mb-0">
            <li>Прочети целта внимателно, преди да започнеш. Знай какво точно трябва да постигнеш.</li>
            <li>Помисли как изглежда SQL заявката зад формата – таблици, колони и условия.</li>
            <li>Реши упражнението повече от веднъж, използвайки различни подходи.</li>
            <li>Записвай си работещите заявки и причината да работят.</li>
            <li>Използвай грешките като насока, а не като пречка.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

<?php endif; ?>

<?php bs_layout_end(); ?>
