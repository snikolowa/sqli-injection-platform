<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

require_once __DIR__ . '/../../includes/layout_bs.php';
bs_layout_start('Lab 1 – Authentication Bypass (Step 2)');
?>

<div class="card shadow-sm">
  <div class="card-body">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 fw-bold mb-1">Lab 1: Authentication Bypass</h1>
        <p class="text-secondary mb-0">
          Step 2 — Как входът от формата влияе на SQL заявката (и как възниква уязвимостта)
        </p>
      </div>
      <span class="badge text-bg-primary rounded-pill">Lab 1</span>
    </div>

    <hr>

    <!-- Navigation -->
    <div class="btn-group mb-4" role="group" aria-label="Lab navigation">
      <a class="btn btn-outline-primary" href="step1.php">Step 1</a>
      <a class="btn btn-primary" href="step2.php">Step 2</a>
      <a class="btn btn-outline-success" href="practice.php">Practice</a>
    </div>

    <!-- Content -->
    <div>
      <h2 class="h5 fw-bold">1. Как приложението проверява логин?</h2>
      <p class="text-secondary">
        При логин форма приложението взима <strong>username</strong> и <strong>password</strong>
        и проверява дали съществува ред в таблицата <code>users</code>, който съвпада с тях.
        Често това се реализира със SQL заявка.
      </p>

      <h2 class="h5 fw-bold mt-4">2. Пример за уязвима SQL заявка</h2>
      <p class="text-secondary">
        Когато разработчикът “слепва” (concatenate) потребителския вход директно в SQL кода,
        получаваме уязвимост:
      </p>

      <pre class="bg-light border rounded p-3 mb-0"><code>SELECT * FROM users
WHERE username = '$username' AND password = '$password';</code></pre>

      <div class="alert alert-warning mt-3">
        Тук проблемът е, че <strong>$username</strong> и <strong>$password</strong> не са просто “текст”.
        Ако съдържат SQL логика, тя ще бъде изпълнена като част от заявката.
      </div>

      <h2 class="h5 fw-bold mt-4">3. Нормален вход — как изглежда заявката</h2>
      <p class="text-secondary mb-2"><strong>Въведено от потребителя:</strong></p>
      <pre class="bg-light border rounded p-3"><code>username: admin
password: admin123</code></pre>

      <p class="text-secondary mb-2"><strong>Как изглежда SQL заявката след “вграждане”:</strong></p>
      <pre class="bg-light border rounded p-3"><code>SELECT * FROM users
WHERE username = 'admin' AND password = 'admin123';</code></pre>

      <p class="text-secondary">
        Ако такъв ред съществува, заявката връща резултат и приложението приема, че логинът е успешен.
      </p>

      <h2 class="h5 fw-bold mt-4">4. Как възниква Authentication Bypass?</h2>
      <p class="text-secondary">
        Authentication Bypass възниква, когато атакуващият успее да промени логиката на WHERE условието,
        така че заявката да върне резултат <strong>без реално да има валидна парола</strong>.
      </p>

      <h3 class="h6 fw-bold mt-3">4.1. Логически оператори (пример с OR)</h3>
      <p class="text-secondary">
        Един типичен подход е добавяне на логика с <code>OR</code>.
        Ако част от условието стане “винаги вярна”, заявката може да върне ред.
      </p>

      <p class="text-secondary mb-2"><strong>Илюстрация (как може да изглежда резултатната заявка):</strong></p>
      <pre class="bg-light border rounded p-3"><code>SELECT * FROM users
WHERE username = '' OR '1'='1' AND password = '...';</code></pre>

      <ul class="text-secondary">
        <li><code>OR</code> означава “или”</li>
        <li><code>'1'='1'</code> винаги е истина</li>
        <li>ако заявката върне поне един ред → приложението приема “успешен вход”</li>
      </ul>

      <h3 class="h6 fw-bold mt-4">4.2. SQL коментари (метод с <code>--</code>)</h3>
      <p class="text-secondary">
        Друг често използван механизъм са SQL коментарите. Коментарът <code>--</code> може да направи така,
        че “остатъкът” от заявката да бъде игнориран.
      </p>

      <p class="text-secondary mb-2">
        <strong>Илюстрация на идея:</strong> ако част от WHERE условието стане коментар, проверката за парола може да бъде игнорирана.
      </p>

      <pre class="bg-light border rounded p-3"><code>SELECT * FROM users
WHERE username = 'admin' -- ' AND password = '...';</code></pre>

      <div class="alert alert-info">
        <strong>MySQL уточнение:</strong> в MySQL често се използва като <code>-- </code> (с интервал след двете тирета).
      </div>

      <h2 class="h5 fw-bold mt-4">5. Как да се предотврати тази уязвимост?</h2>
      <p class="text-secondary">
        Най-доброто решение е входът да не се вгражда в SQL кода, а да се подава като параметър чрез
        <strong>prepared statements</strong>.
      </p>

      <pre class="bg-light border rounded p-3"><code>SELECT * FROM users WHERE username = ? AND password = ?;</code></pre>

      <ul class="text-secondary">
        <li>входът се третира само като стойност, а не като SQL логика</li>
        <li>коментари/OR логика не могат да “прекъснат” заявката</li>
        <li>в комбинация с хеширани пароли защитата е значително по-силна</li>
      </ul>

      <div class="alert alert-warning mt-4">
        <strong>Важно:</strong> В реални системи никога не трябва да се показва SQL заявката или грешките към потребителя.
        Тук това е учебна среда.
      </div>

      <div class="d-flex justify-content-between mt-4">
        <a class="btn btn-outline-secondary" href="step1.php">← Back</a>
        <a class="btn btn-brand" href="practice.php">Към упражнението →</a>
      </div>
    </div>

  </div>
</div>

<?php bs_layout_end(); ?>
