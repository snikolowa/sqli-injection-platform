<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/lab_gate.php';
require_once __DIR__ . '/../../includes/layout_bs.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

require_prereq_or_block($conn, $userId, 'LAB4_ERROR_BASED');

bs_layout_start('Lab 5 – Time-based Blind SQL Injection (Step 2)');
?>


<div class="card shadow-sm">
  <div class="card-body">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Модул 5: Time-based Blind SQL Injection</h1>
        <p class="text-secondary mb-0">
          Как времето става “oracle” (TRUE/FALSE)
        </p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Модул 5</span>
    </div>

    <hr>

    <!-- Navigation -->
    <div class="btn-group mb-4" role="group" aria-label="Lab navigation">
      <a class="btn btn-outline-primary" href="step1.php">Урок</a>
      <a class="btn btn-primary" href="step2.php">Примери</a>
      <a class="btn btn-outline-success" href="practice.php">Упражнение</a>
    </div>

    <!-- Content -->
    <div>

      <h2 class="h5 fw-bold">1. Основната идея</h2>
      <p class="text-secondary">
        В този lab използваме SQL логика, която:
      </p>
      <ul class="text-secondary">
        <li>ако дадено условие е вярно → извиква функция за забавяне</li>
        <li>ако условието е невярно → не забавя</li>
      </ul>

      <h2 class="h5 fw-bold mt-4">2. Примерна форма на SQL (MySQL)</h2>
      <p class="text-secondary">
        В MySQL може да се използва условен израз <code>IF(...)</code> и функция за забавяне
        <code>SLEEP(seconds)</code>. Концептуално:
      </p>

      <pre class="bg-light border rounded p-3"><code>SELECT IF( (УСЛОВИЕ), SLEEP(2), 0 );</code></pre>

      <p class="text-secondary">
        Ако <code>(УСЛОВИЕ)</code> е вярно → заявката се забавя ~2 секунди.<br>
        Ако е невярно → връща веднага.
      </p>

      <h2 class="h5 fw-bold mt-4">3. Какви “въпроси” можем да задаваме?</h2>
      <p class="text-secondary">
        Подобно на boolean-based подхода, можем да проверяваме факти за данните — но вместо TRUE/FALSE на екрана,
        получаваме “бавен/бърз” отговор.
      </p>

      <div class="bg-light border rounded p-3">
        <p class="fw-semibold mb-2">Примери за условия (концептуално):</p>
        <div class="mb-2"><strong>Пример A (първи символ):</strong></div>
        <pre class="mb-3"><code>SUBSTRING(password, 1, 1) = 'a'</code></pre>

        <div class="mb-2"><strong>Пример B (дължина):</strong></div>
        <pre class="mb-0"><code>LENGTH(password) = 8</code></pre>
      </div>

      <h2 class="h5 fw-bold mt-4">4. Как работи практиката в платформата?</h2>
      <p class="text-secondary">
        В практическата част ще въведеш SQL условие, което приложението ще изпълни в уязвима заявка.
        Платформата измерва времето и показва:
      </p>
      <ul class="text-secondary">
        <li><strong>DELAYED</strong> – ако отговорът е забавен</li>
        <li><strong>NO DELAY</strong> – ако няма забавяне</li>
      </ul>

      <p class="text-secondary">
        Целта е да потвърдиш конкретен факт (в този модул: първият символ на admin паролата).
        При успех Модул 5 се отбелязва автоматично като Завършен.
      </p>

      <div class="d-flex justify-content-between mt-4">
        <a class="btn btn-outline-secondary" href="step1.php">← Назад</a>
        <a class="btn btn-brand" href="practice.php">Към упражнението →</a>
      </div>

    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
