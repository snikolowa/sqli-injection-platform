<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/modules.php';
require_once __DIR__ . '/../includes/layout_bs.php';

$base = base_url();
$loggedIn = !empty($_SESSION['user_id']);

// ---------- Public counters (visible for guests too) ----------
$registeredUsers = 0;
$completedAllUsers = 0;

// Count non-admin users
$res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE COALESCE(role,'user') <> 'admin'");
if ($res && ($row = mysqli_fetch_assoc($res))) {
  $registeredUsers = (int)($row['c'] ?? 0);
}

// Count users that completed all main labs (LAB1..LAB5)
$mainLabCodes = [
  'LAB1_AUTH_BYPASS',
  'LAB2_BOOLEAN_BLIND',
  'LAB3_UNION_BASED',
  'LAB4_ERROR_BASED',
  'LAB5_TIME_BASED',
];

// Prepared statement with IN (...)
$placeholders = implode(',', array_fill(0, count($mainLabCodes), '?'));
$sqlCompleted = "
  SELECT COUNT(*) AS c
  FROM (
    SELECT user_id
    FROM user_progress
    WHERE completed = 1 AND lab_code IN ($placeholders)
    GROUP BY user_id
    HAVING COUNT(DISTINCT lab_code) = ?
  ) t
";

$stmt = mysqli_prepare($conn, $sqlCompleted);
if ($stmt) {
  // build dynamic bind params
  $types = str_repeat('s', count($mainLabCodes)) . 'i';
  $params = array_merge($mainLabCodes, [count($mainLabCodes)]);

  // mysqli bind_param needs references
  $bind = [];
  $bind[] = $types;
  foreach ($params as $k => $v) {
    $bind[] = &$params[$k];
  }
  call_user_func_array([$stmt, 'bind_param'], $bind);
  mysqli_stmt_execute($stmt);
  $r = mysqli_stmt_get_result($stmt);
  if ($r && ($row = mysqli_fetch_assoc($r))) {
    $completedAllUsers = (int)($row['c'] ?? 0);
  }
  mysqli_stmt_close($stmt);
}

// ---------- Logged-in home stats ----------
$userId = (int)($_SESSION['user_id'] ?? 0);
$username = (string)($_SESSION['username'] ?? '');

$userCompletedSet = [];
$userCompletedMain = 0;
$progressPct = 0;
$nextModule = null;

$attemptsTotal = 0;
$successTotal = 0;
$lastAttemptAt = null;

if ($loggedIn && $userId > 0) {
  $stmtP = mysqli_prepare($conn, "SELECT lab_code FROM user_progress WHERE user_id = ? AND completed = 1");
  if ($stmtP) {
    mysqli_stmt_bind_param($stmtP, 'i', $userId);
    mysqli_stmt_execute($stmtP);
    $rp = mysqli_stmt_get_result($stmtP);
    while ($rp && ($row = mysqli_fetch_assoc($rp))) {
      $code = (string)($row['lab_code'] ?? '');
      if ($code !== '') $userCompletedSet[$code] = true;
    }
    mysqli_stmt_close($stmtP);
  }

  foreach ($mainLabCodes as $c) {
    if (!empty($userCompletedSet[$c])) $userCompletedMain++;
  }

  $progressPct = (int)round(($userCompletedMain / max(1, count($mainLabCodes))) * 100);

  // next module = first not completed from ordered modules (skip intro)
  foreach (get_modules_ordered() as $m) {
    if (($m['code'] ?? '') === 'LAB0_INTRO') continue;
    $code = (string)($m['code'] ?? '');
    if ($code !== '' && empty($userCompletedSet[$code])) {
      $nextModule = $m;
      break;
    }
  }

  // attempts aggregates
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
}

bs_layout_start('SQLi Training Platform');
?>

