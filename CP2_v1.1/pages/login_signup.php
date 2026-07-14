<?php

// Start the session so we can read any error messages / active-form
// state that user_mngmnt.php may have stored before redirecting here.
// FIX: session setup now centralized in session_config.php — see
// that file for why (dedicated session storage folder + consistent
// cookie config across every session-using page).
require_once '../logic/session_config.php';

/**
 * Build an $errors array holding any login/signup error messages that
 * were stashed in the session by user_mngmnt.php (e.g. "Email is
 * already registered." or "Incorrect credentials."). If nothing was
 * set, default to an empty string for each key.
 */
$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'signup' => $_SESSION['signup_error'] ?? ''
];

/**
 * Determine which form (login or signup) should be shown as "active"
 * (i.e. visible) when the page loads:
 * 1. Prefer $_SESSION['active_form'] if user_mngmnt.php set it
 *    (e.g. after a failed login/signup, to keep the same form open).
 * 2. Otherwise fall back to a ?form= query string value
 *    (e.g. landing_page.php links to login_signup.php?form=signup).
 * 3. If neither is present, default to 'login'.
 */
$activeForm = $_SESSION['active_form'] ?? ($_GET['form'] ?? 'login');

// FIX: this used to be session_unset(), which clears the ENTIRE
// session — not just the few keys this page actually reads. Since
// browser tabs share one session (same cookie), that meant loading
// this page in ANY tab (e.g. a Signup tab open alongside an Admin
// dashboard tab in another tab of the same browser) wiped out
// $_SESSION['email'] / ['role'] for every other tab too — making an
// already-logged-in admin look logged out the moment they switched
// back, even though nothing about their own session actually expired.
// Only clearing the specific keys this page owns leaves any unrelated
// session data (like another tab's logged-in identity) untouched.
unset(
    $_SESSION['login_error'],
    $_SESSION['signup_error'],
    $_SESSION['signup_success'],
    $_SESSION['active_form']
);

/**
 * showError()
 * ------------------------------------------------------------------
 * Renders an error message as a styled <div> if an error string was
 * passed in; returns an empty string otherwise (so nothing is shown).
 *
 * @param string $error  The error message text (or empty if none).
 * @return string         HTML markup for the error box, or ''.
 */
function showError($error) {
    // FIX: escape with htmlspecialchars() before injecting into HTML.
    // Today $error only ever holds two hardcoded strings set in
    // user_mngmnt.php, so this wasn't exploitable yet — but it was
    // injecting a session-derived string straight into the page with no
    // escaping at all, which becomes an XSS hole the moment any error
    // message is built from user input. Escaping now costs nothing and
    // removes that landmine.
    return !empty($error) ? "<div style='padding:12px; background-color: #bd8084; color: #ff0011; border-radius:6px; text-align:center; margin-bottom:20px; color:#7e030b; font-weight:600; font-size: 14px; width:100%;'>" . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . "</div>" : '';
}

/**
 * isActiveForm()
 * ------------------------------------------------------------------
 * Compares a given form name against the currently active form name
 * and returns the CSS class "active" if they match, or an empty
 * string otherwise. This class is what controls (via login_signup.css
 * .form-box.active) which form box is visible on the page.
 *
 * @param string $formName    The form being checked ('login' or 'signup').
 * @param string $activeForm  The form that should currently be shown.
 * @return string              "active" or "".
 */
function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'active' : '';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/login_signup.css">
    <title>ZPGC Services | Login/Signup</title>
</head>
<body>
    <div class="container">
        <!-- Logo links back to the landing page -->
        <div class="logo">
            <a href="../pages/landing_page.php">
                <img src="../images/ZPGC.com2.png" alt="ZPGC Services">
            </a>
        </div>

        <!--
            LOGIN FORM BOX
            - Gets the "active" class (and is therefore visible) when
              $activeForm === 'login'.
            - Submits to user_mngmnt.php, which checks credentials and
              either redirects to the right dashboard or sends the user
              back here with an error.
        -->
        <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
            <form action="../logic/user_mngmnt.php" method="post">
                <h1>LOGIN</h1>
                <?= showError($errors['login']); ?>
                <h5>Enter your credentials to access, create, or track your ticket</h5>

                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <!-- name="login" is what user_mngmnt.php checks via isset($_POST['login']) -->
                <button type="submit" name="login">Login</button>
                <!-- Clicking this link calls showForm('signup-form') from script.js
                     to switch forms client-side without reloading the page -->
                <p>Don't have an account?<a href="#" onclick="showForm('signup-form')"> Signup now!</a></p>
            </form>
        </div>

        <!--
            SIGNUP FORM BOX
            - Gets the "active" class (and is therefore visible) when
              $activeForm === 'signup'.
            - Submits to user_mngmnt.php, which checks for a duplicate
              email and either creates the account or sends the user
              back here with an error.
        -->
        <div class="form-box <?= isActiveForm('signup', $activeForm); ?>" id="signup-form">
            <form action="../logic/user_mngmnt.php" method="post">
                <h1>SIGNUP</h1>
                <?= showError($errors['signup']); ?>
                <h5>Enter your credentials to create your ZPGC account</h5>

                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <!-- Role selected here determines which dashboard the user
                     is sent to on future logins (admin/techn/user) -->
                <select name="role" required>
                    <option value="" disabled selected>Role</option>
                    <option value="user">User</option>
                    <option value="admin">Administrator</option>
                    <option value="techn">Technician</option>
                </select>
                <!-- name="signup" is what user_mngmnt.php checks via isset($_POST['signup']) -->
                <button type="submit" name="signup">Signup</button>
                <!-- Clicking this link calls showForm('login-form') from script.js
                     to switch forms client-side without reloading the page -->
                <p>Already have an account?<a href="#" onclick="showForm('login-form')"> Login now!</a></p>
            </form>
        </div>
    </div>

    <!-- Handles client-side switching between login/signup forms -->
    <script src="../js/script.js"></script>
</body>
</html>