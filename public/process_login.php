<?php
session_start();
require_once '../includes/db.php';

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($username === '' || $password === '') {
    header("Location: login.php?error=1");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, username, password_hash FROM platform_users WHERE username = ?");
if (!$stmt) {
    // Не показваме детайли
    header("Location: login.php?error=1");
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$user = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if ($user && password_verify($password, $user['password_hash'])) {
    // session
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];

    header("Location: dashboard.php");
    exit;
}

header("Location: login.php?error=1");
exit;
