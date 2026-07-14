<?php
/**
 * get_users.php
 * ----------------------------------------------------------------------
 * Read-only JSON endpoint backing the Utilities tab's live-updating
 * account list (admin.php polls this on an interval via fetch()).
 * Returns the same data admin.php's own initial page load queries —
 * this endpoint exists purely so the browser can re-fetch it every
 * few seconds without a full page reload, so new pending self-signups
 * (or any other admin's changes) show up automatically instead of
 * requiring the admin to manually refresh.
 *
 * Admin-only, same guard as every other admin endpoint. Read-only
 * (SELECT only) — this file never modifies the database.
 * ----------------------------------------------------------------------
 */

require_once '../logic/session_config.php';
require_once '../logic/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Not authorized.']);
    exit();
}

$users = [];
$result = $conn->query(
    "SELECT id, first_name, last_name, email, role, status FROM users ORDER BY (status = 'active'), id"
);

if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not load user accounts: ' . $conn->error]);
    exit();
}

while ($row = $result->fetch_assoc()) {
    // Cast id to int explicitly — mysqli returns numeric columns as
    // strings by default, and the front-end JS compares ids to detect
    // newly-added rows, which is safer as a real number than a string.
    $row['id'] = (int) $row['id'];
    $users[] = $row;
}

echo json_encode(['users' => $users]);