<?php if (!$loggedIn): ?>
  <!-- Guest landing -->
  <section class="hero-sqli p-4 p-md-5 rounded-4 shadow-sm border overflow-hidden">
    <div class="row g-4 align-items-center position-relative">
      <div class="col-12 col-lg-7">
        <div class="d-inline-flex align-items-center gap-2 badge badge-brand px-3 py-2 rounded-pill mb-3">
          <span class="dot"></span>
          <span class="fw-semibold">Практика • Уроци • Прогрес</span>
        </div>

        <h1 class="display-6 fw-bold mb-3">SQLi Training Platform</h1>
        <p class="lead text-secondary mb-4">
          Уеб базирана платформа за обучение по <strong>SQL Injection</strong> с уроци, примери и
          умишлено уязвими упражнения в контролирана (локална) среда.
        </p>

        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-brand btn-lg" href="<?php echo $base; ?>/public/register.php">Регистрация</a>
          <a class="btn btn-outline-secondary btn-lg" href="<?php echo $base; ?>/public/login.php">Вход</a>
          <a class="btn btn-link text-decoration-none" href="<?php echo $base; ?>/public/labs.php">Виж модулите →</a>
        </div>

        <div class="row g-3 mt-4">
          <div class="col-12 col-sm-6">
            <div class="stat-card p-3 rounded-4 border bg-white shadow-sm h-100">
              <div class="text-secondary small">Регистрирани потребители</div>
              <div class="stat-number" data-count="<?php echo (int)$registeredUsers; ?>">0</div>
            </div>
          </div>
          <div class="col-12 col-sm-6">
            <div class="stat-card p-3 rounded-4 border bg-white shadow-sm h-100">
              <div class="text-secondary small">Завършили всички упражнения</div>
              <div class="stat-number" data-count="<?php echo (int)$completedAllUsers; ?>">0</div>
            </div>
          </div>
        </div>

        <div class="alert alert-warning mt-4 mb-0 rounded-4">
          <strong>Важно:</strong> Платформата е предназначена само за обучение и тестване в локална среда.
          Не използвай техники извън контролирана среда.
        </div>
      </div>

      <div class="col-12 col-lg-5">
        <div class="card shadow-sm border-0 rounded-4">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">Какво ще правиш вътре?</h2>
            <ul class="text-secondary mb-3">
              <li><strong>Урок</strong> → разбираш концепцията и типичните грешки.</li>
              <li><strong>Примери</strong> → виждаш реални payload-и в действие.</li>
              <li><strong>Упражнение</strong> → решаваш задача в умишлено уязвима среда.</li>
            </ul>
            <div class="p-3 rounded-4 bg-light border">
              <div class="fw-semibold mb-1">Проследяване на прогрес</div>
              <div class="text-secondary small">
                Прогресът се записва автоматично. Следващият модул се отключва след като предишният е Completed.
              </div>
            </div>

            <div class="mt-3 p-3 rounded-4 bg-light border">
              <div class="fw-semibold mb-1">Точкова система (в процес)</div>
              <div class="text-secondary small">
                Подготвяме CTF-style точки, бонуси и класация. Скоро.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="row g-3 mt-3">
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h3 class="h6 fw-bold mb-2">За кого е подходяща?</h3>
          <p class="text-secondary mb-0">
            За студенти, начинаещи devs и QA, които искат практическо разбиране на SQLi в безопасна среда.
          </p>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h3 class="h6 fw-bold mb-2">Какво покрива?</h3>
          <p class="text-secondary mb-0">
            Authentication bypass, Boolean-based Blind, UNION-based, Error-based и Time-based техники.
          </p>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h3 class="h6 fw-bold mb-2">Ревю на обучението</h3>
          <p class="text-secondary mb-0">
            Кратки обяснения, ясни цели и упражнения, които те водят стъпка по стъпка.
          </p>
        </div>
      </div>
    </div>
  </div>

