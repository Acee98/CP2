<?php
/**
 * config.php
 * ----------------------------------------------------------------------
 * Database connection configuration for the ZPGC Services system.
 *
 * Workflow:
 * 1. Force mysqli into non-throwing mode (see note below).
 * 2. Define database connection credentials (host, username, password, db name).
 * 3. Open a new MySQLi connection using those credentials.
 * 4. Check if the connection failed; if so, stop execution and show the error.
 * 5. If successful, the $conn object becomes available to any file that
 *    includes/requires this script (e.g. user_mngmnt.php), allowing those
 *    files to run queries against the "users_db" database.
 * ----------------------------------------------------------------------
 */

// FIX: explicitly force mysqli to return false on query/prepare/execute
// failures instead of throwing a mysqli_sql_exception. Some PHP builds
// (and some default php.ini setups) enable MYSQLI_REPORT_ERROR |
// MYSQLI_REPORT_STRICT out of the box. Every script in this project
// (user_mngmnt.php, user_admin_mngmnt.php, etc.) is written expecting
// the classic behavior — checking `$stmt === false` or `$result ===
// false` and handling it gracefully. If exceptions are enabled instead,
// those checks are never reached: a failed query (e.g. a DELETE
// blocked by a foreign key constraint) throws an uncaught exception
// that crashes the request with a raw PHP error page before the
// script's own error handling and redirect logic ever runs — which
// can look like the user got logged out, when really the script just
// never made it back to a normal page. Forcing MYSQLI_REPORT_OFF here,
// once, guarantees the predictable "check the return value" behavior
// everywhere this file is required.
mysqli_report(MYSQLI_REPORT_OFF);

// Database server address. "localhost" means the DB runs on the same
// machine as the web server.
$host = "localhost";

// MySQL username used to authenticate the connection.
$user = "root";

// MySQL password for the above user. Empty string = no password set
// (common in local/dev environments).
$password = "";

// Name of the database this system reads/writes to.
$database = "users_db"; 

// Create a new MySQLi connection object using the credentials above.
// This $conn variable is what other files (after requiring this file)
// will use to run SQL queries (e.g. $conn->query(...)).
$conn = new mysqli($host, $user, $password, $database);

// If the connection attempt failed, immediately stop the script and
// display the connection error message. This prevents the rest of the
// application from running with a broken/missing database connection.
if ($conn->connect_error) {
    die("Connection failed: ". $conn->connect_error);
}

?>