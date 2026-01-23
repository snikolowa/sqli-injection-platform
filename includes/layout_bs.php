<?php
/**
 * Global Bootstrap layout (header + footer)
 * Includes timed hints JS.
 */

/**
 * Base URL helper.
 * IMPORTANT: If your folder in htdocs is different, change it here.
 * Example: if project is in C:\xampp\htdocs\sqli-platform -> return '/sqli-platform';
 */
function base_url(): string {
    return '/sqli-platform';
}

function bs_layout_start(string $title): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $base = base_url();
    $loggedIn = !empty($_SESSION['user_id']);
    ?>
    <!doctype html>
    <html lang="bg">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo htmlspecialchars($title); ?></title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="<?php echo $base; ?>/assets/css/custom.css" rel="stylesheet">
    </head>
    <body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo $base; ?>/public/index.php">
                SQLi Platform
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto gap-1">
                    <?php if ($loggedIn): ?>
                      <?php if (function_exists('is_admin') && is_admin()): ?>
                        <li class="nav-item">
                          <a class="nav-link" href="<?php echo $base; ?>/public/admin/index.php">Админ</a>
                        </li>
                      <?php endif; ?>

                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base; ?>/public/dashboard.php">Табло</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base; ?>/public/profile.php">Профил</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base; ?>/public/logout.php">Изход</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base; ?>/public/index.php">Начало</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base; ?>/public/labs.php">Модули</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $base; ?>/public/login.php">Вход</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-4">
        <div class="container">
    <?php
}

function bs_layout_end(): void {
    ?>
        </div>
    </main>

    <footer class="py-4 mt-4 border-top bg-white">
        <div class="container small text-secondary">
            Учебна платформа за SQL Injection. Само за контролирана среда.
            <div class="mt-1">© <?php echo date('Y'); ?> SQLi Platform</div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo base_url(); ?>/assets/js/hints-timer.js"></script>

    </body>
    </html>
    <?php
}
