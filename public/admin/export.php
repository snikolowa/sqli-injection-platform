<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_admin();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout_bs.php';
require_once __DIR__ . '/../../includes/attempt_logger.php';

function send_csv_headers(string $filename): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
}

function csv_out_row($out, array $row): void {
    // Excel-friendly UTF-8 BOM:
    static $bomSent = false;
    if (!$bomSent) {
        fwrite($out, "\xEF\xBB\xBF");
        $bomSent = true;
    }
    fputcsv($out, $row);
}

/**
 * Parse YYYY-MM-DD -> timestamp range
 */
function parse_date_or_default(string $s, ?string $default): ?string {
    $s = trim($s);
    if ($s === '') return $default;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $default;
    return $s;
}

function safe_int(?string $v): ?int {
    if ($v === null) return null;
    $v = trim($v);
    if ($v === '') return null;
    if (!preg_match('/^\d+$/', $v)) return null;
    return (int)$v;
}

function safe_lab(?string $v): ?string {
    if ($v === null) return null;
    $v = trim($v);
    if ($v === '') return null;
    // allow only simple lab tokens
    if (!preg_match('/^[a-zA-Z0-9_]{1,64}$/', $v)) return null;
    return $v;
}

function safe_success(?string $v): ?int {
    if ($v === null) return null;
    $v = trim($v);
    if ($v === '') return null;
    if ($v === '0' || $v === '1') return (int)$v;
    return null;
}

function safe_username(?string $v): ?string {
    if ($v === null) return null;
    $v = trim($v);
    if ($v === '') return null;
    // keep it conservative
    if (mb_strlen($v) > 64) $v = mb_substr($v, 0, 64);
    return $v;
}

/**
 * Export 1: Users + progress + aggregates (DB)
 */
function export_users_csv(mysqli $conn): void {
    $labCodes = ['LAB0_INTRO','LAB1_AUTH_BYPASS','LAB2_BOOLEAN_BLIND','LAB3_UNION_BASED','LAB4_ERROR_BASED','LAB5_TIME_BASED'];
    $totalLabs = count($labCodes);

    $sql = "
      SELECT
        u.id,
        u.username,
        COALESCE(u.email,'') AS email,
        COALESCE(u.role,'user') AS role,
        COALESCE(p.completed_count,0) AS completed_count,
        COALESCE(a.attempts_total,0) AS attempts_total,
        COALESCE(a.success_total,0) AS success_total,
        a.last_attempt_at,
        a.last_success_at
      FROM users u
      LEFT JOIN (
        SELECT user_id, COUNT(*) AS completed_count
        FROM user_progress
        WHERE completed = 1
        GROUP BY user_id
      ) p ON p.user_id = u.id
      LEFT JOIN attempts_agg_user a ON a.user_id = u.id
      ORDER BY u.id ASC
    ";

    $res = mysqli_query($conn, $sql);

    send_csv_headers('users_progress_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');

    csv_out_row($out, [
        'user_id','username','email','role',
        'completed_count','total_labs','progress_percent',
        'attempts_total','success_total',
        'last_attempt_at','last_success_at'
    ]);

    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $cc = (int)($r['completed_count'] ?? 0);
            $pct = $totalLabs > 0 ? round(($cc / $totalLabs) * 100) : 0;

            csv_out_row($out, [
                (int)$r['id'],
                (string)$r['username'],
                (string)$r['email'],
                (string)$r['role'],
                $cc,
                $totalLabs,
                $pct,
                (int)($r['attempts_total'] ?? 0),
                (int)($r['success_total'] ?? 0),
                $r['last_attempt_at'] ?? '',
                $r['last_success_at'] ?? '',
            ]);
        }
    }

    fclose($out);
    exit;
}

/**
 * Export 2: Attempts from NDJSON files (with filters)
 * Limits: max 31 days scan (safety)
 */
