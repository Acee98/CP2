<?php
/**
 * user_admin_mngmnt.php
 * ----------------------------------------------------------------------
 * Backend handler for the admin-only Utilities page (admin.php).
 * Four actions, one per form that can submit here:
 *   - add_user    Admin directly creates a new account (any role),
 *                 created as status='active' immediately — this is
 *                 separate from the public self-signup flow in
 *                 user_mngmnt.php, which gates new accounts as
 *                 'inactive' until an admin approves them. An account
 *                 an admin creates by hand doesn't need to wait on
 *                 admin approval — the admin creating it *is* the
 *                 approval.
 *   - edit_user   Update an existing account's name/email/role.
 *   - set_status  Activate or deactivate an account — this is also
 *                 how a pending self-signup gets approved (flip
 *                 'inactive' -> 'active').
 *   - delete_user Permanently remove an account. Irreversible, unlike
 *                 set_status (which just flips a flag) — there's no
 *                 "undelete", so this is guarded the same way
 *                 self-deactivation is (can't delete the account
 *                 you're currently logged in as).
 *
 * All four redirect back to admin.php?tab=utilities when done, with
 * a session-based success/error message (utilities_success /
 * utilities_error) that admin.php reads and clears, same pattern
 * user_mngmnt.php already uses for login/signup messages.
 * ----------------------------------------------------------------------
 */

// FIX: session setup now centralized in session_config.php — see
// that file for why (dedicated session storage folder + consistent
// cookie config across every session-using page). Uses the same
// '../logic/' prefix the rest of this file already uses for config.php,
// even though this file lives in logic/ itself — matches the existing
// project convention and avoids relying on include_path resolution.
require_once '../logic/session_config.php';
require_once '../logic/config.php';

// Guard: only a logged-in admin may use this endpoint. Anyone else —
// logged out, or logged in as user/techn — is bounced to login rather
// than being able to hit these actions directly by URL.
if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../pages/login_signup.php");
    exit();
}

/**
 * Same graceful-failure guard as user_mngmnt.php: prepare() returns
 * false (not an exception) on bad SQL — most likely here because a
 * referenced column doesn't exist yet. See the two migration notes
 * below (status, id) for what this table needs.
 */
function require_prepared($stmt, $conn) {
    if ($stmt === false) {
        error_log('user_admin_mngmnt.php: prepare() failed — ' . $conn->error);
        $_SESSION['utilities_error'] = 'Something went wrong on our end. Please try again shortly.';
        header("Location: ../pages/admin.php?tab=utilities");
        exit();
    }
    return $stmt;
}

$allowedRoles = ['user', 'admin', 'techn'];
$allowedStatuses = ['active', 'inactive'];

/**
 * ------------------------------------------------------------------
 * ADD USER
 * ------------------------------------------------------------------
 * NOTE ON SCHEMA: this assumes `users` has an auto-increment `id`
 * primary key, which is standard practice but was never explicitly
 * shown in any file in this project (user_mngmnt.php never selects
 * or uses one). If your table doesn't have one yet:
 *   ALTER TABLE users ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST;
 * Edit/status actions below key off this id rather than email, since
 * editing a row includes changing its email.
 */
