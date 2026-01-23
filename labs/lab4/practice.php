<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/lab_gate.php';
require_once __DIR__ . '/../../includes/layout_bs.php';
require_once __DIR__ . '/../../includes/modules.php';
require_once __DIR__ . '/../../includes/attempt_logger.php';

$LAB_CODE = "LAB4_ERROR_BASED";

$message = "";
$completedNow = false;
$next = get_next_module($LAB_CODE);

$userId = (int)($_SESSION['user_id'] ?? 0);
$usernameSess = (string)($_SESSION['username'] ?? '');
require_prereq_or_block($conn, $userId, 'LAB3_UNION_BASED');

$input = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST['input'] ?? '';

    $sql = "SELECT * FROM users WHERE username = '$input'";
    $result = mysqli_query($conn, $sql);

    if ($result === false) {
        // ✅ Error-based success: предизвикана SQL грешка
        $completedNow = true;
        $message = "🎉 Успешно! Предизвика SQL грешка (error-based сигнал).";
    } else {
        if (mysqli_num_rows($result) > 0) {
            $message = "✅ Има резултат. Провери дали покри целта на Lab 4.";
        } else {
            $message = "Няма резултат или неуспешен опит.";
        }
    }

    // ✅ Log attempt to file + aggregates
    $lab = "lab4_practice";
    $successInt = $completedNow ? 1 : 0;
    log_attempt($conn, $userId, $usernameSess, $lab, $successInt, (string)$input);

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

bs_layout_start('Lab 4 – Practice');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Модул 4: Practice – Error-based SQLi</h1>
        <p class="text-secondary mb-0">
          Цел: предизвикай error-based поведение (SQL грешка) в уязвимата заявка.
          При успех задачата се отбелязва автоматично като мината.
        </p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Модул 4</span>
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

    <!-- Practice form -->
    <form method="post" class="row g-3 mt-2" autocomplete="off">
      <div class="col-12">
        <label class="form-label">Input</label>
        <input type="text" name="input" class="form-control" required value="<?php echo htmlspecialchars($input); ?>">
        <div class="form-text">
          Това поле се използва в SQL заявка (умишлено уязвимо, учебна среда).
        </div>
      </div>

      <div class="col-12 d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-brand">Провери</button>
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
      <!-- IMPORTANT: data-hints enables timed hints -->
      <div class="accordion" id="lab4Hints" data-hints>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#lab4_hint1"
                    data-hint-unlock="300" disabled>
              Подсказка 1: Как мислиш за входа?
              <span class="ms-2 small text-secondary" data-hint-countdown></span>
            </button>
          </h2>
          <div id="lab4_hint1" class="accordion-collapse collapse" data-bs-parent="#lab4Hints">
            <div class="accordion-body text-secondary">
              Помисли къде точно се използва въведеното и какъв тип данни очаква (текст/число).
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#lab4_hint2"
                    data-hint-unlock="600" disabled>
              Подсказка 2: Какво е “успех”?
              <span class="ms-2 small text-secondary" data-hint-countdown></span>
            </button>
          </h2>
          <div id="lab4_hint2" class="accordion-collapse collapse" data-bs-parent="#lab4Hints">
            <div class="accordion-body text-secondary">
              При error-based техниките “успех” е когато уязвимата заявка започне да връща грешка/информация
              заради обработката на входа.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#lab4_hint3"
                    data-hint-unlock="900" disabled>
              Подсказка 3: Ако не става?
              <span class="ms-2 small text-secondary" data-hint-countdown></span>
            </button>
          </h2>
          <div id="lab4_hint3" class="accordion-collapse collapse" data-bs-parent="#lab4Hints">
            <div class="accordion-body text-secondary">
              Пробвай различни стойности и наблюдавай дали поведението се променя (например грешка вместо празен резултат).
              При error-based целта е да “провокираш” грешка.
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
