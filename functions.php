<?php
/**
 * Cached lookup of branch name by id.
 */
function getBranchName(mysqli $conn, ?int $branch_id): string {
    static $cache = [];

    if (!$branch_id) return 'System';
    if (isset($cache[$branch_id])) return $cache[$branch_id];

    $stmt = $conn->prepare("SELECT branch_name FROM branches WHERE branch_id = ?");
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $stmt->bind_result($name);
    $stmt->fetch();
    $stmt->close();

    $name = $name ?: 'Unknown Branch';
    $cache[$branch_id] = $name;
    return $name;
}

/**
 * In any free-text $text:
 * 1) Replace "branch 7" / "branch id: 7" / "Branch-7" with the actual branch name.
 * 2) Tidy common phrasing: "to branch ID Barandal Branch" -> "to Barandal Branch",
 *    "from branch Barandal Branch" -> "from Barandal Branch".
 */
function expandBranchTokensToNames(mysqli $conn, string $text): string {
    if ($text === '') return $text;

    // 1) Collect unique IDs that appear after "branch" or "branch id"
    if (preg_match_all('/\bbranch(?:\s*id)?\s*[:#-]?\s*(\d+)\b/i', $text, $m)) {
        $ids = array_values(array_unique(array_map('intval', $m[1])));
        if ($ids) {
            // Build id -> name map
            $map = [];
            foreach ($ids as $id) {
                $map[$id] = getBranchName($conn, $id);
            }

            // Replace the numeric IDs with their names (keep the prefix initially)
            $text = preg_replace_callback(
                '/\b(branch(?:\s*id)?\s*[:#-]?\s*)(\d+)\b/i',
                function ($mm) use ($map) {
                    $id = (int)$mm[2];
                    $name = $map[$id] ?? $mm[2];
                    return $mm[1] . $name; // e.g., "branch id " + "Barandal Branch"
                },
                $text
            );
        }
    }

    // 2) Tidy phrases so we don’t show "to branch ID <Name>"
    //    Convert "to branch( id)? <Name>" -> "to <Name>" (same for "from")
    //    We only remove the literal "branch"/"branch id" when it’s immediately before a NAME, not a number.
    $text = preg_replace('/\b(to|from)\s+branch(?:\s*id)?\s*[:#-]?\s+(?=[A-Za-z])/i', '$1 ', $text);

    return $text;
}

/**
 * Log an action; expands branch tokens in details and appends context if missing.
 */
function logAction(mysqli $conn, string $action, string $details, ?int $user_id = null, ?int $branch_id = null): void {
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = (int)$_SESSION['user_id'];
    }
    if (!$branch_id && isset($_SESSION['branch_id'])) {
        $branch_id = (int)$_SESSION['branch_id']; // current session branch
    }

    // Expand any "branch 3" / "branch id: 3" -> branch names; tidy phrasing.
    $details = expandBranchTokensToNames($conn, (string)$details);

    // If there is no "branch" mention in the text, append a simple context tag.
    if ($branch_id && stripos($details, 'branch') === false) {
        $details .= ' | Branch: ' . getBranchName($conn, (int)$branch_id);
    }

    $stmt = $conn->prepare("
        INSERT INTO logs (user_id, action, details, timestamp, branch_id)
        VALUES (?, ?, ?, NOW(), ?)
    ");
    $stmt->bind_param("issi", $user_id, $action, $details, $branch_id);
    $stmt->execute();
    $stmt->close();
}

function get_active_shift(mysqli $conn, int $user_id, int $branch_id): ?array {
    $stmt = $conn->prepare("
      SELECT * FROM shifts
      WHERE user_id=? AND branch_id=? AND status='open'
      ORDER BY start_time DESC LIMIT 1
    ");
    $stmt->bind_param("ii", $user_id, $branch_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function start_shift(mysqli $conn, int $user_id, int $branch_id, float $opening_cash, string $note=''): int {
    // prevent duplicates
    if (get_active_shift($conn, $user_id, $branch_id)) {
        throw new Exception("You already have an open shift.");
    }
    $stmt = $conn->prepare("
      INSERT INTO shifts (user_id, branch_id, opening_cash, opening_note)
      VALUES (?,?,?,?)
    ");
    $stmt->bind_param("iids", $user_id, $branch_id, $opening_cash, $note);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return (int)$id;
}

/**
 * Compute expected drawer for the shift:
 * opening_cash
 * + sum(payment - change_given) from sales within shift window for this user & branch
 * - sum(refund_total) from sales_refunds within shift window
 * + pay_in - pay_out from shift_cash_moves
 */
function compute_expected_cash_for_shift(mysqli $conn, int $shift_id): float {
    // load shift window
    $s = $conn->prepare("SELECT user_id, branch_id, opening_cash, start_time, IFNULL(end_time, NOW()) AS end_time
                         FROM shifts WHERE shift_id=?");
    $s->bind_param("i", $shift_id);
    $s->execute();
    $sh = $s->get_result()->fetch_assoc();
    $s->close();
    if (!$sh) return 0.0;

    [$uid, $bid, $opening, $start, $end] = [
        (int)$sh['user_id'], (int)$sh['branch_id'],
        (float)$sh['opening_cash'], $sh['start_time'], $sh['end_time']
    ];

    // Cash in from sales = payment - change_given (drawer net increase)
    $q1 = $conn->prepare("
      SELECT COALESCE(SUM(payment - change_given),0) AS net_in
      FROM sales
      WHERE branch_id=? AND processed_by=? AND sale_date BETWEEN ? AND ?
            AND shift_id=?  -- tie to this shift
    ");
    $q1->bind_param("isssi", $bid, $uid, $start, $end, $shift_id);
    $q1->execute();
    $net_in = (float)($q1->get_result()->fetch_assoc()['net_in'] ?? 0);
    $q1->close();

    // Cash out from refunds
    $q2 = $conn->prepare("
      SELECT COALESCE(SUM(refund_total),0) AS cash_out
      FROM sales_refunds
      WHERE refund_date BETWEEN ? AND ?
        AND shift_id=?
    ");
    $q2->bind_param("ssi", $start, $end, $shift_id);
    $q2->execute();
    $cash_out = (float)($q2->get_result()->fetch_assoc()['cash_out'] ?? 0);
    $q2->close();

    // Petty cash moves
    $q3 = $conn->prepare("
      SELECT
        COALESCE(SUM(CASE WHEN move_type='pay_in'  THEN amount END),0) AS pin,
        COALESCE(SUM(CASE WHEN move_type='pay_out' THEN amount END),0) AS pout
      FROM shift_cash_moves WHERE shift_id=?
    ");
    $q3->bind_param("i", $shift_id);
    $q3->execute();
    $r3 = $q3->get_result()->fetch_assoc();
    $q3->close();

    $pay_in  = (float)($r3['pin']  ?? 0);
    $pay_out = (float)($r3['pout'] ?? 0);

    return $opening + $net_in - $cash_out + $pay_in - $pay_out;
}

function end_shift(mysqli $conn, int $shift_id, float $closing_cash, string $note=''): array {
    $expected = compute_expected_cash_for_shift($conn, $shift_id);
    $diff = $closing_cash - $expected;

    $stmt = $conn->prepare("
      UPDATE shifts
      SET end_time=NOW(), closing_cash=?, expected_cash=?, cash_difference=?, closing_note=?, status='closed'
      WHERE shift_id=? AND status='open'
    ");
    $stmt->bind_param("ddssi", $closing_cash, $expected, $diff, $note, $shift_id);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    if (!$ok) throw new Exception("Shift already closed or not found.");

    return ['expected' => $expected, 'difference' => $diff];
}
