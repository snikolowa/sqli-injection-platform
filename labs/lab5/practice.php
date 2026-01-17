<?php
ini_set('display_errors', 1);        // махни след тестване
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';

$LAB_CODE = "LAB5_TIME_BASED";
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

    // УЯЗВИМА: директно вграждане на условие в SQL
    // Идея: ако условието е вярно => SLEEP(2), иначе 0
    $sql = "SELECT IF(($condition), SLEEP(2), 0) AS r";

    $start = microtime(true);
    $res = mysqli_query($conn, $sql);
    $elapsed = microtime(true) - $start;

    // праг за “забавено” (2 секунди sleep + overhead)
    $isDelayed = ($elapsed >= 1.6);

    if ($res) {
        $resultLabel = $isDelayed ? "DELAYED ✅" : "NO DELAY ❌";
    } else {
        // ако условието е невалидно, пак показваме, че има грешка (но без детайли)
        $resultLabel = "SQL ERROR (невалидно условие)";
    }

    // Условие за “решено”: проверка на първия символ на admin паролата = 'a'
    // (приемаме няколко еквивалентни имена на функцията)
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

    // Логване (attempts) — записваме входа като текст
    $lab = "lab5_practice";
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
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8" />
  <title>Lab 5 - Practice</title>
</head>
<body>
  <nav>
    <a href="/sqli-platform/public/dashboard.php">Dashboard</a> |
    <a href="/sqli-platform/labs/lab5/step1.php">Step 1</a> |
    <a href="/sqli-platform/labs/lab5/step2.php">Step 2</a> |
    <a href="/sqli-platform/public/profile.php">Профил</a> |
    <a href="/sqli-platform/public/logout.php">Logout</a>
  </nav>

  <h1>Lab 5: Practice – Time-based Blind SQL Injection</h1>

  <p>
    <strong>Задача:</strong> Потвърди чрез time-based подход, че
    <strong>първият символ на паролата на admin е 'a'</strong>.
    Платформата ще покаже само дали има забавяне.
  </p>

  <?php if ($message): ?>
    <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <label>Въведи SQL условие:</label><br>
    <input type="text" name="condition" value="<?php echo htmlspecialchars($condition); ?>" required style="width: 520px;"><br><br>
    <button type="submit">Test Condition</button>
  </form>

  <?php if ($completedNow): ?>
    <hr>
    <h2>✅ Lab 5 – Completed</h2>
    <p>Задачата е отбелязана като мината и се вижда в профила ти.</p>
  <?php endif; ?>

  <p style="margin-top:16px;">
    Забележка: Лабораторията е умишлено уязвима и е предназначена само за учебни цели.
  </p>
</body>
</html>
