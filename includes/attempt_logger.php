<?php
/**
 * Attempt logging:
 * - Raw attempts to NDJSON files (daily rotation)
 * - Aggregates to MySQL (attempts_agg_user_lab + attempts_agg_user)
 */

function attempts_storage_dir(): string {
    // /sqli-platform/includes -> /sqli-platform/storage/attempts
    return realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'attempts';
}

function ensure_attempts_dir(): void {
    $dir = attempts_storage_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

/**
 * Keep only a short preview, remove newlines/control chars.
 */
function attempt_preview(string $input, int $maxLen = 160): string {
    $s = preg_replace("/[\r\n\t]+/", " ", $input);
    $s = preg_replace("/[^\P{C}]+/u", "", $s); // drop control chars (unicode)
    $s = trim($s ?? "");
    if (mb_strlen($s) > $maxLen) {
        $s = mb_substr($s, 0, $maxLen) . '…';
    }
    return $s;
}

/**
 * Append NDJSON line with file lock.
 */
function log_attempt_to_file(int $userId, string $username, string $lab, int $success, string $input): void {
    ensure_attempts_dir();

    $dir = attempts_storage_dir();
    $date = date('Y-m-d'); // daily file
    $path = $dir . DIRECTORY_SEPARATOR . $date . '.ndjson';

    $payload = [
        'ts' => date('c'),
        'user_id' => $userId,
        'username' => $username,
        'lab' => $lab,
        'success' => (int)$success,
        'input_preview' => attempt_preview($input),
        'input_hash' => hash('sha256', $input),
    ];

    $line = json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n";

    $fp = @fopen($path, 'ab');
    if ($fp === false) return;

    // lock for concurrent writes
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, $line);
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

/**
 * Update MySQL aggregates (fast).
 */
function update_attempt_aggregates(mysqli $conn, int $userId, string $lab, int $success): void {
    $now = date('Y-m-d H:i:s');
    $success = (int)$success;

    // per user+lab
    $sql1 = "
        INSERT INTO attempts_agg_user_lab
          (user_id, lab, attempts_count, success_count, last_attempt_at, last_success_at)
        VALUES
          (?, ?, 1, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          attempts_count = attempts_count + 1,
          success_count = success_count + VALUES(success_count),
          last_attempt_at = VALUES(last_attempt_at),
          last_success_at = IF(VALUES(success_count) = 1, VALUES(last_success_at), last_success_at)
    ";

    $lastSuccessAt = $success === 1 ? $now : null;

    $stmt1 = mysqli_prepare($conn, $sql1);
    if ($stmt1) {
        // bind null safely (mysqli needs variable)
        $lsa = $lastSuccessAt;
        mysqli_stmt_bind_param($stmt1, "isiss", $userId, $lab, $success, $now, $lsa);
        mysqli_stmt_execute($stmt1);
        mysqli_stmt_close($stmt1);
    }

    // per user totals
    $sql2 = "
        INSERT INTO attempts_agg_user
          (user_id, attempts_total, success_total, last_attempt_at, last_success_at)
        VALUES
          (?, 1, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          attempts_total = attempts_total + 1,
          success_total = success_total + VALUES(success_total),
          last_attempt_at = VALUES(last_attempt_at),
          last_success_at = IF(VALUES(success_total) = 1, VALUES(last_success_at), last_success_at)
    ";

    $stmt2 = mysqli_prepare($conn, $sql2);
    if ($stmt2) {
        $lsa2 = $lastSuccessAt;
        mysqli_stmt_bind_param($stmt2, "iiss", $userId, $success, $now, $lsa2);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
    }
}

/**
 * One-call helper used in labs.
 */
function log_attempt(mysqli $conn, int $userId, string $username, string $lab, int $success, string $input): void {
    log_attempt_to_file($userId, $username, $lab, $success, $input);
    update_attempt_aggregates($conn, $userId, $lab, $success);
}
