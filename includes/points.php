<?php
/**
 * Automatic Points on LAB completion (no flags).
 *
 * начислява точки 1 път на LAB completion (LAB1..LAB5)
 * penalty: -2 точки за всеки допълнителен опит (след първия)
 * минимум: 30% от base points
 */

function points_base_for_lab(string $labCode): int {
    $map = [
        'LAB0_INTRO'         => 10,
        'LAB1_AUTH_BYPASS'   => 50,
        'LAB2_BOOLEAN_BLIND' => 80,
        'LAB3_UNION_BASED'   => 100,
        'LAB4_ERROR_BASED'   => 120,
        'LAB5_TIME_BASED'    => 150,
    ];
    return (int)($map[$labCode] ?? 0);
}

/**
 * map LAB_CODE -> attempt logger lab name (както се логва в practice.php)
 * за да вземем attempts_count от attempts_agg_user_lab
 */
function points_attempt_lab_key(string $labCode): ?string {
    $map = [
        'LAB1_AUTH_BYPASS'   => 'lab1_practice',
        'LAB2_BOOLEAN_BLIND' => 'lab2_practice',
        'LAB3_UNION_BASED'   => 'lab3_practice',
        'LAB4_ERROR_BASED'   => 'lab4_practice',
        'LAB5_TIME_BASED'    => 'lab5_practice',
    ];
    return $map[$labCode] ?? null;
}

function points_calculate_award(int $base, int $attemptsUsed): int {
    $attemptsUsed = max(1, $attemptsUsed);
    $penalty = ($attemptsUsed - 1) * 2;
    $floor = (int)round($base * 0.30);
    $bonus = ($attemptsUsed === 1) ? 10 : 0;
    return max($floor, $base - $penalty) + $bonus;
}

function points_get_user_points(mysqli $conn, int $userId): int {
    $stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(delta),0) AS pts FROM user_points_ledger WHERE user_id = ?");
    if (!$stmt) return 0;
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $pts = 0;
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $pts = (int)($row['pts'] ?? 0);
    }
    mysqli_stmt_close($stmt);
    return $pts;
}

function points_add_ledger(mysqli $conn, int $userId, int $delta, string $reason, ?string $refType=null, ?int $refId=null, ?string $note=null): void {
    $stmt = mysqli_prepare($conn, "
        INSERT INTO user_points_ledger (user_id, delta, reason, ref_type, ref_id, note)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) return;
    mysqli_stmt_bind_param($stmt, "iissis", $userId, $delta, $reason, $refType, $refId, $note);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Award points on LAB completion (only once per user+lab_code).
 * Returns awarded points (0 if already awarded or not eligible).
 */
function points_award_for_lab_completion(mysqli $conn, int $userId, string $labCode): int {
    if ($userId <= 0) return 0;
    if (function_exists('is_admin') && is_admin()) return 0;

    $base = points_base_for_lab($labCode);
    if ($base <= 0) return 0; // ignore LAB0 and unknown labs

    // prevent duplicate awards
    mysqli_begin_transaction($conn);
    try {
        $stmtIns = mysqli_prepare($conn, "
            INSERT INTO user_lab_rewards (user_id, lab_code, points_awarded, awarded_at)
            VALUES (?, ?, 0, NOW())
        ");
        if (!$stmtIns) {
            mysqli_rollback($conn);
            return 0;
        }
        mysqli_stmt_bind_param($stmtIns, "is", $userId, $labCode);
        mysqli_stmt_execute($stmtIns);

        // If duplicate, insert fails with error 1062; treat as already awarded
        if (mysqli_stmt_errno($stmtIns) === 1062) {
            mysqli_stmt_close($stmtIns);
            mysqli_commit($conn);
            return 0;
        }
        $rewardId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmtIns);

        // determine attempts used from aggregates for practice key
        $attemptKey = points_attempt_lab_key($labCode);
        $attemptsUsed = 1;

        if ($attemptKey) {
            $stmtA = mysqli_prepare($conn, "
                SELECT attempts_count
                FROM attempts_agg_user_lab
                WHERE user_id = ? AND lab = ?
                LIMIT 1
            ");
            if ($stmtA) {
                mysqli_stmt_bind_param($stmtA, "is", $userId, $attemptKey);
                mysqli_stmt_execute($stmtA);
                $res = mysqli_stmt_get_result($stmtA);
                if ($res && ($row = mysqli_fetch_assoc($res))) {
                    $attemptsUsed = max(1, (int)($row['attempts_count'] ?? 1));
                }
                mysqli_stmt_close($stmtA);
            }
        }

        $award = points_calculate_award($base, $attemptsUsed);

        // update reward row with actual points
        $stmtUp = mysqli_prepare($conn, "UPDATE user_lab_rewards SET points_awarded = ? WHERE id = ?");
        if ($stmtUp) {
            mysqli_stmt_bind_param($stmtUp, "ii", $award, $rewardId);
            mysqli_stmt_execute($stmtUp);
            mysqli_stmt_close($stmtUp);
        }

        // ledger
        points_add_ledger($conn, $userId, $award, 'lab_completed', 'lab', null, $labCode . ' (attempts=' . $attemptsUsed . ')');

        mysqli_commit($conn);
        return $award;

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return 0;
    }
}
