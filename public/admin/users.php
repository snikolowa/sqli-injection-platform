<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_admin();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout_bs.php';

function lab_codes(): array {
    return [
        'LAB0_INTRO',
        'LAB1_AUTH_BYPASS',
        'LAB2_BOOLEAN_BLIND',
        'LAB3_UNION_BASED',
        'LAB4_ERROR_BASED',
        'LAB5_TIME_BASED',
    ];
}
$totalLabs = count(lab_codes());

$q = trim($_GET['q'] ?? '');
$filter = trim($_GET['filter'] ?? ''); // "inactive" later

// users + progress count + agg totals
$sql = "
  SELECT
    u.id,
    u.username,
    COALESCE(u.email, '') AS email,
    COALESCE(u.role, 'user') AS role,
    COALESCE(p.completed_count, 0) AS completed_count,
    COALESCE(a.attempts_total, 0) AS attempts_total,
    COALESCE(a.success_total, 0) AS success_total,
    a.last_attempt_at
  FROM users u
  LEFT JOIN (
    SELECT user_id, COUNT(*) AS completed_count
    FROM user_progress
    WHERE completed = 1
    GROUP BY user_id
  ) p ON p.user_id = u.id
  LEFT JOIN attempts_agg_user a ON a.user_id = u.id
  WHERE 1=1
";

$params = [];
$types = "";

if ($q !== '') {
    $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like;
    $types .= "ss";
}

$sql .= " ORDER BY u.id DESC";

$stmt = mysqli_prepare($conn, $sql);
$users = [];

if ($stmt) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $users[] = $row;
    }
    mysqli_stmt_close($stmt);
}

bs_layout_start('Admin – Users');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Потребители и прогрес</h1>
        <p class="text-secondary mb-0">Показва агрегати от опитите и завършени модули.</p>
      </div>
      <a class="btn btn-outline-secondary" href="index.php">← Админ</a>
    </div>

    <hr>

    <form class="row g-2 align-items-end mb-3" method="get">
      <div class="col-12 col-md-6">
        <label class="form-label">Търси</label>
        <input class="form-control" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="username или email">
      </div>
      <div class="col-12 col-md-auto">
        <button class="btn btn-brand" type="submit">Search</button>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>User</th>
            <th>Role</th>
            <th>Progress</th>
            <th>Attempts</th>
            <th>Success</th>
            <th>Last activity</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <?php
            $cc = (int)$u['completed_count'];
            $pct = $totalLabs > 0 ? round(($cc / $totalLabs) * 100) : 0;
          ?>
          <tr>
            <td>
              <div class="fw-semibold"><?php echo htmlspecialchars($u['username']); ?></div>
              <?php if (!empty($u['email'])): ?>
                <div class="small text-secondary"><?php echo htmlspecialchars($u['email']); ?></div>
              <?php endif; ?>
            </td>
            <td><span class="badge text-bg-<?php echo ($u['role']==='admin')?'danger':'secondary'; ?>">
              <?php echo htmlspecialchars($u['role']); ?>
            </span></td>
            <td><?php echo $cc . " / " . $totalLabs; ?> (<?php echo $pct; ?>%)</td>
            <td><?php echo (int)$u['attempts_total']; ?></td>
            <td><?php echo (int)$u['success_total']; ?></td>
            <td class="small text-secondary">
              <?php echo $u['last_attempt_at'] ? htmlspecialchars($u['last_attempt_at']) : '—'; ?>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary"
                 href="user.php?id=<?php echo (int)$u['id']; ?>">
                View
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
