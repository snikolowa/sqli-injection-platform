<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_admin();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout_bs.php';

$base = base_url();

// --- Helpers ---
function q_one_int(mysqli $conn, string $sql): int {
  $res = mysqli_query($conn, $sql);
  if ($res && ($row = mysqli_fetch_assoc($res))) {
    $v = array_values($row)[0] ?? 0;
    return (int)$v;
  }
  return 0;
}

// --- Core stats ---
$totalUsers = q_one_int($conn, "SELECT COUNT(*) FROM users WHERE COALESCE(role,'user') <> 'admin'");

// Active users in last 7 days (based on last_attempt_at aggregate)
$active7 = q_one_int($conn, "
  SELECT COUNT(*)
  FROM attempts_agg_user a
  JOIN users u ON u.id = a.user_id
  WHERE COALESCE(u.role,'user') <> 'admin'
    AND a.last_attempt_at >= (NOW() - INTERVAL 7 DAY)
");

// Completed all main labs (LAB1..LAB5)
$mainLabCodes = [
  'LAB1_AUTH_BYPASS',
  'LAB2_BOOLEAN_BLIND',
  'LAB3_UNION_BASED',
  'LAB4_ERROR_BASED',
  'LAB5_TIME_BASED',
];

$completedAllUsers = 0;
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
  $types = str_repeat('s', count($mainLabCodes)) . 'i';
  $params = array_merge($mainLabCodes, [count($mainLabCodes)]);
  $bind = [];
  $bind[] = $types;
  foreach ($params as $k => $v) $bind[] = &$params[$k];
  call_user_func_array([$stmt, 'bind_param'], $bind);
  mysqli_stmt_execute($stmt);
  $r = mysqli_stmt_get_result($stmt);
  if ($r && ($row = mysqli_fetch_assoc($r))) {
    $completedAllUsers = (int)($row['c'] ?? 0);
  }
  mysqli_stmt_close($stmt);
}

