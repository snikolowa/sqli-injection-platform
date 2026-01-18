<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout_bs.php';

/**
 * LAB 0 – Intro
 * Отбелязва се като completed при първо отваряне
 */
$LAB_CODE = "LAB0_INTRO";
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId > 0) {
    $stmt = mysqli_prepare($conn, "
        INSERT INTO user_progress (user_id, lab_code, completed, completed_at)
        VALUES (?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE
            completed = 1,
            completed_at = IFNULL(completed_at, NOW())
    ");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "is", $userId, $LAB_CODE);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

bs_layout_start('Урок 0 – Въведение в SQL Injection');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Модул 0: Въведение в SQL Injection</h1>
        <p class="text-secondary mb-0">
          Основни понятия, видове атаки и принципи за защита
        </p>
      </div>
      <span class="badge text-bg-secondary rounded-pill">Въведение</span>
    </div>

    <hr>

    <!-- Content -->
    <div>

      <p class="text-secondary">
        Този уводен урок има за цел да въведе основните понятия, свързани със SQL Injection атаките,
        които ще бъдат разглеждани и упражнявани в следващите лабораторни упражнения.
        Урокът <strong>не съдържа практическа част</strong>, но се отбелязва като прочетен.
      </p>

      <h2 class="h5 fw-bold mt-4">1. Какво представлява SQL Injection?</h2>
      <p class="text-secondary">
        SQL Injection (SQLi) е клас уязвимости в уеб приложения, при които нападателят успява да
        манипулира SQL заявки чрез специално подготвен вход. Това се случва, когато входът от
        потребителя се използва директно в SQL заявка без защита.
      </p>

      <h2 class="h5 fw-bold mt-4">2. Как възниква SQL Injection?</h2>
      <p class="text-secondary">
        Най-често SQL Injection възниква при динамично изграждане на SQL заявки,
        при което липсва ясно разграничение между SQL код и данни.
      </p>

      <h2 class="h5 fw-bold mt-4">3. Защо SQL Injection е опасна?</h2>
      <p class="text-secondary">
        Успешна SQL Injection атака може да доведе до неоторизиран достъп до данни,
        заобикаляне на логин механизми, модификация или изтриване на информация,
        а понякога и до пълен контрол над базата данни.
      </p>

      <h2 class="h5 fw-bold mt-4">4. Основни видове SQL Injection</h2>
      <ul class="text-secondary">
        <li>
          <strong>In-band SQL Injection</strong> – данните се извличат директно в отговора.
        </li>
        <li>
          <strong>Inferential (Blind) SQL Injection</strong> – информацията се извлича
          индиректно чрез логика или време.
        </li>
        <li>
          <strong>Out-of-Band SQL Injection</strong> – използва се страничен комуникационен канал.
        </li>
      </ul>

      <h2 class="h5 fw-bold mt-4">5. NoSQL Injection</h2>
      <p class="text-secondary">
        Injection уязвимости могат да възникнат и при NoSQL системи,
        чрез специфични оператори и синтаксис.
      </p>

      <h2 class="h5 fw-bold mt-4">6. Общи принципи за защита</h2>
      <ul class="text-secondary">
        <li>Prepared statements / параметризирани заявки</li>
        <li>Строга валидация на входа</li>
        <li>Минимални привилегии за DB потребителя</li>
        <li>Ограничаване на грешките към крайния потребител</li>
      </ul>

      <div class="alert alert-success mt-4">
        ✅ Този урок е отбелязан като <strong>прочетен</strong>.
      </div>

      <h2 class="h5 fw-bold mt-4">Какво следва?</h2>
      <p class="text-secondary">
        Следващата стъпка е първата практическа лаборатория.
      </p>

      <div class="d-flex justify-content-end mt-4">
        <a class="btn btn-brand" href="/sqli-platform/labs/lab1/step1.php">
          Към Модул 1 → Authentication Bypass
        </a>
      </div>

    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
