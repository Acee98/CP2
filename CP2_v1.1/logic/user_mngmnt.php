<?php

// Start (or resume) the PHP session so we can store/read data like
// the logged-in user's info and any error messages across page loads.
// FIX: session setup now centralized in session_config.php — see
// that file for why (dedicated session storage folder + consistent
// cookie config across every session-using page).
require_once '../logic/session_config.php';

// Pull in the database connection ($conn) defined in config.php so this
// script can run queries against the users table.
require_once '../logic/config.php';

/**
 * require_prepared()
 * ------------------------------------------------------------------
 * $conn->prepare() returns `false` (not an exception) if the SQL is
 * invalid — most commonly here because a referenced column doesn't
 * exist yet (e.g. the `status` column from config.php's migration
 * note hasn't been run). Calling ->bind_param() on that `false`
 * throws "Call to a member function bind_param() on bool", which is a
 * confusing fatal error + raw stack trace (exposing the server's file
 * path) for something that has one very specific, very fixable cause.
 *
 * This checks the result of prepare() right after each call and, if
 * it's false, bails out with a plain redirect + a session error
 * message the person can actually act on, instead of a crash.
 *
 * @param mysqli_stmt|false $stmt  The return value of $conn->prepare().
 * @param mysqli            $conn  The connection, to read ->error from.
 */
function require_prepared($stmt, $conn) {
    if ($stmt === false) {
        error_log('user_mngmnt.php: prepare() failed — ' . $conn->error);
        // Shows the actual DB error on-screen (not just server-side via
        // error_log) while this is still being actively debugged locally
        // — a query silently failing here is exactly what would make
        // *every* login attempt fail, not just new/pending accounts, so
        // seeing the real reason immediately matters more right now than
        // hiding it behind a generic message.
        $_SESSION['login_error'] = 'Login is temporarily broken: ' . $conn->error;
        header("Location: ../pages/login_signup.php");
        exit();
    }
    return $stmt;
}

/**
 * ------------------------------------------------------------------
 * SIGNUP WORKFLOW
 * ------------------------------------------------------------------
 * Triggered when the signup form on login_signup.php is submitted
 * (i.e. the form has a button named "signup").
 *
 * Step-by-step:
 * 1. Grab and store all submitted signup fields from $_POST.
 * 2. Hash the plain-text password before storing it (never store
 *    raw passwords).
 * 3. Check the database to see if the submitted email is already
 *    registered.
 * 4. If the email exists -> save an error message + which form was
 *    active into the session, then redirect back to the signup page
 *    so the error can be displayed there.
 * 5. If the email does NOT exist -> insert the new user record into
 *    the "users" table.
 * 6. Redirect back to the login/signup page either way (success or
 *    duplicate-email case), ending this request.
 * ------------------------------------------------------------------
 */
if (isset($_POST['signup'])) {
    // Read submitted signup form values.
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    // Securely hash the password using PHP's built-in default algorithm
    // (currently bcrypt) so the plain password is never stored.
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // FIX: validate the submitted role against a fixed allow-list instead
    // of trusting the raw $_POST value directly. The <select> in
    // login_signup.php only offers these three values, but a request can
    // be crafted by hand (e.g. via curl/devtools) to send anything, which
    // would otherwise be inserted into the database as-is. Anything not
    // in this list is rejected before it ever reaches a query.
    $allowedRoles = ['user', 'admin', 'techn'];
    $role = $_POST['role'] ?? '';
    if (!in_array($role, $allowedRoles, true)) {
        $_SESSION['signup_error'] = "Invalid role selected.";
        $_SESSION['active_form'] = 'signup';
        header("Location: ../pages/login_signup.php");
        exit();
    }

    // FIX: use a prepared statement instead of interpolating $email
    // directly into the SQL string. The original code
    // ("...WHERE email = '$email'") let anyone submit SQL inside the
    // email field (e.g. ' OR '1'='1) and have it execute as part of the
    // query. Binding the value as a parameter means it's always treated
    // as plain data, never as SQL syntax.
    $checkEmail = require_prepared($conn->prepare("SELECT email FROM users WHERE email = ?"), $conn);
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        // Email is taken -> store an error message in the session so the
        // login/signup page can display it after the redirect, and
        // remember that the signup form should stay open/active.
        $checkEmail->close();
        $_SESSION['signup_error'] = "Email is already registered.";
        $_SESSION['active_form'] = 'signup';
        header("Location: ../pages/login_signup.php");
        exit();
    } else {
        $checkEmail->close();
        // Email is free -> insert the new user record into the database
        // with the hashed password and validated role (user/admin/techn).
        // FIX: prepared statement here too, for the same reason as above —
        // first_name/last_name were also being interpolated unescaped.
        //
        // Admin-approval gating: new self-signups are inserted as
        // status='inactive' rather than immediately usable. They can't
        // log in (see the LOGIN workflow below) until an administrator
        // flips their status to 'active' from the Utilities page. This
        // requires a `status` column on the users table — see the
        // migration note in config.php.
        $status = 'inactive';
        $insert = require_prepared($conn->prepare(
            "INSERT INTO users (first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)"
        ), $conn);
        $insert->bind_param("ssssss", $first_name, $last_name, $email, $password, $role, $status);
        $insert->execute();
        $insert->close();

        // Let the person know their account was created but is waiting
        // on an admin, rather than silently dropping them back on a
        // blank login form. Uses a separate session key (signup_success)
        // from signup_error so login_signup.php can style it as a
        // neutral notice instead of a red error box.
        $_SESSION['signup_success'] = "Your account has been created and is pending admin approval. You'll be able to log in once an administrator activates it.";
        header("Location: ../pages/login_signup.php");
        exit();
    }
}

