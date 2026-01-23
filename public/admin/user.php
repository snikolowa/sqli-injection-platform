<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_admin();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout_bs.php';
require_once __DIR__ . '/../../includes/attempt_logger.php';

$base = base_url();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo "Bad request";
    exit;
}

/**
 * Parse YYYY-MM-DD -> validated date string or default
 */
function parse_date_or_default(string $s, ?string $default): ?string {
    $s = trim($s);
    if ($s === '') return $default;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $default;
    return $s;
}

/**
 * Normalize and cap date range for NDJSON scans.
 * Returns [from, to, fromTs, toTs, days]
 */
function normalize_range_for_scan(?string $fromIn, ?string $toIn): array {
    $from = parse_date_or_default($fromIn ?? '', date('Y-m-d', strtotime('-7 days')));
    $to = parse_date_or_default($toIn ?? '', date('Y-m-d'));

    $fromTs = strtotime($from);
    $toTs = strtotime($to);

    if ($fromTs === false || $toTs === false) {
        $from = date('Y-m-d', strtotime('-7 days'));
        $to = date('Y-m-d');
        $fromTs = strtotime($from);
        $toTs = strtotime($to);
    }

    if ($toTs < $fromTs) {
        $tmp = $from; $from = $to; $to = $tmp;
        $tmp2 = $fromTs; $fromTs = $toTs; $toTs = $tmp2;
    }

    $days = (int)floor(($toTs - $fromTs) / 86400) + 1;

    // Safety cap: max 31 days scan
    if ($days > 31) {
        $from = date('Y-m-d', strtotime('-30 days', $toTs));
        $fromTs = strtotime($from);
        $days = 31;
    }

    return [$from, $to, $fromTs, $toTs, $days];
}

function safe_lab(?string $v): ?string {
    if ($v === null) return null;
    $v = trim($v);
    if ($v === '') return null;
    if (!preg_match('/^[a-zA-Z0-9_]{1,64}$/', $v)) return null;
    return $v;
}

// user
$stmt = mysqli_prepare($conn, "SELECT id, username, COALESCE(email,'') AS email, COALESCE(role,'user') AS role FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$userRes = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($userRes);
mysqli_stmt_close($stmt);

if (!$user) {
    http_response_code(404);
    echo "Not found";
    exit;
}

// Progress (DB)
$progress = [];
$res = mysqli_query($conn, "SELECT lab_code, completed, completed_at FROM user_progress WHERE user_id=" . (int)$id . " ORDER BY completed DESC, completed_at DESC");
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) $progress[] = $r;
}

