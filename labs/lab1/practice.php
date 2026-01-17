<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout_bs.php';

$LAB_CODE = "LAB1_AUTH_BYPASS";

$message = "";
$completedNow = false;

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // ❌ УЯЗВИМА SQL ЗАЯВКА (умишлено)
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        if (isset($row['username']) && $row['username'] === 'admin') {
            $message = "🎉 Успешно! Получи достъп като admin.";
            $completedNow = true;
        } else {
            $message = "Влезе успешно, но не като admin. Целта е достъп като admin.";
        }
    } else {
        $message = "Невалидни данни или неуспешен опит.";
    }

    // ЛОГВАНЕ (attempts)
    $lab = "lab1_practice";
    $mode = "vuln";
    $successInt = $completedNow ? 1 : 0;

    $stmtLog = mysqli_prepare(
        $conn,
        "INSERT INTO attempts (lab, mode, username_input, success)
         VALUES (?, ?, ?, ?)"
    );
    if ($stmtLog) {
        mysqli_stmt_bind_param($stmtLog, "sssi", $lab, $mode, $username, $successInt);
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

bs_layout_start('Lab 1 – Practice');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Lab 1: Practice – Authentication Bypass</h1>
        <p class="text-secondary mb-0">
          Цел: да получиш достъп като <strong>admin</strong>. При успех задачата се отбелязва автоматично като мината.
        </p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Lab 1</span>
    </div>

    <hr>

    <!-- Navigation -->
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

    <!-- Login form -->
    <form method="post" class="row g-3 mt-2" autocomplete="off">
      <div class="col-12 col-md-6">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required>
      </div>

      <div class="col-12 col-md-6">
        <label class="form-label">Password</label>
        <input type="text" name="password" class="form-control" required>
      </div>

      <div class="col-12 d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-brand">Test</button>
        <a href="/sqli-platform/public/profile.php" class="btn btn-outline-secondary">
          Виж профил
        </a>
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
      <div class="accordion" id="lab1Hints">

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#hint1">
              Подсказка 1: Какво трябва да върне SQL заявката?
            </button>
          </h2>
          <div id="hint1" class="accordion-collapse collapse" data-bs-parent="#lab1Hints">
            <div class="accordion-body text-secondary">
              Приложението счита логина за успешен, ако SQL заявката върне поне един ред.
              Целта е този ред да бъде за потребителя <strong>admin</strong>.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#hint2">
              Подсказка 2: Каква е ролята на OR?
            </button>
          </h2>
          <div id="hint2" class="accordion-collapse collapse" data-bs-parent="#lab1Hints">
            <div class="accordion-body text-secondary">
              <code>OR</code> е логически оператор.
              Ако едно от условията е винаги вярно, цялото WHERE условие може да стане вярно.
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#hint3">
              Подсказка 3: Какво правят SQL коментарите (-- )
            </button>
          </h2>
          <div id="hint3" class="accordion-collapse collapse" data-bs-parent="#lab1Hints">
            <div class="accordion-body text-secondary">
              SQL коментарите могат да направят така, че част от заявката да бъде игнорирана.
              В MySQL често се използва <code>-- </code> (с интервал).
            </div>
          </div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#hint4">
              Подсказка 4: Ако не става?
            </button>
          </h2>
          <div id="hint4" class="accordion-collapse collapse" data-bs-parent="#lab1Hints">
            <div class="accordion-body text-secondary">
              Помисли:
              <ul class="mt-2">
                <li>Коя част от WHERE условието можеш да контролираш?</li>
                <li>Как можеш да направиш условието вярно?</li>
                <li>Коя проверка би могла да бъде игнорирана?</li>
              </ul>
            </div>
          </div>
        </div>

      </div>
    </div>

    <?php if ($completedNow): ?>
      <div class="alert alert-success mt-4">
        ✅ Lab 1 е успешно завършен и е записан в профила ти.
      </div>
    <?php endif; ?>

    <div class="small text-secondary mt-4">
      ⚠️ Тази страница е умишлено уязвима и е предназначена само за обучение.
    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
