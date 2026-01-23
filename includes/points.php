<?php
/**
 * Points / CTF helpers
 *
 * Security note:
 * - Never store plaintext flags in DB.
 * - We store only flag_hash (HMAC-SHA256) using a server-side pepper.
 *
 * Setup:
 * 1) Change POINTS_PEPPER to something long and secret.
 * 2) Generate hashes for your flags and put them in DB (challenges.flag_hash).
 *
 * Quick hash helper:
 * - Temporarily call points_hash_flag('SQLI{demo}') somewhere and echo it,
 *   OR use /public/ctf.php debug block (only for admin/dev).
 */

const POINTS_PEPPER = 'CHANGE_THIS_TO_A_LONG_RANDOM_SECRET_STRING_>=_32_CHARS';

function points_hash_flag(string $flag): string {
    $flag = trim($flag);
    // HMAC is better than plain sha256(flag+pepper) because pepper acts as key
    return hash_hmac('sha256', $flag, POINTS_PEPPER);
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

function points_is_solved(mysqli $conn, int $userId, int $challengeId): bool {
    $stmt = mysqli_prepare($conn, "SELECT 1 FROM user_challenge_solves WHERE user_id = ? AND challenge_id = ? LIMIT 1");
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, "ii", $userId, $challengeId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ok = (bool)($res && mysqli_fetch_assoc($res));
    mysqli_stmt_close($stmt);
    return $ok;
}

/**
 * Increment attempts counter for (user, challenge) and return new attempts_count.
 */
function points_increment_attempt(mysqli $conn, int $userId, int $challengeId): int {
    // Try update first
    $stmt = mysqli_prepare($conn, "
        UPDATE user_flag_attempts
        SET attempts_count = attempts_count + 1, last_attempt_at = NOW()
        WHERE user_id = ? AND challenge_id = ?
    ");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $userId, $challengeId);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($affected > 0) {
            // fetch current attempts_count
            $stmt2 = mysqli_prepare($conn, "SELECT attempts_count FROM user_flag_attempts WHERE user_id=? AND challenge_id=? LIMIT 1");
            if (!$stmt2) return 1;
            mysqli_stmt_bind_param($stmt2, "ii", $userId, $challengeId);
            mysqli_stmt_execute($stmt2);
            $res = mysqli_stmt_get_result($stmt2);
            $count = 1;
            if ($res && ($row = mysqli_fetch_assoc($res))) $count = (int)($row['attempts_count'] ?? 1);
            mysqli_stmt_close($stmt2);
            return max(1, $count);
        }
    }

    // Insert if missing
    $stmt3 = mysqli_prepare($conn, "
        INSERT INTO user_flag_attempts (user_id, challenge_id, attempts_count, last_attempt_at)
        VALUES (?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE attempts_count = attempts_count + 1, last_attempt_at = NOW()
    ");
    if (!$stmt3) return 1;
    mysqli_stmt_bind_param($stmt3, "ii", $userId, $challengeId);
    mysqli_stmt_execute($stmt3);
    mysqli_stmt_close($stmt3);

    // Read
    $stmt4 = mysqli_prepare($conn, "SELECT attempts_count FROM user_flag_attempts WHERE user_id=? AND challenge_id=? LIMIT 1");
    if (!$stmt4) return 1;
    mysqli_stmt_bind_param($stmt4, "ii", $userId, $challengeId);
    mysqli_stmt_execute($stmt4);
    $res = mysqli_stmt_get_result($stmt4);
    $count = 1;
    if ($res && ($row = mysqli_fetch_assoc($res))) $count = (int)($row['attempts_count'] ?? 1);
    mysqli_stmt_close($stmt4);

    return max(1, $count);
}

/**
 * Points formula:
 * - base points
 * - penalty: -2 points per extra attempt beyond first
 * - minimum floor: 30% of base (so it never becomes 0)
 */
function points_calculate_award(int $base, int $attemptsUsed): int {
    $attemptsUsed = max(1, $attemptsUsed);
    $penalty = ($attemptsUsed - 1) * 2;
    $floor = (int)round($base * 0.30);
    return max($floor, $base - $penalty);
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
