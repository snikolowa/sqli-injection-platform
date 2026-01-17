<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout_bs.php';

$LAB_CODE = "LAB2_BOOLEAN_BLIND";
$userId = (int)($_SESSION['user_id'] ?? 0);

$message = "";
$resultLabel = "";
$completedNow = false;

$condition = "";

function normalize_condition(string $s): string {
    $s = strtolower($s);
    $s = preg_replace('/\s+/', '', $s);
    return $s ?? "";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $condition = trim($_POST['condition'] ?? '');

    // УЯЗВИМА: директно вграждане на условие (учебна среда)
    $sql = "SELECT IF(($condition), 1, 0) AS ok";
    $res = mysqli_query($conn, $sql);

    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $ok = isset($row['ok']) ? (int)$row['ok'] : 0;

        if ($ok === 1) {
            $resultLabel = "TRUE ✅";
        } else {
            $resultLabel = "FALSE ❌";
        }

        // Условие за “решено” (учебна цел):
        // Потвърждаваме, че LENGTH(admin password) = 8
        // (админ паролата в твоята база е admin123 -> 8 символа)
        $norm = normalize_condition($condition);
        $looksRight =
            str_contains($norm, "length(") &&
            str_contains($norm, "selectpasswordfromuserswhereusername='admin'") &&
            (str_contains($norm, "=8") || str_contains($norm, ")=8"));

        if ($ok === 1 && $looksRight) {
            $completedNow = true;
            $message = "🎉 Успешно! Потвърди boolean-based, че дължината на паролата на admin е 8.";
        } else {
            $message = "Резултат: $resultLabel";
        }

    } else {
        $resultLabel = "SQL ERROR (невалидно условие)";
        $message = "Резултат: $resultLabel";
    }

    // Логване (attempts)
    $lab = "lab2_practice";
    $mode = "vuln";
    $successInt = $completedNow ? 1 : 0;

    $stmtLog = mysqli_prepare(
        $conn,
        "INSERT INTO attempts (lab, mode, username_input, success)
         VALUES (?, ?, ?, ?)"
    );
    if ($stmtLog) {
        mysqli_stmt_bind_param($stmtLog, "sssi", $lab, $mode, $condition, $successInt);
        mysqli_stmt_execute($stmtLog);
        mysqli_stmt_close($stmtLog);
    }

    // user_progress
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

bs_layout_start('Lab 2 – Practice');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Lab 2: Practice – Boolean-based Blind</h1>
        <p class="text-secondary mb-0">
          Цел: потвърди чрез TRUE/FALSE реакция, че <strong>дължината на паролата на admin е 8</strong>.
          При успех Lab 2 се маркира автоматично като Completed.
        </p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Lab 2</span>
    </div>

    <hr>

    <div class="btn-group mb-3" role="group">
      <a class="btn btn-outline-primary" href="step1.php">Step 1</a>
      <a class="btn btn-outline-primary" href="step2.php">Step 2</a>
      <a class="btn btn-success" href="practice.php">Practice</a>
    </div>

    <?php if ($message): ?>
      <div class="alert <?php echo $completedNow ? 'alert-success' : 'alert-secondary'; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="post" class="mt-3" autocomplete="off">
      <label class="form-label fw-semibold">Въведи SQL условие (boolean въпрос):</label>
      <input type="text" name="condition" class="form-control" required
             value="<?php echo htmlspecialchars($condition); ?>"
             placeholder="(пример: условие, което връща TRUE или FALSE)">
      <div class="d-flex flex-wrap gap-2 mt-3">
        <button type="submit" class="btn btn-brand">Test</button>
        <a href="/sqli-platform/public/profile.php" class="btn btn-outline-secondary">Виж профил</a>
      </div>
    </form>

    <!-- Button to show hints -->
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
      <div class="accordion" id="lab2Hints">

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#h2_1">
              Подсказка 1: Какво трябва да върне условието?
            </button>
          </h2>
          <div id="h2_1" class="accordion-collapse collapse" data-bs-parent="#lab2Hints">
            <div class="accordion-body text-secondary">
              Условието трябва да е логически израз, който базата може да оцени като TRUE или FALSE
              (например сравнение, проверка на дължина, проверка на символ).
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#h2_2">
              Подсказка 2: Какво точно е целта тук?
            </button>
          </h2>
          <div id="h2_2" class="accordion-collapse collapse" data-bs-parent="#lab2Hints">
            <div class="accordion-body text-secondary">
              Трябва да потвърдиш факт за данните: че паролата на потребителя <strong>admin</strong> е с дължина 8.
              Това е “yes/no” въпрос към базата.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#h2_3">
              Подсказка 3: Как се взима стойност за проверка?
            </button>
          </h2>
          <div id="h2_3" class="accordion-collapse collapse" data-bs-parent="#lab2Hints">
            <div class="accordion-body text-secondary">
              Обикновено първо “избираш” стойност (например паролата на admin) и после проверяваш нещо за нея
              (дължина, символ на позиция и т.н.). Точно това е логиката при blind техниките.
            </div>
          </div>
        </div>

      </div>
    </div>

    <?php if ($completedNow): ?>
      <div class="alert alert-success mt-4">
        ✅ Lab 2 е завършен и прогресът е записан в профила ти.
      </div>
    <?php endif; ?>

    <div class="small text-secondary mt-4">
      ⚠️ Тази страница е умишлено уязвима и е предназначена само за обучение.
    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
