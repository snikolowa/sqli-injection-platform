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
  'LAB0_INTRO' => 'Въведение в SQL Injection',
  'LAB1_AUTH_BYPASS' => 'Authentication Bypass',
  'LAB2_BOOLEAN_BLIND' => 'Boolean-based Blind SQLi',
  'LAB3_UNION_BASED' => 'UNION-based SQLi',
  'LAB4_ERROR_BASED' => 'Error-based SQLi',
  'LAB5_TIME_BASED' => 'Time-based Blind SQLi',
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

    <div class="mb-2 d-flex justify-content-between">
      <span class="fw-semibold">Прогрес</span>
      <span class="text-secondary small">Следващ модул: <strong>Continue</strong></span>
    </div>

    <div class="progress mb-2" style="height:14px">
      <div class="progress-bar" style="width: <?php echo $percent; ?>%"></div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-3">
      <a class="btn btn-brand" href="<?php echo htmlspecialchars($nextLabPath); ?>">Продължи</a>
      <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/profile.php">Профил</a>
      <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/index.php">Начало</a>
    </div>

    <div class="row g-3 mt-3">
      <div class="col-12 col-lg-4">
        <div class="p-3 rounded-4 border bg-light h-100">
          <div class="text-secondary small">Общо опити</div>
          <div class="h4 fw-bold mb-0"><?php echo (int)$attemptsTotal; ?></div>
          <div class="text-secondary small mt-1">Успешни: <strong><?php echo (int)$successTotal; ?></strong></div>
        </div>
      </div>
      <div class="col-12 col-lg-4">
        <div class="p-3 rounded-4 border bg-light h-100">
          <div class="text-secondary small">Последна активност</div>
          <div class="fw-semibold"><?php echo $lastAttemptAt ? htmlspecialchars((string)$lastAttemptAt) : '—'; ?></div>
          <div class="text-secondary small mt-1">От агрегати</div>
        </div>
      </div>
      <div class="col-12 col-lg-4">
        <div class="p-3 rounded-4 border bg-light h-100">
          <div class="text-secondary small">Най-практикувано упражнение</div>
          <div class="fw-semibold"><?php echo $mostTriedLab ? htmlspecialchars($mostTriedLab) : '—'; ?></div>
          <div class="text-secondary small mt-1">
            <?php echo $mostTriedLab ? ('Опити: <strong>' . (int)$mostTriedCount . '</strong>') : 'Няма данни още'; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h5 fw-bold mb-0">Модули</h2>
        <span class="small text-secondary">Locked докато не завършиш prerequisite</span>
      </div>

      <div class="list-group">
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

            $type = $lab['type'] ?? 'lab';
          ?>

          <?php if ($locked): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center opacity-75">
              <span>
                <strong><?php echo htmlspecialchars($lab['short']); ?>:</strong>
                <?php echo htmlspecialchars($lab['title']); ?>
              </span>
              <span class="badge text-bg-secondary rounded-pill">Locked 🔒</span>
            </div>
          <?php else: ?>
            <a href="<?php echo htmlspecialchars($lab['path']); ?>"
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
              <span>
                <strong><?php echo htmlspecialchars($lab['short']); ?>:</strong>
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

  <!-- Extra section at the end (so it doesn't feel empty) -->
  <div class="row g-3">
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h3 class="h6 fw-bold mb-2">Как да напредваш по-бързо</h3>
          <ul class="text-secondary mb-0">
            <li>Първо прочети Step 1 (обясненията).</li>
            <li>След това повтори в Practice, докато стане естествено.</li>
            <li>Пиши си “payload notes” — най-работещото за памет.</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h3 class="h6 fw-bold mb-2">Точкова система (CTF-style) – скоро</h3>
          <p class="text-secondary mb-2">
            Ще има точки по упражнение + бонуси/наказания (опити, hints) и класация.
          </p>
          <div class="p-3 rounded-4 border bg-light">
            <div class="small text-secondary">Идеи за badges:</div>
            <div class="small">
              <span class="badge text-bg-secondary rounded-pill">First blood</span>
              <span class="badge text-bg-secondary rounded-pill">No hints</span>
              <span class="badge text-bg-secondary rounded-pill">3 wins streak</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h3 class="h6 fw-bold mb-2">Безопасност</h3>
          <p class="text-secondary mb-0">
            Тези техники са само за контролирана среда. Не ги използвай върху реални системи без разрешение.
          </p>
        </div>
      </div>
    </div>
  </div>

<?php endif; ?>

<?php bs_layout_end(); ?>
