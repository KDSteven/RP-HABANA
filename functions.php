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
