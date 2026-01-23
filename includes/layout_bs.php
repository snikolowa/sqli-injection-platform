<?php
/**
 * Global Bootstrap layout (header + footer)
 * Includes timed hints JS.
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
    $current = $_SERVER['SCRIPT_NAME'] ?? '';
    $username = (string)($_SESSION['username'] ?? '');

    $isActive = function(string $path) use ($current): bool {
        return $current === $path;
    };
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
                          <?php
                            $adminPath = $base . '/public/admin/index.php';
                            $adminActive = $isActive($adminPath);
                          ?>
                          <a class="nav-link <?php echo $adminActive ? 'nav-active' : ''; ?>"
                             <?php echo $adminActive ? 'aria-current="page"' : ''; ?>
                             href="<?php echo $adminPath; ?>">Админ</a>
                        </li>
                      <?php endif; ?>

                        <li class="nav-item">
                            <?php
                              $dashPath = $base . '/public/dashboard.php';
                              $dashActive = $isActive($dashPath);
                            ?>
                            <a class="nav-link <?php echo $dashActive ? 'nav-active' : ''; ?>"
                               <?php echo $dashActive ? 'aria-current="page"' : ''; ?>
                               href="<?php echo $dashPath; ?>">Табло</a>
                        </li>
                        <li class="nav-item">
                            <?php
                              $profilePath = $base . '/public/profile.php';
                              $profileActive = $isActive($profilePath);
                            ?>
                            <a class="nav-link d-flex align-items-center gap-2 <?php echo $profileActive ? 'nav-active' : ''; ?>"
                               <?php echo $profileActive ? 'aria-current="page"' : ''; ?>
                               href="<?php echo $profilePath; ?>">
                                <span class="nav-avatar rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center">
                                  <?php echo htmlspecialchars(mb_strtoupper(mb_substr($username, 0, 1, 'UTF-8'), 'UTF-8')); ?>
                                </span>
                                <span><?php echo htmlspecialchars($username ?: 'Профил'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <?php
                              $logoutPath = $base . '/public/logout.php';
                              $logoutActive = $isActive($logoutPath);
                            ?>
                            <a class="nav-link <?php echo $logoutActive ? 'nav-active' : ''; ?>"
                               <?php echo $logoutActive ? 'aria-current="page"' : ''; ?>
                               href="<?php echo $logoutPath; ?>">Изход 🚪</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <?php
                              $homePath = $base . '/public/index.php';
                              $homeActive = $isActive($homePath);
                            ?>
                            <a class="nav-link <?php echo $homeActive ? 'nav-active' : ''; ?>"
                               <?php echo $homeActive ? 'aria-current="page"' : ''; ?>
                               href="<?php echo $homePath; ?>">Начало</a>
                        </li>
                        <li class="nav-item">
                            <?php
                              $labsPath = $base . '/public/labs.php';
                              $labsActive = $isActive($labsPath);
                            ?>
                            <a class="nav-link <?php echo $labsActive ? 'nav-active' : ''; ?>"
                               <?php echo $labsActive ? 'aria-current="page"' : ''; ?>
                               href="<?php echo $labsPath; ?>">Модули</a>
                        </li>
                        <li class="nav-item">
                            <?php
                              $registerPath = $base . '/public/register.php';
                              $registerActive = $isActive($registerPath);
                            ?>
                            <a class="nav-link <?php echo $registerActive ? 'nav-active' : ''; ?>"
                               <?php echo $registerActive ? 'aria-current="page"' : ''; ?>
                               href="<?php echo $registerPath; ?>">Регистрация</a>
                        </li>
                        <li class="nav-item">
                            <?php
                              $loginPath = $base . '/public/login.php';
                              $loginActive = $isActive($loginPath);
                            ?>
                            <a class="nav-link <?php echo $loginActive ? 'nav-active' : ''; ?>"
                               <?php echo $loginActive ? 'aria-current="page"' : ''; ?>
                               href="<?php echo $loginPath; ?>">Вход</a>
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
