<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/lab_gate.php';
require_once __DIR__ . '/../../includes/layout_bs.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

require_prereq_or_block($conn, $userId, 'LAB3_UNION_BASED');

bs_layout_start('Lab 4 – Error-based SQL Injection (Step 2)');
?>


<div class="card shadow-sm">
  <div class="card-body">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Модул 4: Error-based SQL Injection</h1>
        <p class="text-secondary mb-0">
          Как грешките “издават” информация и как да се предотврати
        </p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Модул 4</span>
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

      <h2 class="h5 fw-bold">1. Уязвима заявка (пример)</h2>
      <p class="text-secondary">
        Да разгледаме типична уязвима заявка, в която входът <code>$id</code>
        се използва директно:
      </p>

      <pre class="bg-light border rounded p-3"><code>SELECT id, name, description
FROM products
WHERE id = $id;</code></pre>

      <p class="text-secondary">
        Ако <code>$id</code> не се валидира като число, потребителят може да подаде стойност,
        която променя логиката на заявката или предизвиква грешка.
      </p>

      <h2 class="h5 fw-bold mt-4">2. “Обикновени” грешки (информационен теч)</h2>
      <p class="text-secondary">
        Дори само синтактична грешка може да помогне да се разбере:
      </p>
      <ul class="text-secondary">
        <li>дали параметърът се използва в SQL;</li>
        <li>дали заявката очаква число или текст;</li>
        <li>какъв е SQL диалектът/типът грешка.</li>
      </ul>

      <div class="alert alert-info">
        В учебна среда е полезно грешките да се виждат, за да се разбере причината.
        В реални системи това е сериозен риск.
      </div>

      <h2 class="h5 fw-bold mt-4">3. Error-based идея за извличане на стойности</h2>
      <p class="text-secondary">
        В някои бази данни съществуват функции/механизми, които при определени аргументи
        могат да върнат грешка, съдържаща част от подадените данни.
        В учебна среда това позволява да се демонстрира как “информация изтича” чрез грешки.
      </p>

      <div class="alert alert-warning">
        <strong>Забележка:</strong> Тук описваме концепцията. В практическата част целта е да разпознаеш теча и
        да постигнеш условието на lab-а в контролирана среда.
      </div>

      <h2 class="h5 fw-bold mt-4">4. Как да се предотврати?</h2>
      <ul class="text-secondary">
        <li><strong>Не показвай SQL грешки</strong> към крайния потребител (логвай ги отделно)</li>
        <li>Използвай <strong>prepared statements</strong> / параметризирани заявки</li>
        <li>Валидирай входа (например <code>id</code> да е число)</li>
        <li>Минимални привилегии за DB потребителя</li>
      </ul>

      <h2 class="h5 fw-bold mt-4">Практическа част</h2>
      <p class="text-secondary">
        В практиката ще имаш поле за <code>id</code> (или друг параметър според имплементацията ти).
        Целта е да наблюдаваш грешките и да изпълниш условието на Модул 4.
        При успех модулът се отбелязва автоматично като Завършен.
      </p>

      <div class="d-flex justify-content-between mt-4">
        <a class="btn btn-outline-secondary" href="step1.php">← Назад</a>
        <a class="btn btn-brand" href="practice.php">Към упражнението →</a>
      </div>

    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
