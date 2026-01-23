<?php

function get_modules_ordered(): array {
  return [
    ['code' => 'LAB0_INTRO',         'path' => '/sqli-platform/labs/lab0/intro.php',      'label' => 'Въведение'],
    ['code' => 'LAB1_AUTH_BYPASS',   'path' => '/sqli-platform/labs/lab1/step1.php',      'label' => 'Модул 1'],
    ['code' => 'LAB2_BOOLEAN_BLIND', 'path' => '/sqli-platform/labs/lab2/step1.php',      'label' => 'Модул 2'],
    ['code' => 'LAB3_UNION_BASED',   'path' => '/sqli-platform/labs/lab3/step1.php',      'label' => 'Модул 3'],
    ['code' => 'LAB4_ERROR_BASED',   'path' => '/sqli-platform/labs/lab4/step1.php',      'label' => 'Модул 4'],
    ['code' => 'LAB5_TIME_BASED',    'path' => '/sqli-platform/labs/lab5/step1.php',      'label' => 'Модул 5'],
  ];
}

function get_next_module(string $currentCode): ?array {
  $mods = get_modules_ordered();
  $n = count($mods);

  for ($i = 0; $i < $n; $i++) {
    if ($mods[$i]['code'] === $currentCode) {
      return ($i + 1 < $n) ? $mods[$i + 1] : null;
    }
  }
  return null;
}
