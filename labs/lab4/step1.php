<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/lab_gate.php';
require_once __DIR__ . '/../../includes/layout_bs.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

// Модул 4 е заключен, ако Модул 3 не е завършен
require_prereq_or_block($conn, $userId, 'LAB3_UNION_BASED');

bs_layout_start('Модул 4 – Error-based SQL Injection (Урок)');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Модул 4: Error-based SQL Injection</h1>
        <p class="text-secondary mb-0">
          Какво е error-based SQLi и защо SQL грешките са опасни
        </p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Модул 4</span>
    </div>

    <hr>

    <!-- Navigation -->
    <div class="btn-group mb-4" role="group" aria-label="Module navigation">
      <a class="btn btn-primary" href="step1.php">Урок</a>
      <a class="btn btn-outline-primary" href="step2.php">Примери</a>
      <a class="btn btn-outline-success" href="practice.php">Упражнение</a>
    </div>

    <!-- Content -->
    <div>

      <h2 class="h5 fw-bold">1. Какво означава „error-based“?</h2>
      <p class="text-secondary">
        <strong>Error-based SQL Injection</strong> е техника, при която атакуващият използва
        <strong>съобщенията за грешки</strong>, които базата данни връща,
        за да получи информация за структурата на заявката, базата данни,
        таблиците и колоните.
      </p>

      <h2 class="h5 fw-bold mt-4">2. Защо SQL грешките са проблем?</h2>
      <p class="text-secondary">
        В реални приложения SQL грешките често „издават“ твърде много информация:
      </p>
      <ul class="text-secondary">
        <li>SQL синтаксис и част от изпълняваната заявка</li>
        <li>имена на таблици и колони</li>
        <li>типове данни</li>
        <li>понякога дори реални стойности</li>
      </ul>

      <div class="alert alert-warning">
        <strong>Важно:</strong> Показването на SQL грешки към крайния потребител
        е сериозна уязвимост.
      </div>

      <h2 class="h5 fw-bold mt-4">3. Кога се среща най-често?</h2>
      <p class="text-secondary">Error-based SQLi се среща най-често при:</p>
      <ul class="text-secondary">
        <li>стари или лошо поддържани приложения</li>
        <li>debug режим, оставен включен в продукция</li>
        <li>директно извеждане на <code>mysqli_error()</code></li>
        <li>динамично „слепване“ на входа в SQL заявки</li>
      </ul>

      <h2 class="h5 fw-bold mt-4">4. Каква ще е задачата в този модул?</h2>
      <p class="text-secondary">
        В практическата част ще имаш параметър, който се използва директно в SQL заявка.
        Приложението умишлено показва SQL грешките (учебна среда).
      </p>

      <p class="text-secondary">
        Целта ще бъде чрез <strong>error-based подход</strong> да предизвикаш SQL грешка,
        която съдържа <strong>името на текущата база данни</strong>.
      </p>

      <div class="alert alert-info">
        В следващия урок ще разгледаме конкретни примери как грешките могат
        да „извеждат“ информация.
      </div>

      <div class="d-flex justify-content-end mt-4">
        <a class="btn btn-brand" href="step2.php">
          Към примерите →
        </a>
      </div>

    </div>
  </div>
</div>

<?php bs_layout_end(); ?>
