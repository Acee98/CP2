<?php

// Start (or resume) the PHP session so we can store/read data like
// the logged-in user's info and any error messages across page loads.
session_start();

// Pull in the database connection ($conn) defined in config.php so this
// script can run queries against the users table.
require_once '../logic/config.php';

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
    $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
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
        $insert = $conn->prepare(
            "INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)"
        );
        $insert->bind_param("sssss", $first_name, $last_name, $email, $password, $role);
        $insert->execute();
        $insert->close();
    }

    // After a successful signup, redirect back to the login/signup page
    // (this will land on the default/login form since no error/active
    // form was set in this branch).
    header("Location: ../pages/login_signup.php");
    exit();
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
    $stmt = $conn->prepare(
        "SELECT first_name, last_name, email, password, role FROM users WHERE email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($db_first_name, $db_last_name, $db_email, $db_password_hash, $db_role);

    if ($stmt->fetch()) {
        // Email exists -> verify the submitted plain-text password
        // against the stored hash.
        if (password_verify($password, $db_password_hash)) {
            // Credentials are correct -> save basic user info into the
            // session so other pages know who is logged in.
            $_SESSION['first_name'] = $db_first_name;
            $_SESSION['last_name'] = $db_last_name;
            $_SESSION['email'] = $db_email;

            $stmt->close();

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