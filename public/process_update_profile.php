<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

require_once __DIR__ . '/../includes/db.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$firstName = preg_replace('/\s+/', ' ', $firstName);
$lastName  = preg_replace('/\s+/', ' ', $lastName);

$stmt = mysqli_prepare($conn, "UPDATE users SET first_name = ?, last_name = ? WHERE id = ?");
if (!$stmt) {
  header("Location: profile.php?error=DB грешка");
  exit;
}

mysqli_stmt_bind_param($stmt, "ssi", $firstName, $lastName, $userId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header("Location: profile.php?ok=Промените са запазени");
exit;
