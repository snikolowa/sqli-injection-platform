<?php
ini_set('display_errors', 1);        // махни след тестване
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';

$LAB_CODE = "LAB4_ERROR_BASED";
$userId = (int)($_SESSION['user_id'] ?? 0);

$message = "";
$errorBox = "";
$completedNow = false;

$id = $_GET['id'] ?? '';
$id = (string)$id;

// за auto-check: взимаме истинското име на текущата база
$dbName = "";
$tmp = mysqli_query($conn, "SELECT DATABASE() AS dbname");
if ($tmp && mysqli_num_rows($tmp) > 0) {
    $dbName = mysqli_fetch_assoc($tmp)['dbname'] ?? "";
}

$rows = [];

if ($id !== '') {
    // УЯЗВИМА: директно слагане на параметър в SQL (без валидация)
    $sql = "SELECT id, name, description FROM products WHERE id = $id";
    $res = mysqli_query($conn, $sql);

    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = $r;
        }
        if (count($rows) === 0) {
            $message = "Няма намерен продукт за това id.";
        } else {
            $message = "Намерени резултати: " . count($rows);
        }
    } else {
        // Умишлено показваме грешката (учебна среда)
        $err = mysqli_error($conn);
        $errorBox = $err;

        // Условие за “решено”:
        // да се появи името на базата между ~...~
        if ($dbName !== "" && str_contains($err, "~" . $dbName . "~")) {
            $completedNow = true;
            $message = "🎉 Успешно! Грешката изведе името на базата данни.";
        } else {
            $message = "Има SQL грешка. Опитай да извлечеш името на базата между ~ ~.";
        }
    }

    // Логване (attempts) — записваме само входа (id)
    $lab = "lab4_practice";
    $mode = "vuln";
    $successInt = $completedNow ? 1 : 0;

    $stmtLog = mysqli_prepare(
        $conn,
        "INSERT INTO attempts (lab, mode, username_input, success)
         VALUES (?, ?, ?, ?)"
    );
    if ($stmtLog) {
        mysqli_stmt_bind_param($stmtLog, "sssi", $lab, $mode, $id, $successInt);
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
  <title>Lab 4 - Practice</title>
</head>
<body>
  <nav>
    <a href="/sqli-platform/public/dashboard.php">Dashboard</a> |
    <a href="/sqli-platform/labs/lab4/step1.php">Step 1</a> |
    <a href="/sqli-platform/labs/lab4/step2.php">Step 2</a> |
    <a href="/sqli-platform/public/profile.php">Профил</a> |
    <a href="/sqli-platform/public/logout.php">Logout</a>
  </nav>

  <h1>Lab 4: Practice – Error-based SQL Injection</h1>

  <p>
    <strong>Задача:</strong> Предизвикай SQL грешка, която показва името на текущата база данни
    между символи <strong>~</strong> (пример: <code>~database_name~</code>).
  </p>

  <form method="get" autocomplete="off">
    <label>Product ID (id):</label><br>
    <input type="text" name="id" value="<?php echo htmlspecialchars($id); ?>" style="width: 420px;">
    <button type="submit">Load</button>
  </form>

  <?php if ($message): ?>
    <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
  <?php endif; ?>

  <?php if (count($rows) > 0): ?>
    <h2>Резултат</h2>
    <table border="1" cellpadding="8" cellspacing="0">
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Description</th>
      </tr>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars((string)$r['id']); ?></td>
          <td><?php echo htmlspecialchars($r['name']); ?></td>
          <td><?php echo htmlspecialchars($r['description']); ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <?php if ($errorBox): ?>
    <hr>
    <h2>SQL Error (учебна среда)</h2>
    <pre><code><?php echo htmlspecialchars($errorBox); ?></code></pre>
  <?php endif; ?>

  <?php if ($completedNow): ?>
    <hr>
    <h2>✅ Lab 4 – Completed</h2>
    <p>Задачата е отбелязана като мината и се вижда в профила ти.</p>
  <?php endif; ?>

  <p style="margin-top:16px;">
    Забележка: Лабораторията е умишлено уязвима и е предназначена само за учебни цели.
  </p>
</body>
</html>