// Overall aggregates (DB)
$aggUser = [
    'attempts_total' => 0,
    'success_total' => 0,
    'last_attempt_at' => null,
    'last_success_at' => null,
];
$stmtA = mysqli_prepare($conn, "SELECT attempts_total, success_total, last_attempt_at, last_success_at FROM attempts_agg_user WHERE user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmtA, "i", $id);
mysqli_stmt_execute($stmtA);
$rA = mysqli_stmt_get_result($stmtA);
if ($rA && ($row = mysqli_fetch_assoc($rA))) {
    $aggUser['attempts_total'] = (int)($row['attempts_total'] ?? 0);
    $aggUser['success_total'] = (int)($row['success_total'] ?? 0);
    $aggUser['last_attempt_at'] = $row['last_attempt_at'] ?? null;
    $aggUser['last_success_at'] = $row['last_success_at'] ?? null;
}
mysqli_stmt_close($stmtA);

// Aggregates per lab (DB)
$agg = [];
$stmt2 = mysqli_prepare($conn, "SELECT lab, attempts_count, success_count, last_attempt_at, last_success_at FROM attempts_agg_user_lab WHERE user_id=? ORDER BY last_attempt_at DESC");
mysqli_stmt_bind_param($stmt2, "i", $id);
mysqli_stmt_execute($stmt2);
$r2 = mysqli_stmt_get_result($stmt2);
while ($row = mysqli_fetch_assoc($r2)) $agg[] = $row;
mysqli_stmt_close($stmt2);

// Build map for charting
$aggMap = []; // lab => row
$maxAttempts = 1;
foreach ($agg as $a) {
    $lab = (string)($a['lab'] ?? '');
    $aggMap[$lab] = $a;
    $maxAttempts = max($maxAttempts, (int)($a['attempts_count'] ?? 0));
}

// --- NDJSON period scan for this user (for charts + last attempts + attempts-to-first-success) ---
[$from, $to, $fromTs, $toTs, $days] = normalize_range_for_scan($_GET['from'] ?? '', $_GET['to'] ?? '');
$filterLab = safe_lab($_GET['lab'] ?? null);

// Last attempts
$N = 50;
$attempts = []; // newest first

// Period stats by lab
// lab => [
//   attempts, success, fail,
//   first_success_ts, attempts_to_first_success
// ]
$periodByLab = [];

$dir = attempts_storage_dir();

// Scan from end date backwards so last attempts come fast
for ($i = 0; $i < $days; $i++) {
    $date = date('Y-m-d', strtotime("-$i day", $toTs));
    $path = $dir . DIRECTORY_SEPARATOR . $date . '.ndjson';
    if (!is_file($path)) continue;

    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) continue;

    // Walk backwards for newest-first attempts
    for ($j = count($lines) - 1; $j >= 0; $j--) {
        $obj = json_decode($lines[$j], true);
        if (!is_array($obj)) continue;

        if ((int)($obj['user_id'] ?? 0) !== $id) continue;

        $ts = (string)($obj['ts'] ?? '');
        $tsUnix = $ts ? strtotime($ts) : false;
        if ($tsUnix !== false) {
            if ($tsUnix < $fromTs || $tsUnix > ($toTs + 86399)) continue;
        }

        $lab = (string)($obj['lab'] ?? '');
        if ($filterLab !== null && $lab !== $filterLab) continue;

        $success = (int)($obj['success'] ?? 0);

        // collect last attempts
        if (count($attempts) < $N) {
            $attempts[] = $obj;
        }

        // period stats
        if (!isset($periodByLab[$lab])) {
            $periodByLab[$lab] = [
                'attempts' => 0,
                'success' => 0,
                'fail' => 0,
                'first_success_ts' => null,
                'attempts_to_first_success' => null,
            ];
        }
        $periodByLab[$lab]['attempts']++;

        if ($success === 1) {
            $periodByLab[$lab]['success']++;
            if ($periodByLab[$lab]['first_success_ts'] === null) {
                // IMPORTANT: because we scan newest->oldest, this would give LAST success, not first.
                // We'll fix below by calculating attempts_to_first_success with a second pass oldest->newest per day range.
            }
        } else {
            $periodByLab[$lab]['fail']++;
        }

        // Break early if we already got enough attempts AND lab filter is set (fast)
        // (Period stats still correct enough for that lab because we're scanning whole period anyway when lab filter is used)
    }
}

// Fix first_success_ts + attempts_to_first_success properly (oldest -> newest pass)
if (!empty($periodByLab)) {
    // Reset first-success fields and recompute correctly
    foreach ($periodByLab as $lab => $v) {
        $periodByLab[$lab]['first_success_ts'] = null;
        $periodByLab[$lab]['attempts_to_first_success'] = null;
        $periodByLab[$lab]['attempts_seen_oldest'] = 0;
    }

    for ($i = 0; $i < $days; $i++) {
        $date = date('Y-m-d', strtotime("+$i day", $fromTs));
        $path = $dir . DIRECTORY_SEPARATOR . $date . '.ndjson';
        if (!is_file($path)) continue;

        $fp = fopen($path, 'rb');
        if (!$fp) continue;

        while (($line = fgets($fp)) !== false) {
            $line = trim($line);
            if ($line === '') continue;

            $obj = json_decode($line, true);
            if (!is_array($obj)) continue;

            if ((int)($obj['user_id'] ?? 0) !== $id) continue;

            $ts = (string)($obj['ts'] ?? '');
            $tsUnix = $ts ? strtotime($ts) : false;
            if ($tsUnix !== false) {
                if ($tsUnix < $fromTs || $tsUnix > ($toTs + 86399)) continue;
            }

            $lab = (string)($obj['lab'] ?? '');
            if (!isset($periodByLab[$lab])) continue;
            if ($filterLab !== null && $lab !== $filterLab) continue;

            $periodByLab[$lab]['attempts_seen_oldest']++;

            $success = (int)($obj['success'] ?? 0);
            if ($success === 1 && $periodByLab[$lab]['first_success_ts'] === null) {
                $periodByLab[$lab]['first_success_ts'] = $ts;
                $periodByLab[$lab]['attempts_to_first_success'] = (int)$periodByLab[$lab]['attempts_seen_oldest'];
            }
        }

        fclose($fp);
    }

    // cleanup helper field
    foreach ($periodByLab as $lab => $v) {
        unset($periodByLab[$lab]['attempts_seen_oldest']);
    }
}

// Sort period labs by attempts desc
uksort($periodByLab, function($a, $b) use ($periodByLab) {
    return ($periodByLab[$b]['attempts'] ?? 0) <=> ($periodByLab[$a]['attempts'] ?? 0);
});

$completedCount = 0;
foreach ($progress as $p) {
    if ((int)($p['completed'] ?? 0) === 1) $completedCount++;
}

