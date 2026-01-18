<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/lab_gate.php';
require_once __DIR__ . '/../../includes/layout_bs.php';
require_once __DIR__ . '/../../includes/modules.php';

$LAB_CODE = "LAB3_UNION_BASED";
$userId = (int)($_SESSION['user_id'] ?? 0);
require_prereq_or_block($conn, $userId, 'LAB2_BOOLEAN_BLIND');

$q = '';
$message = '';
$completedNow = false;
$rows = [];
$next = get_next_module($LAB_CODE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $q = trim($_POST['q'] ?? '');

    $sql = "SELECT name, description FROM products WHERE name LIKE '%$q%'";
    $res = mysqli_query($conn, $sql);

    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = $r;
        }

        if (count($rows) === 0) {
            $message = "Няма резултати.";
        } else {
            $message = "Намерени резултати: " . count($rows);
        }

        foreach ($rows as $r) {
            $n = strtolower((string)($r['name'] ?? ''));
            $d = strtolower((string)($r['description'] ?? ''));
            if (str_contains($n, 'admin') || str_contains($d, 'admin')) {
                $completedNow = true;
                $message = "🎉 Успешно! В резултатите се появи 'admin'.";
                break;
            }
        }

    } else {
        $message = "Възникна грешка при търсенето. Опитай с различна заявка.";
    }

    $lab = "lab3_practice";
    $mode = "vuln";
    $successInt = $completedNow ? 1 : 0;

    $stmtLog = mysqli_prepare(
        $conn,
        "INSERT INTO attempts (lab, mode, username_input, success)
         VALUES (?, ?, ?, ?)"
    );
    if ($stmtLog) {
        mysqli_stmt_bind_param($stmtLog, "sssi", $lab, $mode, $q, $successInt);
        mysqli_stmt_execute($stmtLog);
        mysqli_stmt_close($stmtLog);
    }

    if ($completedNow && $userId > 0) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO user_progress (user_id, lab_code, completed, completed_at)
            VALUES (?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE completed = 1, completed_at = NOW()
        ");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "is", $userId, $LAB_CODE);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

bs_layout_start('Lab 3 – Practice');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Модул 3: Practice – UNION-based SQLi</h1>
        <p class="text-secondary mb-0">
          Цел: чрез уязвимата търсачка да направиш така, че в резултатите да се появи <strong>admin</strong>.
          При успех се отбелязва автоматично.
        </p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Lab 3</span>
    </div>

    <hr>

    <div class="btn-group mb-3" role="group">
      <a class="btn btn-outline-primary" href="step1.php">Урок</a>
      <a class="btn btn-outline-primary" href="step2.php">Примери</a>
      <a class="btn btn-success" href="practice.php">Упражнение</a>
    </div>

    <?php if ($message): ?>
      <div class="alert <?php echo $completedNow ? 'alert-success' : 'alert-secondary'; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="post" class="mt-3" autocomplete="off">
      <label class="form-label fw-semibold">Search (име на продукт):</label>
      <input type="text" name="q" class="form-control" required
             value="<?php echo htmlspecialchars($q); ?>"
             placeholder="Пример: Phone">
      <div class="d-flex flex-wrap gap-2 mt-3">
        <button type="submit" class="btn btn-brand">Search</button>
      </div>
    </form>

    <?php if (count($rows) > 0): ?>
      <div class="table-responsive mt-4">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Name</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars((string)($r['name'] ?? '')); ?></td>
              <td><?php echo htmlspecialchars((string)($r['description'] ?? '')); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <!-- Hints button -->
    <div class="mt-4">
      <button class="btn btn-outline-info"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#hintsSection"
              aria-expanded="false"
              aria-controls="hintsSection">
        💡 Покажи подсказки
      </button>
    </div>

    <!-- Hidden hints -->
    <div class="collapse mt-3" id="hintsSection">
      <div class="accordion" id="lab3Hints">

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#l3h1">
              Подсказка 1: Колко колони виждаш на екрана?
            </button>
          </h2>
          <div id="l3h1" class="accordion-collapse collapse" data-bs-parent="#lab3Hints">
            <div class="accordion-body text-secondary">
              Таблицата показва 2 колони (Name и Description). При UNION частта трябва да “паснеш” същия брой колони.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#l3h2">
              Подсказка 2: Какво означава успех в този lab?
            </button>
          </h2>
          <div id="l3h2" class="accordion-collapse collapse" data-bs-parent="#lab3Hints">
            <div class="accordion-body text-secondary">
              Успехът се отчита, ако в резултатите (в някоя от двете колони) се появи текстът “admin”.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#l3h3">
              Подсказка 3: Ако има много резултати?
            </button>
          </h2>
          <div id="l3h3" class="accordion-collapse collapse" data-bs-parent="#lab3Hints">
            <div class="accordion-body text-secondary">
              Пробвай по-специфично търсене, за да намалиш резултатите и да се вижда по-лесно добавеният ред.
            </div>
          </div>
        </div>

      </div>
    </div>

    <?php if ($completedNow): ?>
      <div class="alert alert-success mt-4">
        ✅ Модулът е успешно завършен и е записан в профила ти.
      </div>

        <?php if (!empty($next)): ?>
          <div class="d-flex justify-content-end mt-3">
            <a class="btn btn-brand" href="<?php echo htmlspecialchars($next['path']); ?>">
              Към <?php echo htmlspecialchars($next['label']); ?> →
            </a>
          </div>
        <?php else: ?>
          <div class="alert alert-info mt-3 mb-0">
            🎉 Това беше последният модул!
          </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="small text-secondary mt-4">
      ⚠️ Тази страница е умишлено уязвима и е предназначена само за обучение.
    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
