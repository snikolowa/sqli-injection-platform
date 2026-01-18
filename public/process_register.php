<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$username = preg_replace('/\s+/', ' ', $username);
$username = mb_strtolower($username, 'UTF-8');
$email = mb_strtolower($email, 'UTF-8');
$email = preg_replace('/\s+/', '', $email);
$firstName = preg_replace('/\s+/', ' ', $firstName);
$lastName  = preg_replace('/\s+/', ' ', $lastName);

if ($username === '' || $email === '' || $password === '' || $passwordConfirm === '') {
    header("Location: register.php?error=Всички задължителни полета трябва да са попълнени");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: register.php?error=Невалиден имейл адрес");
    exit;
}

if ($password !== $passwordConfirm) {
    header("Location: register.php?error=Паролите не съвпадат");
    exit;
}

if (mb_strlen($username, 'UTF-8') < 3 || strlen($password) < 6) {
    header("Location: register.php?error=Минимум 3 символа за име и 6 за парола");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
if (!$stmt) {
    header("Location: register.php?error=DB грешка");
    exit;
}
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

if (!mysqli_stmt_store_result($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: register.php?error=DB грешка при проверка");
    exit;
}
if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    header("Location: register.php?error=Потребителското име е заето");
    exit;
}
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
if (!$stmt) {
    header("Location: register.php?error=DB грешка");
    exit;
}
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);

if (!mysqli_stmt_store_result($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: register.php?error=DB грешка при проверка");
    exit;
}
if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    header("Location: register.php?error=Имейлът вече е регистриран");
    exit;
}
mysqli_stmt_close($stmt);

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO users (username, password, first_name, last_name, email)
     VALUES (?, ?, ?, ?, ?)"
);
if (!$stmt) {
    header("Location: register.php?error=DB грешка (insert)");
    exit;
}

mysqli_stmt_bind_param($stmt, "sssss", $username, $hash, $firstName, $lastName, $email);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    $_SESSION['register_success'] = true;
    header("Location: login.php");
    exit;
}

$err = mysqli_error($conn);
mysqli_stmt_close($stmt);

if (stripos($err, 'duplicate') !== false) {
    header("Location: register.php?error=Потребителско име или имейл вече се използва");
    exit;
}

header("Location: register.php?error=Грешка при регистрацията");
exit;
