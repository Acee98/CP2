<?php
/**
 * ticket_mngmnt.php
 * ----------------------------------------------------------------------
 * Handles "Submit New Ticket" from pages/ticket.php.
 * Inserts a row into the tickets table and redirects back to the
 * user dashboard with a one-shot success/error message in the session.
 * ----------------------------------------------------------------------
 */

require_once '../logic/session_config.php';
require_once '../logic/config.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'user') {
    header('Location: ../pages/login_signup.php');
    exit();
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    // Session predates user_id being stored — look it up once by email.
    $lookup = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    if ($lookup === false) {
        $_SESSION['ticket_error'] = 'Could not verify your account. Please log out and log in again.';
        header('Location: ../pages/user.php');
        exit();
    }
    $lookup->bind_param('s', $_SESSION['email']);
    $lookup->execute();
    $lookup->bind_result($foundId);
    if (!$lookup->fetch()) {
        $lookup->close();
        header('Location: ../pages/login_signup.php');
        exit();
    }
    $userId = (int) $foundId;
    $lookup->close();
    $_SESSION['user_id'] = $userId;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/ticket.php');
    exit();
}

$allowedCategories = ['hardware', 'software', 'network', 'account', 'other'];
$category    = strtolower(trim($_POST['category'] ?? ''));
$subject     = trim($_POST['subject'] ?? '');
$description = trim($_POST['description'] ?? '');

if (!in_array($category, $allowedCategories, true)) {
    $_SESSION['ticket_error'] = 'Please select a valid issue category.';
    header('Location: ../pages/ticket.php');
    exit();
}

if ($subject === '' || mb_strlen($subject) > 255) {
    $_SESSION['ticket_error'] = 'Subject is required and must be 255 characters or fewer.';
    header('Location: ../pages/ticket.php');
    exit();
}

if ($description === '') {
    $_SESSION['ticket_error'] = 'Please describe your issue before submitting.';
    header('Location: ../pages/ticket.php');
    exit();
}

$status = 'pending';

// Priority (severity) is assigned later by AI — not set at submission time.
$insert = $conn->prepare(
    'INSERT INTO tickets (user_id, subject, description, category, status)
     VALUES (?, ?, ?, ?, ?)'
);

if ($insert === false) {
    error_log('ticket_mngmnt.php: prepare() failed — ' . $conn->error);
    $_SESSION['ticket_error'] = 'Ticket submission is unavailable right now. '
        . 'Make sure you ran database/setup.sql in phpMyAdmin.';
    header('Location: ../pages/ticket.php');
    exit();
}

$insert->bind_param('issss', $userId, $subject, $description, $category, $status);
$ok = $insert->execute();
$newId = $insert->insert_id;
$insert->close();

if (!$ok) {
    error_log('ticket_mngmnt.php: execute() failed — ' . $conn->error);
    $_SESSION['ticket_error'] = 'Could not save your ticket. Please try again.';
    header('Location: ../pages/ticket.php');
    exit();
}

$_SESSION['ticket_success'] = 'Ticket #' . str_pad((string) $newId, 4, '0', STR_PAD_LEFT)
    . ' was submitted successfully. You can track it below.';
header('Location: ../pages/user.php');
exit();
