<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header("Location: /sqli-platform/public/login.php");
        exit;
    }
}
function is_admin(): bool {
    return !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_admin(): void {
    if (!is_admin()) {
        http_response_code(403);
        echo "403 Forbidden";
        exit;
    }
}

function require_not_admin(string $redirectTo = "/sqli-platform/public/dashboard.php"): void {
    if (is_admin()) {
        $_SESSION['flash_error'] = "Админите нямат достъп до упражненията.";
        header("Location: " . $redirectTo);
        exit;
    }
}
