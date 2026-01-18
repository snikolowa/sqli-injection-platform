<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/lab_gate.php';
require_once __DIR__ . '/../../includes/layout_bs.php';
require_once __DIR__ . '/../../includes/modules.php';

$LAB_CODE = "LAB4_ERROR_BASED"; 

$message = "";
$completedNow = false;
$next = get_next_module($LAB_CODE);

$userId = (int)($_SESSION['user_id'] ?? 0);
require_prereq_or_block($conn, $userId, 'LAB3_UNION_BASED');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST['input'] ?? '';

    $sql = "SELECT * FROM users WHERE username = '$input'"; 
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $message = "✅ Има резултат. Провери дали покри целта на Lab 4.";
    } else {
        $message = "Няма резултат или неуспешен опит.";
    }

    $lab = "lab4_practice";
    $mode = "vuln";
    $successInt = $completedNow ? 1 : 0;

    $stmtLog = mysqli_prepare(
        $conn,
        "INSERT INTO attempts (lab, mode, username_input, success)
         VALUES (?, ?, ?, ?)"
    );
    if ($stmtLog) {
        mysqli_stmt_bind_param($stmtLog, "sssi", $lab, $mode, $input, $successInt);
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

bs_layout_start('Lab 4 – Practice');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Модул 4: Practice</h1>
        <p class="text-secondary mb-0">
          Цел: изпълни условието на Модул 4. При успех задачата се отбелязва автоматично като мината.
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

    <!-- Practice form -->
    <form method="post" class="row g-3 mt-2" autocomplete="off">
      <div class="col-12">
        <label class="form-label">Input</label>
        <input type="text" name="input" class="form-control" required>
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
      <div class="accordion" id="lab4Hints">

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#lab4_hint1">
              Подсказка 1: Как мислиш за входа?
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
                    data-bs-toggle="collapse" data-bs-target="#lab4_hint2">
              Подсказка 2: Какво е “успех”?
            </button>
          </h2>
          <div id="lab4_hint2" class="accordion-collapse collapse" data-bs-parent="#lab4Hints">
            <div class="accordion-body text-secondary">
              Успехът е конкретен резултат според условието на Lab 4 (например конкретен ред/поле).
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#lab4_hint3">
              Подсказка 3: Ако не става?
            </button>
          </h2>
          <div id="lab4_hint3" class="accordion-collapse collapse" data-bs-parent="#lab4Hints">
            <div class="accordion-body text-secondary">
              Провери как приложението обработва входа: дали има грешки, празни резултати,
              или различно поведение при различни стойности.
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
