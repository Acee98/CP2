<?php
/**
 * mailbox_api.php — JSON API for live mailbox updates (poll + send).
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../logic/session_config.php';
require_once '../logic/config.php';
require_once '../logic/mailbox_helpers.php';

function mailbox_json_response(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

function mailbox_json_error(string $message, int $code = 400): void {
    mailbox_json_response(['ok' => false, 'error' => $message], $code);
}

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    mailbox_json_error('Unauthorized', 401);
}

$role = $_SESSION['role'];
$allowedRoles = ['user', 'techn', 'admin'];
if (!in_array($role, $allowedRoles, true)) {
    mailbox_json_error('Forbidden', 403);
}

$userId = mailbox_resolve_user_id($conn, $_SESSION['email'], $role);
if ($userId <= 0) {
    mailbox_json_error('Could not verify your account.', 401);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'send' || isset($_POST['send_message']))) {
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $message  = trim($_POST['message'] ?? '');

    if ($ticketId <= 0) {
        mailbox_json_error('Invalid ticket.');
    }

    if ($message === '') {
        mailbox_json_error('Message cannot be empty.');
    }

    if (mb_strlen($message) > 5000) {
        mailbox_json_error('Message is too long (5000 characters max).');
    }

    if (!mailbox_can_access_ticket($conn, $ticketId, $role, $userId)) {
        mailbox_json_error('You do not have access to that conversation.', 403);
    }

    $insert = $conn->prepare(
        'INSERT INTO messages (ticket_id, sender_id, message) VALUES (?, ?, ?)'
    );
    if ($insert === false) {
        error_log('mailbox_api.php: prepare() failed — ' . $conn->error);
        mailbox_json_error('Could not send message. Please try again.', 500);
    }

    $insert->bind_param('iis', $ticketId, $userId, $message);
    if (!$insert->execute()) {
        error_log('mailbox_api.php: execute() failed — ' . $conn->error);
        $insert->close();
        mailbox_json_error('Could not send message. Please try again.', 500);
    }

    $messageId = (int) $conn->insert_id;
    $insert->close();

    $newMessage = mailbox_fetch_message_by_id($conn, $messageId);
    if ($newMessage === null) {
        mailbox_json_error('Message sent but could not be loaded.', 500);
    }

    mailbox_json_response([
        'ok'            => true,
        'message'       => mailbox_message_to_api($newMessage, $userId, $role),
        'conversations' => mailbox_conversations_to_api($conn, $role, $userId),
    ]);
}

if ($action === 'poll' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $ticketId = (int) ($_GET['ticket_id'] ?? 0);
    $afterId  = (int) ($_GET['after_id'] ?? 0);

    $response = [
        'ok'            => true,
        'conversations' => mailbox_conversations_to_api($conn, $role, $userId),
    ];

    if ($ticketId > 0 && mailbox_can_access_ticket($conn, $ticketId, $role, $userId)) {
        $messages = mailbox_fetch_messages_since($conn, $ticketId, $afterId);
        $response['messages']  = mailbox_messages_to_api($messages, $userId, $role);
        $response['ticket_id'] = $ticketId;
    }

    mailbox_json_response($response);
}

mailbox_json_error('Invalid action.', 400);
