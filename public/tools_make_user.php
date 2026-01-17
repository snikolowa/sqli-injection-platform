<?php
// !!! Използвай само локално и после изтрий файла !!!
require_once '../includes/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn, "INSERT INTO platform_users (username, password_hash) VALUES (?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $username, $hash);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $message = $ok ? "Потребителят е създаден успешно." : "Грешка: потребителското име може да е заето.";
        } else {
            $message = "Database error.";
        }
    } else {
        $message = "Попълни username и password.";
    }
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
  <meta charset="UTF-8" />
  <title>Create User (Local Tool)</title>
</head>
<body>
  <h1>Local Tool: Create Platform User</h1>
  <p><strong>Важно:</strong> След като си създадеш потребител(и), изтрий този файл.</p>

  <?php if ($message): ?>
    <p><?php echo htmlspecialchars($message); ?></p>
  <?php endif; ?>

  <form method="post">
    <label>Username:</label><br>
    <input name="username" required><br><br>
    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>
    <button type="submit">Create</button>
  </form>

  <p><a href="login.php">Отиди към Login</a></p>
</body>
</html>