/**
 * ------------------------------------------------------------------
 * LOGIN WORKFLOW
 * ------------------------------------------------------------------
 * Triggered when the login form on login_signup.php is submitted
 * (i.e. the form has a button named "login").
 *
 * Step-by-step:
 * 1. Grab the submitted email and password from $_POST.
 * 2. Look up the user record matching that email in the database.
 * 3. If a matching record is found, verify the submitted password
 *    against the stored password hash.
 * 4. If the password matches -> store the user's basic info in the
 *    session (used elsewhere to identify who is logged in), then
 *    redirect to the correct dashboard based on their role
 *    (admin -> admin.php, techn -> techn.php, otherwise -> user.php).
 * 5. If no matching user OR the password is wrong -> fall through to
 *    the bottom of the function, store a generic "incorrect
 *    credentials" error in the session, remember the login form was
 *    active, and redirect back to login/signup page.
 * ------------------------------------------------------------------
 */
if (isset($_POST['login'])) {
    // Read submitted login credentials.
    $email = $_POST['email'];
    $password = $_POST['password'];

    // FIX: prepared statement instead of interpolating $email directly
    // into the query string (same SQL-injection issue as the signup
    // workflow above). Selecting named columns + bind_result() instead of
    // SELECT * + get_result(), since get_result() needs the mysqlnd
    // driver, which isn't guaranteed to be enabled — bind_result() works
    // with any mysqli install.
    $stmt = require_prepared($conn->prepare(
        "SELECT first_name, last_name, email, password, role, status FROM users WHERE email = ?"
    ), $conn);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($db_first_name, $db_last_name, $db_email, $db_password_hash, $db_role, $db_status);

    if ($stmt->fetch()) {
        // Email exists -> verify the submitted plain-text password
        // against the stored hash.
        if (password_verify($password, $db_password_hash)) {
            $stmt->close();

            // Admin-approval gating: correct credentials, but the
            // account hasn't been activated by an admin yet. Block the
            // login here — before anything is written to session — and
            // tell them clearly why, rather than a generic "incorrect
            // credentials" message that gives no indication the account
            // even exists.
            if ($db_status !== 'active') {
                $_SESSION['login_error'] = "Your account is pending admin approval. You'll be able to log in once an administrator activates it.";
                $_SESSION['active_form'] = 'login';
                header("Location: ../pages/login_signup.php");
                exit();
            }

            // FIX: force a brand-new session ID (and therefore a fresh
            // Set-Cookie) on every successful login. Without this, a
            // browser that already holds an older PHPSESSID cookie
            // (e.g. from before the cookie path/lifetime were pinned
            // consistently above) just keeps reusing that old cookie
            // forever — session_set_cookie_params() has no effect on a
            // cookie the browser already has, it only applies the next
            // time a NEW cookie is actually issued. Regenerating here
            // guarantees login always hands back a cookie under the
            // current, consistent policy, which is also good practice
            // regardless (prevents session fixation).
            session_regenerate_id(true);

            // Credentials are correct and the account is active -> save
            // basic user info into the session so other pages know who
            // is logged in.
            $_SESSION['first_name'] = $db_first_name;
            $_SESSION['last_name'] = $db_last_name;
            $_SESSION['email'] = $db_email;
            // FIX: role wasn't being stored in session at all — needed so
            // individual pages (e.g. user.php's Profile page) can display
            // the account's role and, together with SS3's role-based
            // access control requirement, guard themselves against a
            // logged-in user of the wrong role loading the wrong dashboard.
            $_SESSION['role'] = $db_role;

            // Redirect to the correct dashboard depending on the user's role.
            if ($db_role === 'admin') {
                header("Location: ../pages/admin.php");
            } else if ($db_role === 'techn') {
                header("Location: ../pages/techn.php");
            } else {
                header("Location: ../pages/user.php");
            }
            exit();
        }
    }

    $stmt->close();

    // Reached only if the email wasn't found OR the password didn't match.
    // Store a generic error (so we don't reveal whether the email exists)
    // and remember that the login form should remain active after redirect.
    $_SESSION['login_error'] = 'Incorrect credentials. Please input your correct email or password.';
    $_SESSION['active_form'] = 'login';
    header("Location: ../pages/login_signup.php");
    exit();
}
?>