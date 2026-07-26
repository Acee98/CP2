<?php
/**
 * message_mngmnt.php — send a chat message on a ticket (Mailbox).
 */

require_once '../logic/session_config.php';
require_once '../logic/config.php';
require_once '../logic/mailbox_helpers.php';

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    header('Location: ../pages/login_signup.php');
    exit();
}

$role = $_SESSION['role'];
$allowedRoles = ['user', 'techn', 'admin'];
if (!in_array($role, $allowedRoles, true)) {
    header('Location: ../pages/login_signup.php');
    exit();
}

$redirectPages = [
    'user'  => '../pages/user.php',
    'techn' => '../pages/techn.php',
    'admin' => '../pages/admin.php',
];

function mailbox_redirect_error(string $role, array $redirectPages, string $message, int $ticketId = 0): void {
    $_SESSION['mailbox_error'] = $message;
    $url = $redirectPages[$role] . '?tab=messages';
    if ($ticketId > 0) {
        $url .= '&ticket_id=' . $ticketId;
    }
    header('Location: ' . $url);
    exit();
}

function mailbox_redirect_success(string $role, array $redirectPages, int $ticketId): void {
    $_SESSION['mailbox_success'] = 'Message sent.';
    header('Location: ' . $redirectPages[$role] . '?tab=messages&ticket_id=' . $ticketId);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['send_message'])) {
    header('Location: ' . $redirectPages[$role] . '?tab=messages');
    exit();
}

$userId   = mailbox_resolve_user_id($conn, $_SESSION['email'], $role);
$ticketId = (int) ($_POST['ticket_id'] ?? 0);
$message  = trim($_POST['message'] ?? '');

if ($userId <= 0) {
    mailbox_redirect_error($role, $redirectPages, 'Could not verify your account. Please log in again.');
}

if ($ticketId <= 0) {
    mailbox_redirect_error($role, $redirectPages, 'Invalid ticket.');
}

if ($message === '') {
    mailbox_redirect_error($role, $redirectPages, 'Message cannot be empty.', $ticketId);
}

if (mb_strlen($message) > 5000) {
    mailbox_redirect_error($role, $redirectPages, 'Message is too long (5000 characters max).', $ticketId);
}

if (!mailbox_can_access_ticket($conn, $ticketId, $role, $userId)) {
    mailbox_redirect_error($role, $redirectPages, 'You do not have access to that conversation.', $ticketId);
}

$insert = $conn->prepare(
    'INSERT INTO messages (ticket_id, sender_id, message) VALUES (?, ?, ?)'
);
if ($insert === false) {
    error_log('message_mngmnt.php: prepare() failed — ' . $conn->error);
    mailbox_redirect_error($role, $redirectPages, 'Could not send message. Please try again.', $ticketId);
}

$insert->bind_param('iis', $ticketId, $userId, $message);
if (!$insert->execute()) {
    error_log('message_mngmnt.php: execute() failed — ' . $conn->error);
    $insert->close();
    mailbox_redirect_error($role, $redirectPages, 'Could not send message. Please try again.', $ticketId);
}
$insert->close();

mailbox_redirect_success($role, $redirectPages, $ticketId);
