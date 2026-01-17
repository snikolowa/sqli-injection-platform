<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/layout_bs.php';
bs_layout_start('Lab 1 – Authentication Bypass (Step 1)');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Lab 1: Authentication Bypass</h1>
        <p class="text-secondary mb-0">
          Въведение в заобикалянето на логин механизми чрез SQL Injection.
        </p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Lab 1</span>
    </div>

    <hr>

    <!-- Navigation -->
    <div class="btn-group mb-4" role="group" aria-label="Lab navigation">
      <a class="btn btn-primary" href="step1.php">Step 1</a>
      <a class="btn btn-outline-primary" href="step2.php">Step 2</a>
      <a class="btn btn-outline-success" href="practice.php">Practice</a>
    </div>

    <!-- Content -->
    <div>

      <h2 class="h5 fw-bold">1. Какво представлява Authentication Bypass?</h2>
      <p class="text-secondary">
        <strong>Authentication Bypass</strong> е вид уязвимост, при която атакуващ успява да получи достъп
        до защитена част от уеб приложение <strong>без да притежава валидни потребителски данни</strong>
        (потребителско име и/или парола).
      </p>
      <p class="text-secondary">
        Вместо да “познае” паролата, атакуващият се възползва от грешка в логиката на приложението —
        най-често в начина, по който се изгражда SQL заявката за проверка на входа.
      </p>

      <h2 class="h5 fw-bold mt-4">2. Как работи стандартният логин механизъм?</h2>
      <p class="text-secondary">
        При типичен логин процес уеб приложението:
      </p>
      <ol class="text-secondary">
        <li>получава въведените от потребителя <em>username</em> и <em>password</em>;</li>
        <li>изпраща SQL заявка към базата данни;</li>
        <li>проверява дали съществува запис с тези стойности;</li>
        <li>ако има резултат → достъпът е разрешен.</li>
      </ol>

      <p class="text-secondary">
        Проблемът възниква, когато потребителският вход се вгражда директно в SQL заявката,
        без допълнителна защита.
      </p>

      <h2 class="h5 fw-bold mt-4">3. Кога логинът става уязвим?</h2>
      <p class="text-secondary">
        Логин механизмът е уязвим на SQL Injection, когато са изпълнени едно или повече от следните условия:
      </p>

      <ul class="text-secondary">
        <li>потребителският вход се използва директно в SQL заявка;</li>
        <li>липсват prepared statements (параметризирани заявки);</li>
        <li>няма валидиране или филтриране на входа;</li>
        <li>SQL логиката може да бъде променена чрез оператори като <code>OR</code>;</li>
        <li>част от заявката може да бъде игнорирана чрез SQL коментари (<code>--</code>).</li>
      </ul>

      <h2 class="h5 fw-bold mt-4">4. Каква е ролята на SQL Injection?</h2>
      <p class="text-secondary">
        SQL Injection позволява на атакуващия да “инжектира” собствена SQL логика
        във вече съществуваща заявка. Така той може:
      </p>

      <ul class="text-secondary">
        <li>да промени логическите условия;</li>
        <li>да направи проверката „винаги вярна“;</li>
        <li>да заобиколи проверката за парола;</li>
        <li>да се представи за друг потребител (например администратор).</li>
      </ul>

      <h2 class="h5 fw-bold mt-4">5. Как може да се предотврати Authentication Bypass?</h2>
      <p class="text-secondary">
        Най-ефективните защитни мерки включват:
      </p>

      <ul class="text-secondary">
        <li>използване на prepared statements;</li>
        <li>разделяне на SQL логиката от потребителския вход;</li>
        <li>валидация и ограничаване на входните данни;</li>
        <li>използване на хеширани пароли;</li>
        <li>минимални права за достъп до базата данни.</li>
      </ul>

      <div class="alert alert-warning mt-4">
        <strong>Важно:</strong> Всички лаборатории в тази платформа са умишлено уязвими
        и са предназначени <strong>само за учебни цели</strong> в контролирана среда.
      </div>

      <div class="d-flex justify-content-end mt-4">
        <a class="btn btn-brand" href="step2.php">Продължи към Step 2 →</a>
      </div>

    </div>
  </div>
</div>

<?php bs_layout_end(); ?>