function export_attempts_csv(): void {
    $from = parse_date_or_default($_GET['from'] ?? '', date('Y-m-d', strtotime('-7 days')));
    $to = parse_date_or_default($_GET['to'] ?? '', date('Y-m-d'));

    // max 31 days
    $fromTs = strtotime($from);
    $toTs = strtotime($to);
    if ($fromTs === false || $toTs === false) {
        $from = date('Y-m-d', strtotime('-7 days'));
        $to = date('Y-m-d');
        $fromTs = strtotime($from);
        $toTs = strtotime($to);
    }
    if ($toTs < $fromTs) {
        // swap
        $tmp = $from; $from = $to; $to = $tmp;
        $tmp2 = $fromTs; $fromTs = $toTs; $toTs = $tmp2;
    }
    $days = (int)floor(($toTs - $fromTs) / 86400) + 1;
    if ($days > 31) {
        $from = date('Y-m-d', strtotime('-30 days', $toTs));
        $fromTs = strtotime($from);
        $days = 31;
    }

    $lab = safe_lab($_GET['lab'] ?? null);
    $userId = safe_int($_GET['user_id'] ?? null);
    $username = safe_username($_GET['username'] ?? null);
    $success = safe_success($_GET['success'] ?? null);

    $dir = attempts_storage_dir();
    send_csv_headers('attempts_' . $from . '_to_' . $to . '.csv');
    $out = fopen('php://output', 'w');

    csv_out_row($out, ['ts','user_id','username','lab','success','input_preview','input_hash']);

    for ($i = 0; $i < $days; $i++) {
        $d = date('Y-m-d', strtotime("+$i day", $fromTs));
        $path = $dir . DIRECTORY_SEPARATOR . $d . '.ndjson';
        if (!is_file($path)) continue;

        $fp = fopen($path, 'rb');
        if (!$fp) continue;

        while (($line = fgets($fp)) !== false) {
            $line = trim($line);
            if ($line === '') continue;

            $obj = json_decode($line, true);
            if (!is_array($obj)) continue;

            $objUserId = (int)($obj['user_id'] ?? 0);
            $objUsername = (string)($obj['username'] ?? '');
            $objLab = (string)($obj['lab'] ?? '');
            $objSuccess = (int)($obj['success'] ?? 0);

            if ($lab !== null && $objLab !== $lab) continue;
            if ($userId !== null && $objUserId !== $userId) continue;
            if ($username !== null && mb_strtolower($objUsername) !== mb_strtolower($username)) continue;
            if ($success !== null && $objSuccess !== $success) continue;

            csv_out_row($out, [
                (string)($obj['ts'] ?? ''),
                $objUserId,
                $objUsername,
                $objLab,
                $objSuccess,
                (string)($obj['input_preview'] ?? ''),
                (string)($obj['input_hash'] ?? ''),
            ]);
        }

        fclose($fp);
    }

    fclose($out);
    exit;
}

// handle actions
$action = $_GET['action'] ?? '';
if ($action === 'users') {
    export_users_csv($conn);
}
if ($action === 'attempts') {
    export_attempts_csv();
}

bs_layout_start('Admin – Export');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <div class="d-flex justify-content-between align-items-start">
      <div>
        <h1 class="h4 fw-bold mb-1">Експорт (CSV)</h1>
        <p class="text-secondary mb-0">Изтегляне на данни за потребители и опити.</p>
      </div>
      <a class="btn btn-outline-secondary" href="index.php">← Админ</a>
    </div>

    <hr>

    <h2 class="h6 fw-bold">1) Users + Progress + Aggregates (DB)</h2>
    <p class="text-secondary small mb-2">Един ред на потребител: прогрес + агрегирани опити.</p>
    <a class="btn btn-brand" href="export.php?action=users">⬇️ Export users_progress.csv</a>

    <hr class="my-4">

    <h2 class="h6 fw-bold">2) Attempts (NDJSON → CSV)</h2>
    <p class="text-secondary small">
      По подразбиране: последните 7 дни. Лимит: максимум 31 дни сканиране.
    </p>

    <form class="row g-2" method="get">
      <input type="hidden" name="action" value="attempts">

      <div class="col-12 col-md-3">
        <label class="form-label">From (YYYY-MM-DD)</label>
        <input class="form-control" name="from" value="<?php echo htmlspecialchars($_GET['from'] ?? ''); ?>" placeholder="2026-01-01">
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">To (YYYY-MM-DD)</label>
        <input class="form-control" name="to" value="<?php echo htmlspecialchars($_GET['to'] ?? ''); ?>" placeholder="2026-01-20">
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Lab (optional)</label>
        <input class="form-control" name="lab" value="<?php echo htmlspecialchars($_GET['lab'] ?? ''); ?>" placeholder="lab3_practice">
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Success (optional)</label>
        <select class="form-select" name="success">
          <option value="" <?php echo (($_GET['success'] ?? '') === '') ? 'selected' : ''; ?>>All</option>
          <option value="1" <?php echo (($_GET['success'] ?? '') === '1') ? 'selected' : ''; ?>>✅ Only success</option>
          <option value="0" <?php echo (($_GET['success'] ?? '') === '0') ? 'selected' : ''; ?>>❌ Only failed</option>
        </select>
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">User ID (optional)</label>
        <input class="form-control" name="user_id" value="<?php echo htmlspecialchars($_GET['user_id'] ?? ''); ?>" placeholder="5">
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Username (optional)</label>
        <input class="form-control" name="username" value="<?php echo htmlspecialchars($_GET['username'] ?? ''); ?>" placeholder="ivan">
      </div>

      <div class="col-12 col-md-auto d-flex align-items-end">
        <button class="btn btn-brand" type="submit">⬇️ Export attempts.csv</button>
      </div>
    </form>

    <div class="small text-secondary mt-3">
      Забележка: “Lab” филтър очаква стойности като <code>lab1_practice</code>, <code>lab2_practice</code> и т.н.
    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
