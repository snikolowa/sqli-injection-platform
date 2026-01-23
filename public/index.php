<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/modules.php';
require_once __DIR__ . '/../includes/layout_bs.php';

$base = base_url();
$loggedIn = !empty($_SESSION['user_id']);
$isAdmin = function_exists('is_admin') ? is_admin() : false;

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

$labTitles = [
  'LAB0_INTRO' => 'SQL Injection Basics',
  'LAB1_AUTH_BYPASS' => 'Authentication Bypass',
  'LAB2_BOOLEAN_BLIND' => 'Boolean-based Blind SQLi',
  'LAB3_UNION_BASED' => 'UNION-based SQLi',
  'LAB4_ERROR_BASED' => 'Error-based SQLi',
  'LAB5_TIME_BASED' => 'Time-based Blind SQLi',
];

$labShort = [
  'LAB0_INTRO' => 'Въведение',
  'LAB1_AUTH_BYPASS' => 'Module 1',
  'LAB2_BOOLEAN_BLIND' => 'Module 2',
  'LAB3_UNION_BASED' => 'Module 3',
  'LAB4_ERROR_BASED' => 'Module 4',
  'LAB5_TIME_BASED' => 'Module 5',
];

$labGoals = [
  'LAB0_INTRO' => 'Започни с основите и отключи модулите.',
  'LAB1_AUTH_BYPASS' => 'Получаване на достъп чрез логически bypass.',
  'LAB2_BOOLEAN_BLIND' => 'Потвърди факт чрез true/false отговори.',
  'LAB3_UNION_BASED' => 'Извлечи данни чрез UNION заявки.',
  'LAB4_ERROR_BASED' => 'Използвай грешки за извличане на информация.',
  'LAB5_TIME_BASED' => 'Потвърди условие чрез време за отговор.',
];

