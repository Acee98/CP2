<?php
/**
 * session_config.php
 * ----------------------------------------------------------------------
 * Centralizes session configuration and session_start() so every file
 * that participates in login state (admin.php, user.php,
 * login_signup.php, user_mngmnt.php, user_admin_mngmnt.php) configures
 * the session identically and reads/writes it from the same,
 * explicitly-set location on disk — instead of each file separately
 * duplicating the same block, and instead of relying on PHP's default
 * system temp directory for session storage.
 *
 * WHY A DEDICATED SESSION FOLDER:
 * The system temp directory's availability/permissions can be
 * inconsistent between requests on some Windows/XAMPP setups —
 * antivirus software scanning and periodically clearing temp files,
 * per-worker-process temp scoping, cleanup utilities, etc. When a
 * session file goes missing from under an otherwise-valid session
 * cookie, the *cookie* still arrives on every request (so it looks
 * like nothing is wrong), but $_SESSION comes back empty — so
 * admin.php/user.php's guard correctly sees no email/role and sends
 * the person to login. That intermittent, environment-dependent
 * pattern is exactly what "sometimes it works, sometimes it doesn't"
 * looks like from the outside, and it isn't something more `header()`
 * or `if` logic in the app can fix — the storage itself needs to be
 * pinned down. A folder inside this project, dedicated only to this
 * app's sessions, removes that dependency.
 * ----------------------------------------------------------------------
 */

$sessionPath = __DIR__ . '/../sessions';
if (!is_dir($sessionPath)) {
    // 0700: only the web server process can read/write these — session
    // files hold everything from someone's email to whether they're an
    // admin, so this folder should never be world-readable.
    mkdir($sessionPath, 0700, true);
}

// FIX: PHP's file-based session handler does NOT throw or fail loudly
// if it can't read/write a session file — it just silently starts a
// brand-new, empty session for that one request instead. That is
// almost certainly what "randomly redirected to login, but not
// actually logged out" has been: on the odd request where the
// sessions/ folder is briefly inaccessible (a Windows file lock from
// an antivirus scan, a permissions hiccup, etc.), PHP quietly hands
// back an empty $_SESSION instead of erroring — the guard correctly
// sees no email/role and redirects — and the NEXT request, once
// whatever briefly blocked access clears, reads the real session file
// fine again. That "works again right after" is the signature of a
// storage access hiccup, not a real logout.
//
// This check surfaces that instead of letting it happen invisibly.
if (!is_writable($sessionPath)) {
    error_log("session_config.php: sessions folder is not writable — $sessionPath");
}

session_save_path($sessionPath);

// 8-hour session lifetime, applied site-wide (not scoped to just
// /pages/ or /logic/), so a normal workday of use — or a plain page
// reload — never looks like an expired session on its own.
$sessionLifetime = 60 * 60 * 8;
ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

$sessionStarted = session_start();

// FIX: log if session_start() itself reports failure, or if a session
// cookie came in but the resulting session is missing the identity
// data — both are the "silently got an empty session" scenario above.
// This fires on every occurrence (not just once), so repeated log
// lines with the same pattern of script names/times will show whether
// this correlates with anything (time of day, specific pages, etc.).
if (!$sessionStarted) {
    error_log('session_config.php: session_start() returned false — session could not be started/resumed.');
} elseif (isset($_COOKIE[session_name()]) && !isset($_SESSION['email'])) {
    error_log(sprintf(
        'session_config.php: cookie present (session id: %s) but no $_SESSION[\'email\'] — requested via %s',
        session_id(),
        $_SERVER['SCRIPT_NAME'] ?? 'unknown'
    ));
}