<?php else: ?>
  <!-- Logged-in home -->
  <section class="p-4 p-md-5 bg-white rounded-4 shadow-sm border">
    <div class="row g-4 align-items-center">
      <div class="col-12 col-lg-8">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="badge text-bg-dark rounded-pill">Welcome back</span>
          <span class="text-secondary">@<?php echo htmlspecialchars($username); ?></span>
        </div>

        <h1 class="h3 fw-bold mb-2">Твоят прогрес</h1>
        <p class="text-secondary mb-4">
          Завършени упражнения: <strong><?php echo (int)$userCompletedMain; ?></strong> / <?php echo (int)count($mainLabCodes); ?>
        </p>

        <div class="progress rounded-pill" style="height: 12px;">
          <div class="progress-bar" role="progressbar" style="width: <?php echo (int)$progressPct; ?>%" aria-valuenow="<?php echo (int)$progressPct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="d-flex justify-content-between text-secondary small mt-2">
          <span><?php echo (int)$progressPct; ?>%</span>
          <span>Автоматично отключване на модули</span>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
          <?php if (!empty($nextModule['path'])): ?>
            <a class="btn btn-brand" href="<?php echo htmlspecialchars($nextModule['path']); ?>">Продължи: <?php echo htmlspecialchars($nextModule['label'] ?? 'Следващ модул'); ?></a>
          <?php else: ?>
            <a class="btn btn-brand" href="<?php echo $base; ?>/public/dashboard.php">Виж таблото</a>
          <?php endif; ?>
          <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/dashboard.php">Табло</a>
          <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/profile.php">Профил</a>
          <?php if (is_admin()): ?>
            <a class="btn btn-outline-danger" href="<?php echo $base; ?>/public/admin/index.php">Админ панел</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <h2 class="h6 fw-bold mb-3">Бърза статистика</h2>
            <div class="d-flex justify-content-between">
              <span class="text-secondary">Общо опити</span>
              <span class="fw-semibold"><?php echo (int)$attemptsTotal; ?></span>
            </div>
            <div class="d-flex justify-content-between mt-1">
              <span class="text-secondary">Успешни</span>
              <span class="fw-semibold"><?php echo (int)$successTotal; ?></span>
            </div>
            <hr>
            <div class="text-secondary small">
              Последна активност:
              <strong>
                <?php echo $lastAttemptAt ? htmlspecialchars($lastAttemptAt) : '—'; ?>
              </strong>
            </div>
            <div class="mt-3 p-3 rounded-4 bg-light border">
              <div class="fw-semibold mb-1">Точкова система (в процес)</div>
              <div class="text-secondary small">
                Скоро ще виждаш точки, бонуси и класация директно тук.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="row g-3 mt-3">
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h3 class="h6 fw-bold mb-2">Какво следва?</h3>
          <p class="text-secondary mb-0">
            Продължи към следващия незавършен модул или прегледай всички упражнения в таблото.
          </p>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h3 class="h6 fw-bold mb-2">Мотивация</h3>
          <p class="text-secondary mb-0">
            Малки стъпки, много практика. По-доброто разбиране идва от опитите.
          </p>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h3 class="h6 fw-bold mb-2">Безопасност</h3>
          <p class="text-secondary mb-0">
            Техниките са само за контролирана среда. Не ги използвай върху реални системи.
          </p>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<script>
// Simple counter animation for landing stats
(function(){
  const els = document.querySelectorAll('.stat-number[data-count]');
  if (!els.length) return;

  const fmt = new Intl.NumberFormat('bg-BG');
  els.forEach(el => {
    const target = parseInt(el.getAttribute('data-count') || '0', 10);
    const duration = 700;
    const start = performance.now();
    function tick(now){
      const p = Math.min(1, (now - start) / duration);
      const val = Math.floor(target * (0.15 + 0.85 * p));
      el.textContent = fmt.format(val);
      if (p < 1) requestAnimationFrame(tick);
      else el.textContent = fmt.format(target);
    }
    requestAnimationFrame(tick);
  });
})();
</script>

<?php bs_layout_end(); ?>
