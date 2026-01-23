<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/lab_gate.php';
require_once __DIR__ . '/../../includes/layout_bs.php';
require_once __DIR__ . '/../../includes/modules.php';
require_once __DIR__ . '/../../includes/attempt_logger.php';
require_once __DIR__ . '/../../includes/points.php';

$LAB_CODE = "LAB5_TIME_BASED";
$userId = (int)($_SESSION['user_id'] ?? 0);
$usernameSess = (string)($_SESSION['username'] ?? '');
require_prereq_or_block($conn, $userId, 'LAB4_ERROR_BASED');

$message = "";
$resultLabel = "";
$completedNow = false;
$next = get_next_module($LAB_CODE);

$condition = "";

function normalize_condition(string $s): string {
    $s = strtolower($s);
    $s = preg_replace('/\s+/', '', $s);
    return $s ?? "";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $condition = trim($_POST['condition'] ?? '');

    $sql = "SELECT IF(($condition), SLEEP(2), 0) AS r";

    $start = microtime(true);
    $res = mysqli_query($conn, $sql);
    $elapsed = microtime(true) - $start;
    $isDelayed = ($elapsed >= 1.6);

    if ($res) {
        $resultLabel = $isDelayed ? "DELAYED ✅" : "NO DELAY ❌";
    } else {
        $resultLabel = "SQL ERROR (невалидно условие)";
    }

    $norm = normalize_condition($condition);
    $looksRight =
        str_contains($norm, "substring(password,1,1)='a'") ||
        str_contains($norm, "substr(password,1,1)='a'");

    if ($isDelayed && $looksRight) {
        $completedNow = true;
        $message = "🎉 Успешно! Потвърди чрез time-based подход, че първият символ е 'a'.";
    } else {
        $message = "Резултат: $resultLabel (време: " . number_format($elapsed, 3) . "s)";
    }

    // ✅ Log attempt to file + aggregates
    $lab = "lab5_practice";
    $successInt = $completedNow ? 1 : 0;
    log_attempt($conn, $userId, $usernameSess, $lab, $successInt, (string)$condition);

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

        $awarded = points_award_for_lab_completion($conn, $userId, $LAB_CODE);
        if ($awarded > 0) {
            $message .= " (+{$awarded} точки)";
        }
    }
}

bs_layout_start('Lab 5 – Practice');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Модул 5: Practice – Time-based Blind SQL Injection</h1>
        <p class="text-secondary mb-0">
          Задача: потвърди чрез time-based подход, че <strong>първият символ на паролата на admin е 'a'</strong>.
          Платформата показва само дали има забавяне.
        </p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Модул 5</span>
    </div>

    <hr>

    <!-- Navigation -->
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

    <!-- Used by hints-timer.js: reveal all hints after solving -->
    <div id="exercise-status" data-solved="<?php echo $completedNow ? '1' : '0'; ?>"></div>

    <!-- Form -->
    <form method="post" class="row g-3 mt-2" autocomplete="off">
      <div class="col-12">
        <label class="form-label">Въведи SQL условие</label>
        <input
          type="text"
          name="condition"
          class="form-control"
          value="<?php echo htmlspecialchars($condition); ?>"
          required
        >
        <div class="form-text">
          Подай условие, което се оценява в SQL. Приложението отчита дали има забавяне.
        </div>
      </div>

      <div class="col-12 d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-brand">Провери</button>
      </div>
    </form>

    <!-- Hints -->
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

    <div class="collapse mt-3" id="hintsSection">
      <!-- IMPORTANT: data-hints enables timed hints -->
      <div class="accordion" id="lab5Hints" data-hints>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#lab5_hint1"
                    data-hint-unlock="300" disabled>
              Подсказка 1: Какво измерваме?
              <span class="ms-2 small text-secondary" data-hint-countdown></span>
            </button>
          </h2>
          <div id="lab5_hint1" class="accordion-collapse collapse" data-bs-parent="#lab5Hints">
            <div class="accordion-body text-secondary">
              Ако условието е вярно, заявката умишлено забавя отговора (sleep). Ако е невярно — няма забавяне.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#lab5_hint2"
                    data-hint-unlock="600" disabled>
              Подсказка 2: Какво трябва да “потвърдиш”?
              <span class="ms-2 small text-secondary" data-hint-countdown></span>
            </button>
          </h2>
          <div id="lab5_hint2" class="accordion-collapse collapse" data-bs-parent="#lab5Hints">
            <div class="accordion-body text-secondary">
              Условието трябва да е формулирано така, че да проверява първия символ от паролата на admin.
              При успех ще видиш DELAYED и lab-ът ще се маркира като Completed.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#lab5_hint3"
                    data-hint-unlock="900" disabled>
              Подсказка 3: Как да мислиш за blind проверката?
              <span class="ms-2 small text-secondary" data-hint-countdown></span>
            </button>
          </h2>
          <div id="lab5_hint3" class="accordion-collapse collapse" data-bs-parent="#lab5Hints">
            <div class="accordion-body text-secondary">
              Това е “да/не” въпрос към базата, но сигналът е време. Първо проверяваш факт (TRUE/FALSE),
              после го превръщаш в забавяне чрез <code>IF(условие, SLEEP(2), 0)</code>.
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
      ⚠️ Тази страница е умишлено уязвима и е предназначена само за учебни цели.
    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