if (isset($_POST['add_user'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $role       = $_POST['role'] ?? '';
    $password_plain = $_POST['password'] ?? '';

    if ($first_name === '' || $last_name === '' || $email === ''
        || strlen($password_plain) < 8 || !in_array($role, $allowedRoles, true)) {
        $_SESSION['utilities_error'] = 'Please fill in every field with a valid role and an 8+ character password.';
        header("Location: ../pages/admin.php?tab=utilities&action=add");
        exit();
    }

    $checkEmail = require_prepared($conn->prepare("SELECT id FROM users WHERE email = ?"), $conn);
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) {
        $checkEmail->close();
        $_SESSION['utilities_error'] = 'That email is already registered.';
        header("Location: ../pages/admin.php?tab=utilities&action=add");
        exit();
    }
    $checkEmail->close();

    $password = password_hash($password_plain, PASSWORD_DEFAULT);
    $status = 'active';

    $insert = require_prepared($conn->prepare(
        "INSERT INTO users (first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)"
    ), $conn);
    $insert->bind_param("ssssss", $first_name, $last_name, $email, $password, $role, $status);
    $insert->execute();
    $insert->close();

    $_SESSION['utilities_success'] = "Account for $first_name $last_name created and active.";
    header("Location: ../pages/admin.php?tab=utilities");
    exit();
}

/**
 * ------------------------------------------------------------------
 * EDIT USER
 * ------------------------------------------------------------------
 */
if (isset($_POST['edit_user'])) {
    $id         = (int) ($_POST['id'] ?? 0);
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $role       = $_POST['role'] ?? '';

    if ($id <= 0 || $first_name === '' || $last_name === '' || $email === '' || !in_array($role, $allowedRoles, true)) {
        $_SESSION['utilities_error'] = 'Please fill in every field with a valid role.';
        header("Location: ../pages/admin.php?tab=utilities&edit_id=$id");
        exit();
    }

    // FIX: same self-target safety net as set_status/delete_user, just
    // applied to role instead of status. The admin's own account shows
    // up as a normal row in this same table, so nothing was stopping
    // an admin from editing themselves and picking "User" or
    // "Technician" from the role dropdown — silently demoting their
    // own account. The session stays logged in for the rest of that
    // browser session (role is cached at login), but the next real
    // login sends them to the wrong dashboard, and admin.php's guard
    // correctly bounces them to login_signup.php since they genuinely
    // aren't an admin anymore. This looked like "Edit logs me out" but
    // was really "Edit let me remove my own admin access."
    $selfCheck = require_prepared($conn->prepare("SELECT email FROM users WHERE id = ?"), $conn);
    $selfCheck->bind_param("i", $id);
    $selfCheck->execute();
    $selfCheck->bind_result($current_email);
    $selfCheck->fetch();
    $selfCheck->close();

    if ($role !== 'admin' && $current_email === $_SESSION['email']) {
        $_SESSION['utilities_error'] = "You can't remove your own admin role while logged in as this account.";
        header("Location: ../pages/admin.php?tab=utilities&edit_id=$id");
        exit();
    }

    // Email must stay unique, but excluding this row's own current
    // email (otherwise saving a form without changing the email would
    // incorrectly flag itself as a duplicate).
    $checkEmail = require_prepared($conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?"), $conn);
    $checkEmail->bind_param("si", $email, $id);
    $checkEmail->execute();
    $checkEmail->store_result();
    if ($checkEmail->num_rows > 0) {
        $checkEmail->close();
        $_SESSION['utilities_error'] = 'That email is already used by another account.';
        header("Location: ../pages/admin.php?tab=utilities&edit_id=$id");
        exit();
    }
    $checkEmail->close();

    $update = require_prepared($conn->prepare(
        "UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ? WHERE id = ?"
    ), $conn);
    $update->bind_param("ssssi", $first_name, $last_name, $email, $role, $id);
    $updateOk = $update->execute();

    // FIX: same silent-failure gap as the earlier delete bug — execute()
    // returning true doesn't mean a row actually changed. Surface a
    // real error instead of a false "Account updated." message.
    if (!$updateOk) {
        $dbError = $conn->error;
        error_log("user_admin_mngmnt.php: edit_user failed for id=$id — " . $dbError);
        $update->close();
        $_SESSION['utilities_error'] = 'Could not update that account: ' . $dbError;
        header("Location: ../pages/admin.php?tab=utilities&edit_id=$id");
        exit();
    }

    $update->close();

    $_SESSION['utilities_success'] = 'Account updated.';
    header("Location: ../pages/admin.php?tab=utilities");
    exit();
}

/**
 * ------------------------------------------------------------------
 * SET STATUS (Activate / Deactivate)
 * ------------------------------------------------------------------
 * This is also how a pending self-signup gets approved — flipping a
 * row from 'inactive' to 'active' is exactly what unblocks the login
 * check added in user_mngmnt.php.
 */
if (isset($_POST['set_status'])) {
    $id     = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($id <= 0 || !in_array($status, $allowedStatuses, true)) {
        header("Location: ../pages/admin.php?tab=utilities");
        exit();
    }

    // Safety net: don't let an admin deactivate the account they're
    // currently logged in as — that would lock them out with no other
    // active admin necessarily available to undo it.
    $selfCheck = require_prepared($conn->prepare("SELECT email FROM users WHERE id = ?"), $conn);
    $selfCheck->bind_param("i", $id);
    $selfCheck->execute();
    $selfCheck->bind_result($target_email);
    $selfCheck->fetch();
    $selfCheck->close();

    if ($status === 'inactive' && $target_email === $_SESSION['email']) {
        $_SESSION['utilities_error'] = "You can't deactivate your own account while logged in as it.";
        header("Location: ../pages/admin.php?tab=utilities");
        exit();
    }

    $update = require_prepared($conn->prepare("UPDATE users SET status = ? WHERE id = ?"), $conn);
    $update->bind_param("si", $status, $id);
    $updateOk = $update->execute();

    // FIX: same silent-failure gap as the earlier delete/edit bugs —
    // execute() returning true doesn't mean the row's status actually
    // changed. Surface a real error instead of a false "Account
    // deactivated." message when nothing was actually updated.
    if (!$updateOk || $update->affected_rows === 0) {
        $dbError = $conn->error ?: 'no matching row was updated';
        error_log("user_admin_mngmnt.php: set_status failed for id=$id — " . $dbError);
        $update->close();
        $_SESSION['utilities_error'] = 'Could not update that account: ' . $dbError;
        header("Location: ../pages/admin.php?tab=utilities");
        exit();
    }

    $update->close();

    $_SESSION['utilities_success'] = $status === 'active' ? 'Account activated.' : 'Account deactivated.';
    header("Location: ../pages/admin.php?tab=utilities");
    exit();
}

/**
 * ------------------------------------------------------------------
 * DELETE USER
 * ------------------------------------------------------------------
 * Permanently removes an account row. Unlike set_status (a reversible
 * flag flip), this can't be undone, so it gets the same self-target
 * safety net as set_status: an admin can't delete the account they're
 * currently logged in as.
 */
if (isset($_POST['delete_user'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        header("Location: ../pages/admin.php?tab=utilities");
        exit();
    }

    // FIX: wrap the whole delete flow in try/catch as a second safety
    // net on top of config.php's mysqli_report(MYSQLI_REPORT_OFF). If
    // anything throws here for any reason, we still land back on the
    // admin dashboard with a clear error instead of a raw crash page —
    // which is what was making it look like the admin got logged out
    // (the session itself was never touched; the request just never
    // finished).
    try {
        $selfCheck = require_prepared($conn->prepare("SELECT email FROM users WHERE id = ?"), $conn);
        $selfCheck->bind_param("i", $id);
        $selfCheck->execute();
        $selfCheck->bind_result($target_email);
        $found = $selfCheck->fetch();
        $selfCheck->close();

        if (!$found) {
            $_SESSION['utilities_error'] = 'That account no longer exists.';
            header("Location: ../pages/admin.php?tab=utilities");
            exit();
        }

        if ($target_email === $_SESSION['email']) {
            $_SESSION['utilities_error'] = "You can't delete your own account while logged in as it.";
            header("Location: ../pages/admin.php?tab=utilities");
            exit();
        }

        $delete = require_prepared($conn->prepare("DELETE FROM users WHERE id = ?"), $conn);
        $delete->bind_param("i", $id);
        $deleteOk = $delete->execute();

        // execute() returning true only means the statement ran without
        // a fatal error — it does NOT mean a row was actually removed.
        // A failed DELETE (e.g. blocked by a foreign key constraint if
        // another table like tickets references users.id) or a delete
        // that matched zero rows both looked identical to success
        // before this check.
        if (!$deleteOk || $delete->affected_rows === 0) {
            $dbError = $conn->error ?: 'no matching row was removed';
            error_log("user_admin_mngmnt.php: delete_user failed for id=$id — " . $dbError);
            $delete->close();
            $_SESSION['utilities_error'] = 'Could not delete that account: ' . $dbError;
            header("Location: ../pages/admin.php?tab=utilities");
            exit();
        }

        $delete->close();

        $_SESSION['utilities_success'] = 'Account deleted.';
        header("Location: ../pages/admin.php?tab=utilities");
        exit();
    } catch (\Throwable $e) {
        // Catches mysqli_sql_exception (e.g. FK constraint violation,
        // error 1451) or anything else unexpected. Without this, an
        // uncaught exception here would crash the request with a raw
        // PHP error page instead of redirecting back to the dashboard.
        error_log('user_admin_mngmnt.php: delete_user threw — ' . $e->getMessage());
        $_SESSION['utilities_error'] = 'Could not delete that account: ' . $e->getMessage();
        header("Location: ../pages/admin.php?tab=utilities");
        exit();
    }
}

// No recognized action in the POST body — just bounce back rather
// than falling through with no response.
header("Location: ../pages/admin.php?tab=utilities");
exit();