<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/lab_gate.php';
require_once __DIR__ . '/../../includes/layout_bs.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

require_prereq_or_block($conn, $userId, 'LAB1_AUTH_BYPASS');

bs_layout_start('Lab 2 – Boolean-based Blind SQLi (Step 2)');
?>


<div class="card shadow-sm">
  <div class="card-body">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Модул 2: Boolean-based Blind SQL Injection</h1>
        <p class="text-secondary mb-0">Как се задават “въпроси” към базата (TRUE/FALSE)</p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Модул 2</span>
    </div>

    <hr>

    <div class="btn-group mb-4" role="group" aria-label="Lab navigation">
      <a class="btn btn-outline-primary" href="step1.php">Урок</a>
      <a class="btn btn-primary" href="step2.php">Примери</a>
      <a class="btn btn-outline-success" href="practice.php">Упражнение</a>
    </div>

    <h2 class="h5 fw-bold">1. Как изглежда “oracle” реакция?</h2>
    <p class="text-secondary">
      При boolean-based blind ние не виждаме данни, а само индиректна реакция.
      Например страница може да показва:
    </p>

    <div class="row g-3">
      <div class="col-12 col-md-6">
        <div class="border rounded p-3 bg-light">
          <div class="fw-semibold">Ако условието е TRUE</div>
          <div class="text-secondary small">Показва “TRUE ✅” или “Found ✅”</div>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="border rounded p-3 bg-light">
          <div class="fw-semibold">Ако условието е FALSE</div>
          <div class="text-secondary small">Показва “FALSE ❌” или “Not found ❌”</div>
        </div>
      </div>
    </div>

    <h2 class="h5 fw-bold mt-4">2. Какви въпроси могат да се задават?</h2>
    <p class="text-secondary">
      Въпросите са от тип “да/не”. Примери за идеи (не са готови решения):
    </p>

    <ul class="text-secondary">
      <li>“Съществува ли потребител admin?”</li>
      <li>“Дължината на паролата на admin равна ли е на 8?”</li>
      <li>“Първият символ на паролата ‘a’ ли е?”</li>
    </ul>

    <h2 class="h5 fw-bold mt-4">3. Как става извличането на информация?</h2>
    <p class="text-secondary">
      При истинско blind извличане задаваме поредица от въпроси:
      първо за дължина, после за символ по символ (позиция 1, 2, 3…), докато сглобим стойността.
      Тук демонстрираме принципа в контролирана среда.
    </p>

    <h2 class="h5 fw-bold mt-4">4. Защита</h2>
    <p class="text-secondary">
      Ако приложението използва prepared statements и валидира входа, тези “въпроси” не могат да променят логиката на SQL заявката.
    </p>

    <div class="alert alert-warning mt-4">
      <strong>Важно:</strong> В практиката няма да показваме SQL заявката, а само TRUE/FALSE резултат.
    </div>

    <div class="d-flex justify-content-between mt-4">
      <a class="btn btn-outline-secondary" href="step1.php">← Назад</a>
      <a class="btn btn-brand" href="practice.php">Към упражнението →</a>
    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
