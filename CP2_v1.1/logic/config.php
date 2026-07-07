<?php
/**
 * config.php
 * ----------------------------------------------------------------------
 * Database connection configuration for the ZPGC Services system.
 *
 * Workflow:
 * 1. Define database connection credentials (host, username, password, db name).
 * 2. Open a new MySQLi connection using those credentials.
 * 3. Check if the connection failed; if so, stop execution and show the error.
 * 4. If successful, the $conn object becomes available to any file that
 *    includes/requires this script (e.g. user_mngmnt.php), allowing those
 *    files to run queries against the "users_db" database.
 * ----------------------------------------------------------------------
 */

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