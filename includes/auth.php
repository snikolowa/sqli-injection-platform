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