bs_layout_start('Admin – User');
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h1 class="h4 fw-bold mb-1"><?php echo htmlspecialchars($user['username']); ?></h1>
    <div class="text-secondary small">
      ID: <?php echo (int)$user['id']; ?> · Role: <?php echo htmlspecialchars($user['role']); ?>
      <?php if (!empty($user['email'])): ?> · <?php echo htmlspecialchars($user['email']); ?><?php endif; ?>
    </div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/admin/users.php">← Users</a>
    <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/public/admin/index.php">Админ</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-3">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="text-secondary small">Completed (DB)</div>
        <div class="display-6 fw-bold"><?php echo (int)$completedCount; ?></div>
        <div class="text-secondary small">бр. завършени</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-3">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="text-secondary small">Total attempts (DB)</div>
        <div class="display-6 fw-bold"><?php echo (int)$aggUser['attempts_total']; ?></div>
        <div class="text-secondary small">агрегирани</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-3">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="text-secondary small">Total success (DB)</div>
        <div class="display-6 fw-bold"><?php echo (int)$aggUser['success_total']; ?></div>
        <div class="text-secondary small">агрегирани</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-3">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="text-secondary small">Last activity (DB)</div>
        <div class="fw-bold"><?php echo $aggUser['last_attempt_at'] ? htmlspecialchars((string)$aggUser['last_attempt_at']) : '—'; ?></div>
        <div class="text-secondary small">последен опит</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-12 col-lg-7">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0">Упражнения (DB агрегати) – “бар диаграма”</h2>
          <span class="small text-secondary">Attempts скалирани спрямо max</span>
        </div>

        <?php if (empty($agg)): ?>
          <div class="text-secondary small mt-2">Няма агрегирани данни за упражнения.</div>
        <?php else: ?>
          <div class="mt-3">
            <?php foreach ($agg as $a): ?>
              <?php
                $lab = (string)($a['lab'] ?? '');
                $attemptsCount = (int)($a['attempts_count'] ?? 0);
                $successCount = (int)($a['success_count'] ?? 0);
                $pct = (int)round(($attemptsCount / max(1, $maxAttempts)) * 100);
                $solved = $successCount > 0;
              ?>
              <div class="d-flex justify-content-between align-items-center small">
                <div class="fw-semibold">
                  <?php echo htmlspecialchars($lab); ?>
                  <?php if ($solved): ?>
                    <span class="badge text-bg-success ms-2">solved</span>
                  <?php endif; ?>
                </div>
                <div class="text-secondary">
                  attempts: <strong><?php echo (int)$attemptsCount; ?></strong>
                  · success: <strong><?php echo (int)$successCount; ?></strong>
                </div>
              </div>
              <div class="progress mb-3" style="height: 10px;">
                <div class="progress-bar" role="progressbar" style="width: <?php echo (int)$pct; ?>%"></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-5">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h2 class="h6 fw-bold mb-2">Експорт за този потребител</h2>
        <p class="text-secondary small mb-3">
          Избери период (max 31 дни сканиране) и свали CSV.
        </p>

        <form class="row g-2" method="get" action="<?php echo $base; ?>/public/admin/export.php">
          <input type="hidden" name="action" value="attempts">
          <input type="hidden" name="user_id" value="<?php echo (int)$id; ?>">

          <div class="col-12 col-md-6">
            <label class="form-label">From</label>
            <input class="form-control" name="from" value="<?php echo htmlspecialchars($from); ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">To</label>
            <input class="form-control" name="to" value="<?php echo htmlspecialchars($to); ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Lab (optional)</label>
            <input class="form-control" name="lab" value="<?php echo htmlspecialchars($_GET['lab'] ?? ''); ?>" placeholder="lab3_practice">
          </div>

          <div class="col-12 d-flex gap-2 flex-wrap">
            <button class="btn btn-brand" type="submit">⬇️ Export raw attempts (CSV)</button>
            <a class="btn btn-outline-secondary"
               href="<?php echo $base; ?>/public/admin/export.php?action=report_user_lab&user_id=<?php echo (int)$id; ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>">
              ⬇️ Export report user+lab (CSV)
            </a>
          </div>

          <div class="small text-secondary mt-2">
            Report CSV включва “attempts_to_first_success_in_period”.
          </div>
        </form>

        <hr class="my-3">

        <h3 class="h6 fw-bold mb-2">Периодни показатели (NDJSON)</h3>
        <form class="row g-2" method="get">
          <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
          <div class="col-12 col-md-6">
            <label class="form-label">From</label>
            <input class="form-control" name="from" value="<?php echo htmlspecialchars($from); ?>">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">To</label>
            <input class="form-control" name="to" value="<?php echo htmlspecialchars($to); ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Lab (optional)</label>
            <input class="form-control" name="lab" value="<?php echo htmlspecialchars($_GET['lab'] ?? ''); ?>" placeholder="lab2_step1">
          </div>
          <div class="col-12">
            <button class="btn btn-outline-secondary" type="submit">Обнови периода</button>
          </div>
          <div class="small text-secondary">
            Този блок (и “Последни опити” долу) се базира на файловете в storage/attempts за избрания период.
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 fw-bold mb-2">Периоден отчет по упражнения (NDJSON) – <?php echo htmlspecialchars($from); ?> → <?php echo htmlspecialchars($to); ?></h2>

        <?php if (empty($periodByLab)): ?>
          <div class="text-secondary small">Няма намерени опити за периода.</div>
        <?php else: ?>
          <?php
            $maxPeriodAttempts = 1;
            foreach ($periodByLab as $lab => $v) $maxPeriodAttempts = max($maxPeriodAttempts, (int)($v['attempts'] ?? 0));
          ?>

          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr class="text-secondary">
                  <th>Lab</th>
                  <th class="text-end">Attempts</th>
                  <th class="text-end">Success</th>
                  <th class="text-end">Fail</th>
                  <th>Attempts bar</th>
                  <th class="text-end">Solved?</th>
                  <th class="text-end">Attempts to 1st success</th>
                  <th class="text-end">1st success time</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($periodByLab as $lab => $v): ?>
                  <?php
                    $att = (int)($v['attempts'] ?? 0);
                    $succ = (int)($v['success'] ?? 0);
                    $fail = (int)($v['fail'] ?? 0);
                    $pct = (int)round(($att / max(1, $maxPeriodAttempts)) * 100);
                    $solved = ($v['first_success_ts'] !== null);
                    $attsTo = $v['attempts_to_first_success'];
                  ?>
                  <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars($lab); ?></td>
                    <td class="text-end"><?php echo (int)$att; ?></td>
                    <td class="text-end"><?php echo (int)$succ; ?></td>
                    <td class="text-end"><?php echo (int)$fail; ?></td>
                    <td style="min-width: 180px;">
                      <div class="progress" style="height: 9px;">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo (int)$pct; ?>%"></div>
                      </div>
                    </td>
                    <td class="text-end"><?php echo $solved ? '✅' : '—'; ?></td>
                    <td class="text-end"><?php echo $attsTo !== null ? (int)$attsTo : '—'; ?></td>
                    <td class="text-end small text-secondary"><?php echo $solved ? htmlspecialchars((string)$v['first_success_ts']) : '—'; ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="small text-secondary mt-2">
            “Attempts to 1st success” се смята само ако има success в избрания период.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-12 col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h2 class="h6 fw-bold">Прогрес (DB)</h2>
        <?php if (empty($progress)): ?>
          <div class="text-secondary small">Няма записан прогрес.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr class="text-secondary">
                  <th>Lab</th>
                  <th>Completed</th>
                  <th>Completed at</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($progress as $p): ?>
                <tr>
                  <td><?php echo htmlspecialchars((string)$p['lab_code']); ?></td>
                  <td><?php echo ((int)($p['completed'] ?? 0) === 1) ? '✅' : '—'; ?></td>
                  <td class="small text-secondary"><?php echo !empty($p['completed_at']) ? htmlspecialchars((string)$p['completed_at']) : '—'; ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-body">
        <h2 class="h6 fw-bold">Последни опити (NDJSON) – до <?php echo (int)$N; ?> реда</h2>
        <?php if (empty($attempts)): ?>
          <div class="text-secondary small">Няма намерени опити за периода.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr class="text-secondary">
                  <th>Time</th>
                  <th>Lab</th>
                  <th>Success</th>
                  <th>Input preview</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($attempts as $t): ?>
                <tr>
                  <td class="small text-secondary"><?php echo htmlspecialchars((string)($t['ts'] ?? '')); ?></td>
                  <td><?php echo htmlspecialchars((string)($t['lab'] ?? '')); ?></td>
                  <td><?php echo ((int)($t['success'] ?? 0) === 1) ? '✅' : '❌'; ?></td>
                  <td class="small"><?php echo htmlspecialchars((string)($t['input_preview'] ?? '')); ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="small text-secondary mt-2">
            Сканира период: <strong><?php echo htmlspecialchars($from); ?></strong> → <strong><?php echo htmlspecialchars($to); ?></strong>
            (макс 31 дни). <?php if ($filterLab): ?> Филтър lab: <code><?php echo htmlspecialchars($filterLab); ?></code><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php bs_layout_end(); ?>
