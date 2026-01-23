<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$username = preg_replace('/\s+/', ' ', $username);
$username = mb_strtolower($username, 'UTF-8');

if ($username === '' || $password === '') {
    header("Location: login.php?error=1");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, username, role, password FROM users WHERE username = ?");
if (!$stmt) {
    header("Location: login.php?error=1");
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($res)) {
    $hash = $row['password'] ?? '';

    if ($hash !== '' && password_verify($password, $hash)) {
        $_SESSION['user_id'] = (int)$row['id'];
        $_SESSION['username'] = $row['username'] ?? $username;
        $_SESSION['role'] = $row['role'] ?? 'user';

        mysqli_stmt_close($stmt);

        header("Location: dashboard.php");
        exit;
    }
}

mysqli_stmt_close($stmt);
header("Location: login.php?error=1");
exit;
