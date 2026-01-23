<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout_bs.php';
require_once __DIR__ . '/../includes/points.php';

$base = base_url();
$userId = (int)($_SESSION['user_id'] ?? 0);
$isAdmin = function_exists('is_admin') ? is_admin() : false;

$flash = $_SESSION['flash_ctf'] ?? null;
unset($_SESSION['flash_ctf']);

$points = points_get_user_points($conn, $userId);

// Load challenges
$challenges = [];
$res = mysqli_query($conn, "SELECT id, lab_code, slug, title, points_base, is_active FROM challenges WHERE is_active=1 ORDER BY id ASC");
if ($res) {
  while ($row = mysqli_fetch_assoc($res)) {
    $challenges[] = $row;
  }
}

// Solved map
$solved = [];
$stmt = mysqli_prepare($conn, "SELECT challenge_id, solved_at, attempts_used, points_awarded FROM user_challenge_solves WHERE user_id = ?");
if ($stmt) {
  mysqli_stmt_bind_param($stmt, "i", $userId);
  mysqli_stmt_execute($stmt);
  $r = mysqli_stmt_get_result($stmt);
  while ($r && ($row = mysqli_fetch_assoc($r))) {
    $solved[(int)$row['challenge_id']] = $row;
  }
  mysqli_stmt_close($stmt);
}

bs_layout_start('CTF • Flags');
?>

<?php if ($flash): ?>
  <div class="alert alert-<?php echo htmlspecialchars($flash['type'] ?? 'info'); ?> rounded-4">
    <?php echo htmlspecialchars($flash['msg'] ?? ''); ?>
  </div>
<?php endif; ?>

<div class="p-4 bg-white rounded-4 shadow-sm border mb-4">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
      <h1 class="h3 fw-bold mb-1">CTF • Flags</h1>
      <p class="text-secondary mb-0">Въведи флаг след като решиш упражнението. Точките се начисляват автоматично.</p>
    </div>
    <div class="text-end">
      <div class="badge text-bg-dark rounded-pill px-3 py-2">Points: <?php echo (int)$points; ?></div>
      <div class="small text-secondary mt-1">Penalty: -2/доп. опит</div>
    </div>
  </div>
</div>

<?php if (empty($challenges)): ?>
  <div class="text-secondary">Няма активни challenges.</div>
<?php else: ?>

  <div class="row g-3">
    <?php foreach ($challenges as $c): ?>
      <?php
        $cid = (int)($c['id'] ?? 0);
        $isSolved = isset($solved[$cid]);
        $solveInfo = $solved[$cid] ?? null;
      ?>
      <div class="col-12 col-lg-6">
        <div class="card shadow-sm h-100">
          <div class="card-body">

            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="text-secondary small"><?php echo htmlspecialchars((string)($c['lab_code'] ?? '')); ?></div>
                <div class="h6 fw-bold mb-1"><?php echo htmlspecialchars((string)($c['title'] ?? '')); ?></div>
                <div class="small text-secondary">Base points: <strong><?php echo (int)($c['points_base'] ?? 0); ?></strong></div>
              </div>

              <?php if ($isSolved): ?>
                <span class="badge text-bg-success rounded-pill">Solved</span>
              <?php else: ?>
                <span class="badge text-bg-primary rounded-pill">Open</span>
              <?php endif; ?>
            </div>

            <hr>

            <?php if ($isSolved): ?>
              <div class="small text-secondary">
                Solved at: <strong><?php echo htmlspecialchars((string)($solveInfo['solved_at'] ?? '')); ?></strong><br>
                Attempts used: <strong><?php echo (int)($solveInfo['attempts_used'] ?? 1); ?></strong><br>
                Points awarded: <strong><?php echo (int)($solveInfo['points_awarded'] ?? 0); ?></strong>
              </div>
            <?php else: ?>
              <form method="post" action="<?php echo $base; ?>/public/process_flag.php" class="mt-2">
                <input type="hidden" name="challenge_id" value="<?php echo (int)$cid; ?>">
                <div class="mb-2">
                  <label class="form-label">Flag</label>
                  <input class="form-control" name="flag" placeholder="SQLI{...}" required>
                </div>
                <button class="btn btn-brand" type="submit">Submit</button>
                <div class="small text-secondary mt-2">
                  Всеки submit се брои като опит. Точките намаляват при много опити.
                </div>
              </form>
            <?php endif; ?>

          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<?php if ($isAdmin): ?>
  <div class="card shadow-sm mt-4">
    <div class="card-body">
      <h2 class="h6 fw-bold">Admin/Dev helper</h2>
      <p class="text-secondary small mb-2">
        Генериране на hash за флаг (копираш резултата в challenges.flag_hash).
      </p>

      <form method="get" class="row g-2">
        <div class="col-12 col-md-6">
          <input class="form-control" name="debug_flag" placeholder="SQLI{your-flag}">
        </div>
        <div class="col-12 col-md-auto">
          <button class="btn btn-outline-secondary" type="submit">Generate hash</button>
        </div>
      </form>

      <?php if (!empty($_GET['debug_flag'])): ?>
        <div class="mt-3">
          <div class="small text-secondary">Hash:</div>
          <code><?php echo htmlspecialchars(points_hash_flag((string)$_GET['debug_flag'])); ?></code>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php bs_layout_end(); ?>
