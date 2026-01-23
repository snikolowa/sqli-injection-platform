<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_admin();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout_bs.php';
require_once __DIR__ . '/../../includes/attempt_logger.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo "Bad request";
    exit;
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

// progress
$progress = [];
$res = mysqli_query($conn, "SELECT lab_code, completed, completed_at FROM user_progress WHERE user_id=" . (int)$id . " ORDER BY completed DESC, completed_at DESC");
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) $progress[] = $r;
}

// aggregates per lab
$agg = [];
$stmt2 = mysqli_prepare($conn, "SELECT lab, attempts_count, success_count, last_attempt_at, last_success_at FROM attempts_agg_user_lab WHERE user_id=? ORDER BY last_attempt_at DESC");
mysqli_stmt_bind_param($stmt2, "i", $id);
mysqli_stmt_execute($stmt2);
$r2 = mysqli_stmt_get_result($stmt2);
while ($row = mysqli_fetch_assoc($r2)) $agg[] = $row;
mysqli_stmt_close($stmt2);

// last attempts from NDJSON (scan recent days, take last N)
$N = 50;
$attempts = [];

$dir = attempts_storage_dir();
$daysToScan = 7; // v1: scan last 7 days
for ($i=0; $i<$daysToScan; $i++) {
    $date = date('Y-m-d', strtotime("-$i day"));
    $path = $dir . DIRECTORY_SEPARATOR . $date . '.ndjson';
    if (!is_file($path)) continue;

    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) continue;

    // scan from end backwards for this user
    for ($j = count($lines)-1; $j >= 0; $j--) {
        $obj = json_decode($lines[$j], true);
        if (!is_array($obj)) continue;
        if ((int)($obj['user_id'] ?? 0) !== $id) continue;

        $attempts[] = $obj;
        if (count($attempts) >= $N) break 2;
    }
}

// newest first already
bs_layout_start('Admin – User');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h1 class="h4 fw-bold mb-1"><?php echo htmlspecialchars($user['username']); ?></h1>
        <div class="text-secondary small">
          ID: <?php echo (int)$user['id']; ?> · Role: <?php echo htmlspecialchars($user['role']); ?>
          <?php if (!empty($user['email'])): ?> · <?php echo htmlspecialchars($user['email']); ?><?php endif; ?>
        </div>
      </div>
      <a class="btn btn-outline-secondary" href="users.php">← Users</a>
    </div>

    <hr>

    <h2 class="h6 fw-bold">Прогрес</h2>
    <?php if (empty($progress)): ?>
      <div class="text-secondary small">Няма записан прогрес.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Lab</th>
              <th>Completed</th>
              <th>Completed at</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($progress as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars($p['lab_code']); ?></td>
              <td><?php echo ((int)$p['completed'] === 1) ? '✅' : '—'; ?></td>
              <td class="small text-secondary"><?php echo $p['completed_at'] ? htmlspecialchars($p['completed_at']) : '—'; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <hr>

    <h2 class="h6 fw-bold">Агрегати по упражнения</h2>
    <?php if (empty($agg)): ?>
      <div class="text-secondary small">Няма агрегирани опити (още няма логове).</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Lab</th>
              <th>Attempts</th>
              <th>Success</th>
              <th>Last attempt</th>
              <th>Last success</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($agg as $a): ?>
            <tr>
              <td><?php echo htmlspecialchars($a['lab']); ?></td>
              <td><?php echo (int)$a['attempts_count']; ?></td>
              <td><?php echo (int)$a['success_count']; ?></td>
              <td class="small text-secondary"><?php echo htmlspecialchars($a['last_attempt_at']); ?></td>
              <td class="small text-secondary"><?php echo $a['last_success_at'] ? htmlspecialchars($a['last_success_at']) : '—'; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <hr>

    <h2 class="h6 fw-bold">Последни опити (от файловете)</h2>
    <?php if (empty($attempts)): ?>
      <div class="text-secondary small">Няма намерени опити в последните 7 дни.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
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
      <div class="small text-secondary">
        Показва до <?php echo (int)$N; ?> опита, сканира последните <?php echo (int)$daysToScan; ?> дни.
      </div>
    <?php endif; ?>

  </div>
</div>

<?php bs_layout_end(); ?>
