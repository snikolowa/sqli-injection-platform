<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

function require_prereq_or_block(
    mysqli $conn,
    int $userId,
    string $prereqLabCode,
    string $redirectTo = "/sqli-platform/public/dashboard.php"
): void {
    if (function_exists('is_admin') && is_admin()) {
        $_SESSION['flash_error'] = "Админите нямат достъп до упражненията.";
        header("Location: " . $redirectTo);
        exit;
    }

    if ($prereqLabCode === '') return;

    if (!is_lab_completed($conn, $userId, $prereqLabCode)) {
        $_SESSION['flash_error'] =
            "🔒 Този урок/лаборатория е заключен. Първо завърши предишния.";
        header("Location: " . $redirectTo);
        exit;
    }
}
