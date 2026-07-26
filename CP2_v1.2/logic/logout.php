<?php
/**
 * logout.php — destroys the session and sends the user to the login page.
 */

require_once '../logic/session_config.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => app_session_base_path(),
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

session_destroy();
header('Location: ../pages/login_signup.php');
exit();
