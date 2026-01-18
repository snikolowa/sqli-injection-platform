<?php
// includes/lab_gate.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Проверява дали lab е completed
 */
function is_lab_completed(mysqli $conn, int $userId, string $labCode): bool {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT completed FROM user_progress WHERE user_id = ? AND lab_code = ? LIMIT 1"
    );
    if (!$stmt) return false;

    mysqli_stmt_bind_param($stmt, "is", $userId, $labCode);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $done = false;
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $done = ((int)$row['completed'] === 1);
    }

    mysqli_stmt_close($stmt);
    return $done;
}

/**
 * Блокира достъпа, ако prerequisite не е завършен
 */
function require_prereq_or_block(
    mysqli $conn,
    int $userId,
    string $prereqLabCode,
    string $redirectTo = "/sqli-platform/public/dashboard.php"
): void {
    if ($prereqLabCode === '') return;

    if (!is_lab_completed($conn, $userId, $prereqLabCode)) {
        $_SESSION['flash_error'] =
            "🔒 Този урок/лаборатория е заключен. Първо завърши предишния.";
        header("Location: " . $redirectTo);
        exit;
    }
}
