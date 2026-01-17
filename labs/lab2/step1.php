<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/layout_bs.php';
bs_layout_start('Lab 2 – Boolean-based Blind SQLi (Step 1)');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Lab 2: Boolean-based Blind SQL Injection</h1>
        <p class="text-secondary mb-0">
          Въведение в “blind” подхода: извличане на информация чрез TRUE/FALSE реакции.
        </p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Lab 2</span>
    </div>

    <hr>

    <div class="btn-group mb-4" role="group" aria-label="Lab navigation">
      <a class="btn btn-primary" href="step1.php">Step 1</a>
      <a class="btn btn-outline-primary" href="step2.php">Step 2</a>
      <a class="btn btn-outline-success" href="practice.php">Practice</a>
    </div>

    <h2 class="h5 fw-bold">1. Какво означава “Blind” SQL Injection?</h2>
    <p class="text-secondary">
      При “blind” SQLi приложението <strong>не показва директно резултати от базата</strong>
      (няма таблица с данни), а често и <strong>не показва грешки</strong>.
      Въпреки това, можем да разберем дали дадено SQL условие е вярно или невярно чрез
      <strong>различно поведение на страницата</strong>.
    </p>

    <h2 class="h5 fw-bold mt-4">2. Какво означава Boolean-based?</h2>
    <p class="text-secondary">
      “Boolean-based” означава, че задаваме <strong>въпрос</strong> към базата, който има два отговора:
      <strong>TRUE</strong> или <strong>FALSE</strong>.  
      След това наблюдаваме реакцията:
    </p>

    <ul class="text-secondary">
      <li>ако условието е TRUE → виждаме една реакция (“TRUE / Success / Found”);</li>
      <li>ако условието е FALSE → виждаме друга реакция (“FALSE / Not found”).</li>
    </ul>

    <h2 class="h5 fw-bold mt-4">3. Кога се използва този подход?</h2>
    <p class="text-secondary">
      Когато:
    </p>
    <ul class="text-secondary">
      <li>резултатите не се показват;</li>
      <li>грешките са скрити;</li>
      <li>има подозрение за SQLi, но няма “видим” изход.</li>
    </ul>

    <h2 class="h5 fw-bold mt-4">4. Защитен подход</h2>
    <ul class="text-secondary">
      <li>Prepared statements (параметризирани заявки)</li>
      <li>Валидация на входа</li>
      <li>Ограничаване на информацията към потребителя</li>
      <li>Мониторинг и rate limiting</li>
    </ul>

    <div class="alert alert-warning mt-4 mb-0">
      <strong>Важно:</strong> Това е учебна среда. В реални системи не трябва да има уязвими параметри и “oracle” поведение.
    </div>

    <div class="d-flex justify-content-end mt-4">
      <a class="btn btn-brand" href="step2.php">Продължи към Step 2 →</a>
    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
