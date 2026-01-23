<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
require_admin();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout_bs.php';

bs_layout_start('Admin');
?>

<div class="card shadow-sm">
  <div class="card-body">
    <h1 class="h4 fw-bold mb-1">Админ панел</h1>
    <p class="text-secondary mb-3">Потребители, прогрес и опити.</p>

    <div class="list-group">
      <a class="list-group-item list-group-item-action" href="users.php">👥 Потребители и прогрес</a>
      <a class="list-group-item list-group-item-action" href="export.php">⬇️ Експорт (CSV)</a>
    </div>
  </div>
</div>

<?php bs_layout_end(); ?>