// Total attempts (all non-admin users) from aggregates
$totalAttempts = q_one_int($conn, "
  SELECT COALESCE(SUM(a.attempts_total),0)
  FROM attempts_agg_user a
  JOIN users u ON u.id = a.user_id
  WHERE COALESCE(u.role,'user') <> 'admin'
");

// Recent activity (last attempt)
$recent = [];
$resRecent = mysqli_query($conn, "
  SELECT u.id, u.username, a.last_attempt_at, a.attempts_total, a.success_total
  FROM attempts_agg_user a
  JOIN users u ON u.id = a.user_id
  WHERE COALESCE(u.role,'user') <> 'admin'
  ORDER BY a.last_attempt_at DESC
  LIMIT 8
");
if ($resRecent) {
  while ($row = mysqli_fetch_assoc($resRecent)) {
    $recent[] = $row;
  }
}

// Difficulty: top labs by avg attempts per success (approx)
$difficult = [];
$resDiff = mysqli_query($conn, "
  SELECT
    lab,
    COUNT(*) AS users_tried,
    SUM(attempts_count) AS attempts_sum,
    SUM(success_count) AS success_sum,
    ROUND(SUM(attempts_count) / NULLIF(SUM(success_count),0), 2) AS attempts_per_success
  FROM attempts_agg_user_lab
  GROUP BY lab
  HAVING users_tried >= 3
  ORDER BY (attempts_per_success IS NULL) DESC, attempts_per_success DESC
  LIMIT 6
");
if ($resDiff) {
  while ($row = mysqli_fetch_assoc($resDiff)) {
    $difficult[] = $row;
  }
}

// Lab popularity: top labs by users tried
$popular = [];
$resPop = mysqli_query($conn, "
  SELECT lab, COUNT(*) AS users_tried
  FROM attempts_agg_user_lab
  GROUP BY lab
  ORDER BY users_tried DESC
  LIMIT 6
");
if ($resPop) {
  while ($row = mysqli_fetch_assoc($resPop)) {
    $popular[] = $row;
  }
}

bs_layout_start('Админ • Статистики');
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1">Админ панел</h1>
    <p class="text-secondary mb-0">Статистики, потребители, прогрес и експорт.</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/admin/users.php">👥 Потребители</a>
    <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/admin/export.php">⬇️ Експорт</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-3">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="text-secondary small">Потребители (без админи)</div>
        <div class="display-6 fw-bold"><?php echo (int)$totalUsers; ?></div>
        <div class="text-secondary small">Общо регистрирани</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-3">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="text-secondary small">Активни (последни 7 дни)</div>
        <div class="display-6 fw-bold"><?php echo (int)$active7; ?></div>
        <div class="text-secondary small">По последен опит</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-3">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="text-secondary small">Завършили всички (LAB1–LAB5)</div>
        <div class="display-6 fw-bold"><?php echo (int)$completedAllUsers; ?></div>
        <div class="text-secondary small">Пълно завършване</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-3">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="text-secondary small">Общо опити</div>
        <div class="display-6 fw-bold"><?php echo (int)$totalAttempts; ?></div>
        <div class="text-secondary small">От агрегати</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-12 col-lg-7">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h2 class="h6 fw-bold mb-0">Последна активност</h2>
          <a class="small text-decoration-none" href="<?php echo $base; ?>/public/admin/users.php">Виж всички →</a>
        </div>

        <?php if (empty($recent)): ?>
          <div class="text-secondary">Няма данни за активност.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr class="text-secondary">
                  <th>Потребител</th>
                  <th>Последен опит</th>
                  <th class="text-end">Опити</th>
                  <th class="text-end">Успешни</th>
                  <th class="text-end">Профил</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent as $r): ?>
                  <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars($r['username'] ?? ''); ?></td>
                    <td class="text-secondary"><?php echo htmlspecialchars($r['last_attempt_at'] ?? '—'); ?></td>
                    <td class="text-end"><?php echo (int)($r['attempts_total'] ?? 0); ?></td>
                    <td class="text-end"><?php echo (int)($r['success_total'] ?? 0); ?></td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-outline-secondary"
                         href="<?php echo $base; ?>/public/admin/user.php?id=<?php echo (int)($r['id'] ?? 0); ?>">
                        Отвори
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-5">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h2 class="h6 fw-bold mb-2">Най-трудни упражнения (прибл.)</h2>
        <div class="text-secondary small mb-3">
          Изчислено като <em>общо опити / общо успешни</em> по упражнение (от агрегати).
        </div>

        <?php if (empty($difficult)): ?>
          <div class="text-secondary">Няма достатъчно данни.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr class="text-secondary">
                  <th>Упражнение</th>
                  <th class="text-end">Пробвали</th>
                  <th class="text-end">Опити/успех</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($difficult as $d): ?>
                  <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars($d['lab'] ?? ''); ?></td>
                    <td class="text-end"><?php echo (int)($d['users_tried'] ?? 0); ?></td>
                    <td class="text-end">
                      <?php echo $d['attempts_per_success'] !== null ? htmlspecialchars((string)$d['attempts_per_success']) : '—'; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <hr class="my-3">

        <h3 class="h6 fw-bold mb-2">Най-посещавани упражнения</h3>
        <?php if (empty($popular)): ?>
          <div class="text-secondary">Няма данни.</div>
        <?php else: ?>
          <?php
            $maxTried = 0;
            foreach ($popular as $p) $maxTried = max($maxTried, (int)($p['users_tried'] ?? 0));
            $maxTried = max(1, $maxTried);
          ?>
          <?php foreach ($popular as $p): ?>
            <?php
              $lab = (string)($p['lab'] ?? '');
              $v = (int)($p['users_tried'] ?? 0);
              $pct = (int)round(($v / $maxTried) * 100);
            ?>
            <div class="d-flex justify-content-between small">
              <span class="text-secondary"><?php echo htmlspecialchars($lab); ?></span>
              <span class="fw-semibold"><?php echo (int)$v; ?></span>
            </div>
            <div class="progress mb-2" style="height: 8px;">
              <div class="progress-bar" role="progressbar" style="width: <?php echo (int)$pct; ?>%"></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
          <div class="fw-semibold">Бързи действия</div>
          <div class="text-secondary small">Потребители, прогрес, опити и експорт.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/admin/users.php">👥 Потребители и прогрес</a>
          <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/admin/export.php">⬇️ Експорт (CSV)</a>
          <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/profile.php">Моят админ профил</a>
        </div>
      </div>
    </div>
  </div>
</div>
