<?php
/**
 * session_config.php
 * ----------------------------------------------------------------------
 * Centralizes session configuration and session_start() for the whole app.
 * Uses a project-local sessions/ folder and a cookie path scoped to this
 * app's URL prefix (e.g. /CP2_v1.2/) so refresh and logic/ ↔ pages/
 * requests share the same PHPSESSID on XAMPP subdirectory installs.
 * ----------------------------------------------------------------------
 */

if (!function_exists('app_session_base_path')) {
    /**
     * Web path prefix for this project, e.g. "/CP2_v1.2/" when the app
     * lives at http://localhost/CP2_v1.2/pages/admin.php
     */
    function app_session_base_path(): string {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        if (preg_match('#^(/.+?)/(?:pages|logic)/#', $scriptName, $matches)) {
            return $matches[1] . '/';
        }

        return '/';
    }
}

$sessionPath = __DIR__ . '/../sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0700, true);
}

if (!is_writable($sessionPath)) {
    error_log("session_config.php: sessions folder is not writable — $sessionPath");
}

session_save_path($sessionPath);

$sessionLifetime = 60 * 60 * 8;
$sessionCookiePath = app_session_base_path();
$sessionIsSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', (string) $sessionLifetime);

session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path'     => $sessionCookiePath,
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $sessionIsSecure,
]);

$sessionStarted = session_start();

if (!$sessionStarted) {
    error_log('session_config.php: session_start() returned false.');
} elseif (isset($_COOKIE[session_name()]) && !isset($_SESSION['email'])) {
    error_log(sprintf(
        'session_config.php: cookie present (session id: %s) but no $_SESSION[email] — %s',
        session_id(),
        $_SERVER['SCRIPT_NAME'] ?? 'unknown'
    ));
}

/**
 * Re-send the session cookie on authenticated requests so the browser
 * keeps the same id/path and the 8-hour lifetime stays fresh.
 */
if (isset($_SESSION['email']) && !headers_sent()) {
    setcookie(session_name(), session_id(), [
        'expires'  => time() + $sessionLifetime,
        'path'     => $sessionCookiePath,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $sessionIsSecure,
    ]);
}

/**
 * Persist session data before a redirect (helps on some Windows/XAMPP setups).
 */
function app_session_commit(): void {
    session_write_close();
}