$labDifficulty = [
  'LAB0_INTRO' => 'Въведение',
  'LAB1_AUTH_BYPASS' => 'Лесно',
  'LAB2_BOOLEAN_BLIND' => 'Средно',
  'LAB3_UNION_BASED' => 'Средно',
  'LAB4_ERROR_BASED' => 'Трудно',
  'LAB5_TIME_BASED' => 'Трудно',
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
$userPoints = 0;
$leaderboard = [];
$userRank = null;
$userRankTotal = 0;
$treeLabs = [];
$introDone = false;

if ($loggedIn && $userId > 0 && !$isAdmin) {
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

  // next module = first not completed from ordered modules
  foreach (get_modules_ordered() as $m) {
    $code = (string)($m['code'] ?? '');
    if ($code !== '' && empty($userCompletedSet[$code])) {
      $nextModule = $m;
      break;
    }
  }

  $introDone = !empty($userCompletedSet['LAB0_INTRO']);

  // build skill tree (status: completed / current / locked)
  $foundCurrent = false;
  foreach (get_modules_ordered() as $m) {
    $code = (string)($m['code'] ?? '');
    $done = $code !== '' && !empty($userCompletedSet[$code]);
    $status = 'locked';
    if ($done) {
      $status = 'completed';
    } elseif (!$foundCurrent) {
      $status = 'current';
      $foundCurrent = true;
    }

    $treeLabs[] = [
      'code' => $code,
      'label' => (string)($m['label'] ?? $code),
      'path' => (string)($m['path'] ?? ''),
      'status' => $status,
    ];
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

  // points total
  $stmtPts = mysqli_prepare($conn, "SELECT COALESCE(SUM(delta),0) AS pts FROM user_points_ledger WHERE user_id = ?");
  if ($stmtPts) {
    mysqli_stmt_bind_param($stmtPts, 'i', $userId);
    mysqli_stmt_execute($stmtPts);
    $rp = mysqli_stmt_get_result($stmtPts);
    if ($rp && ($row = mysqli_fetch_assoc($rp))) {
      $userPoints = (int)($row['pts'] ?? 0);
    }
    mysqli_stmt_close($stmtPts);
  }

  // leaderboard top 10 (exclude admins)
  $resLb = mysqli_query($conn, "
    SELECT u.id, u.username, COALESCE(SUM(l.delta),0) AS points
    FROM users u
    LEFT JOIN user_points_ledger l ON l.user_id = u.id
    WHERE COALESCE(u.role,'user') <> 'admin'
    GROUP BY u.id
    ORDER BY points DESC, u.username ASC
    LIMIT 10
  ");
  if ($resLb) {
    while ($row = mysqli_fetch_assoc($resLb)) {
      $leaderboard[] = $row;
    }
  }

  // user rank (global)
  $stmtRank = mysqli_prepare($conn, "
    SELECT COUNT(*) AS r
    FROM (
      SELECT u.id, COALESCE(SUM(l.delta),0) AS points
      FROM users u
      LEFT JOIN user_points_ledger l ON l.user_id = u.id
      WHERE COALESCE(u.role,'user') <> 'admin'
      GROUP BY u.id
    ) t
    WHERE t.points > ?
  ");
  if ($stmtRank) {
    mysqli_stmt_bind_param($stmtRank, 'i', $userPoints);
    mysqli_stmt_execute($stmtRank);
    $rr = mysqli_stmt_get_result($stmtRank);
    if ($rr && ($row = mysqli_fetch_assoc($rr))) {
      $userRank = (int)($row['r'] ?? 0) + 1;
    }
    mysqli_stmt_close($stmtRank);
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
  <?php if ($isAdmin): ?>
    <section class="p-4 p-md-5 bg-white rounded-4 shadow-sm border">
      <h1 class="h3 fw-bold mb-2">Начало</h1>
      <p class="text-secondary mb-3">
        Влязъл си като <strong>админ</strong>. Нямаш достъп до упражненията.
      </p>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-brand" href="<?php echo $base; ?>/public/admin/index.php">Към админ панела</a>
        <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/dashboard.php">Табло</a>
        <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/profile.php">Профил</a>
      </div>
    </section>
  <?php else: ?>
  <section class="p-4 p-md-5 bg-white rounded-4 shadow-sm border">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge text-bg-dark rounded-pill">Добре дошъл</span>
          <span class="text-secondary">@<?php echo htmlspecialchars($username); ?></span>
        </div>
        <h1 class="h3 fw-bold mb-1">Начало</h1>
        <p class="text-secondary mb-0">Изгради уменията си стъпка по стъпка по SQLi маршрута.</p>
      </div>
      <div class="text-end">
        <div class="badge text-bg-primary rounded-pill px-3 py-2">
          <?php echo (int)$userCompletedMain; ?> / <?php echo (int)count($mainLabCodes); ?>
        </div>
        <div class="small text-secondary mt-1"><?php echo (int)$progressPct; ?>% завършено</div>
      </div>
    </div>

    <?php if (!$introDone): ?>
      <div class="alert alert-warning mt-3 mb-0 rounded-4">
        👋 Започни с <strong>Intro</strong>, за да отключиш платформата.
      </div>
    <?php endif; ?>

    <div class="row g-4 mt-2">
      <div class="col-12 col-lg-7">
        <div class="card shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h2 class="h6 fw-bold mb-0">SQLi маршрут</h2>
              <span class="small text-secondary">🟢 Завършен • 🟡 Текущ • 🔒 Заключен</span>
            </div>

            <div class="skill-tree">
              <?php foreach ($treeLabs as $node): ?>
                <?php
                  $status = $node['status'] ?? 'locked';
                  $canOpen = $status !== 'locked' && !empty($node['path']);
                  $label = $labTitles[$node['code']] ?? ($node['label'] ?? $node['code']);
                  $short = $labShort[$node['code']] ?? ($node['label'] ?? $node['code']);
                ?>
                <div class="skill-node <?php echo htmlspecialchars($status); ?>">
                  <?php if ($canOpen): ?>
                    <a class="text-decoration-none" href="<?php echo htmlspecialchars($node['path']); ?>">
                      <strong><?php echo htmlspecialchars($short); ?></strong>
                      <span class="text-secondary">— <?php echo htmlspecialchars($label); ?></span>
                    </a>
                  <?php else: ?>
                    <span class="text-secondary">
                      <strong><?php echo htmlspecialchars($short); ?></strong>
                      — <?php echo htmlspecialchars($label); ?>
                    </span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-5">
        <div class="card shadow-sm mb-3">
          <div class="card-body">
            <div class="d-flex align-items-start justify-content-between">
              <div>
                <h2 class="h6 fw-bold mb-1">Следващо упражнение</h2>
                <?php if (!empty($nextModule)): ?>
                  <?php
                    $nextCode = (string)($nextModule['code'] ?? '');
                    $nextTitle = $labTitles[$nextCode] ?? ($nextModule['label'] ?? $nextCode);
                    $nextGoal = $labGoals[$nextCode] ?? 'Изпълни задачата и отключи следващия модул.';
                    $nextDiff = $labDifficulty[$nextCode] ?? '—';
                  ?>
                  <div class="text-secondary small mb-2"><?php echo htmlspecialchars($nextTitle); ?></div>
                  <span class="badge text-bg-secondary rounded-pill"><?php echo htmlspecialchars($nextDiff); ?></span>
                <?php else: ?>
                  <div class="text-secondary small mb-2">Всичко е завършено</div>
                  <span class="badge text-bg-success rounded-pill">Готово</span>
                <?php endif; ?>
              </div>
            </div>

            <div class="mt-3 text-secondary small">
              <?php if (!empty($nextModule)): ?>
                <?php echo htmlspecialchars($nextGoal); ?>
              <?php else: ?>
                Няма следващи модули. Прегледай таблото или профила.
              <?php endif; ?>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
              <?php if (!empty($nextModule['path'])): ?>
                <a class="btn btn-brand" href="<?php echo htmlspecialchars($nextModule['path']); ?>">▶ Старт</a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h2 class="h6 fw-bold mb-0">Класация</h2>
              <span class="small text-secondary">Топ 10</span>
            </div>

            <?php if (empty($leaderboard)): ?>
              <div class="text-secondary small">Още няма точки за класация.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr class="text-secondary small">
                      <th>#</th>
                      <th>User</th>
                      <th class="text-end">Points</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $rank = 1; ?>
                    <?php foreach ($leaderboard as $row): ?>
                      <?php
                        $rowId = (int)($row['id'] ?? 0);
                        $isMe = $rowId === $userId;
                      ?>
                      <tr class="<?php echo $isMe ? 'table-warning' : ''; ?>">
                        <td><?php echo (int)$rank; ?></td>
                        <td>
                          <?php echo htmlspecialchars((string)($row['username'] ?? '—')); ?>
                          <?php if ($isMe): ?> ⭐<?php endif; ?>
                        </td>
                        <td class="text-end"><?php echo (int)($row['points'] ?? 0); ?></td>
                      </tr>
                      <?php $rank++; ?>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <?php if ($userRank !== null && $userRank > 10): ?>
                <div class="small text-secondary mt-2">
                  Ти – №<?php echo (int)$userRank; ?> (<?php echo (int)$userPoints; ?> точки)
                </div>
              <?php endif; ?>
            <?php endif; ?>
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
          <h3 class="h6 fw-bold mb-2">Фокус</h3>
          <div class="text-secondary small">
            <?php if (!empty($nextModule)): ?>
              Следващата цел е: <strong><?php echo htmlspecialchars($labTitles[(string)($nextModule['code'] ?? '')] ?? '—'); ?></strong><br>
              <?php echo htmlspecialchars($labGoals[(string)($nextModule['code'] ?? '')] ?? 'Завърши задачата, за да отключиш следващия модул.'); ?>
            <?php else: ?>
              Няма следващи модули. Провери таблото за детайли.
            <?php endif; ?>
          </div>
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
