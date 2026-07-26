<?php
/**
 * ticket_techn_mngmnt.php
 * ----------------------------------------------------------------------
 * Technician handler for techn.php Tickets tab:
 *   - save_ticket  Update status on a ticket assigned to this technician
 * ----------------------------------------------------------------------
 */

require_once '../logic/session_config.php';
require_once '../logic/config.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'techn') {
    header('Location: ../pages/login_signup.php');
    exit();
}

function techn_tickets_redirect_error($message) {
    $_SESSION['tickets_error'] = $message;
    header('Location: ../pages/techn.php?tab=tickets');
    exit();
}

function techn_tickets_redirect_success($message) {
    $_SESSION['tickets_success'] = $message;
    header('Location: ../pages/techn.php?tab=tickets');
    exit();
}

function techn_require_prepared($stmt, $conn) {
    if ($stmt === false) {
        error_log('ticket_techn_mngmnt.php: prepare() failed — ' . $conn->error);
        techn_tickets_redirect_error('Something went wrong. Please try again shortly.');
    }
    return $stmt;
}

$allowedStatuses = ['pending', 'ongoing', 'processing', 'resolved'];

if (!isset($_POST['save_ticket'])) {
    header('Location: ../pages/techn.php?tab=tickets');
    exit();
}

$technId  = (int) ($_SESSION['user_id'] ?? 0);
$ticketId = (int) ($_POST['ticket_id'] ?? 0);
$status   = $_POST['status'] ?? '';

if ($technId <= 0) {
    $lookup = techn_require_prepared(
        $conn->prepare('SELECT id FROM users WHERE email = ? AND role = ? LIMIT 1'),
        $conn
    );
    $role = 'techn';
    $lookup->bind_param('ss', $_SESSION['email'], $role);
    $lookup->execute();
    $lookup->bind_result($foundId);
    if (!$lookup->fetch()) {
        $lookup->close();
        header('Location: ../pages/login_signup.php');
        exit();
    }
    $technId = (int) $foundId;
    $lookup->close();
    $_SESSION['user_id'] = $technId;
}

if ($ticketId <= 0) {
    techn_tickets_redirect_error('Invalid ticket.');
}

if (!in_array($status, $allowedStatuses, true)) {
    techn_tickets_redirect_error('Invalid status selected.');
}

$update = techn_require_prepared(
    $conn->prepare(
        'UPDATE tickets SET status = ? WHERE id = ? AND assigned_to = ?'
    ),
    $conn
);
$update->bind_param('sii', $status, $ticketId, $technId);

if (!$update->execute()) {
    error_log('ticket_techn_mngmnt.php: execute() failed — ' . $conn->error);
    $update->close();
    techn_tickets_redirect_error('Could not update that ticket. Please try again.');
}

if ($update->affected_rows === 0) {
    $update->close();
    techn_tickets_redirect_error('That ticket is not assigned to you or no longer exists.');
}
$update->close();

$ticketLabel = '#' . str_pad((string) $ticketId, 4, '0', STR_PAD_LEFT);
techn_tickets_redirect_success("Ticket $ticketLabel updated successfully.");
