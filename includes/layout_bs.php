<?php
// includes/layout_bs.php

function bs_layout_start(string $title): void {
  $base = '/sqli-platform';
  ?>
  <!doctype html>
  <html lang="bg">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($title); ?></title>

    <!-- Bootstrap 5 (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Малко custom стил -->
    <link href="<?php echo $base; ?>/assets/css/custom.css" rel="stylesheet">
  </head>
  <body class="bg-light">

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-dark">
    <div class="container">
      <a class="navbar-brand fw-bold" href="<?php echo $base; ?>/public/dashboard.php">SQLi Platform</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav ms-auto gap-1">
          <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/public/dashboard.php">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/public/profile.php">Профил</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/labs/lab0/intro.php">Урок 0</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/public/logout.php">Logout</a></li>
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
      Учебна платформа за SQL Injection (локална среда). Не използвай техники извън контролирана среда.
      <div class="mt-1">© <?php echo date('Y'); ?> SQLi Platform</div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
  </html>
  <?php
}
