<?php
/**
 * ticket_admin_mngmnt.php
 * ----------------------------------------------------------------------
 * Admin-only handler for the Tickets tab on admin.php:
 *   - save_ticket  Update assigned_to and/or status for one ticket
 * ----------------------------------------------------------------------
 */

require_once '../logic/session_config.php';
require_once '../logic/config.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../pages/login_signup.php');
    exit();
}

function tickets_redirect_error($message) {
    $_SESSION['tickets_error'] = $message;
    header('Location: ../pages/admin.php?tab=tickets');
    exit();
}

function tickets_redirect_success($message) {
    $_SESSION['tickets_success'] = $message;
    header('Location: ../pages/admin.php?tab=tickets');
    exit();
}

function require_prepared($stmt, $conn) {
    if ($stmt === false) {
        error_log('ticket_admin_mngmnt.php: prepare() failed — ' . $conn->error);
        tickets_redirect_error('Something went wrong. Please try again shortly.');
    }
    return $stmt;
}

$allowedStatuses = ['pending', 'ongoing', 'processing', 'resolved'];

if (!isset($_POST['save_ticket'])) {
    header('Location: ../pages/admin.php?tab=tickets');
    exit();
}

$ticketId   = (int) ($_POST['ticket_id'] ?? 0);
$status     = $_POST['status'] ?? '';
$assignedRaw = $_POST['assigned_to'] ?? '';

if ($ticketId <= 0) {
    tickets_redirect_error('Invalid ticket.');
}

if (!in_array($status, $allowedStatuses, true)) {
    tickets_redirect_error('Invalid status selected.');
}

$assignedTo = null;
if ($assignedRaw !== '' && $assignedRaw !== '0') {
    $assignedTo = (int) $assignedRaw;
    if ($assignedTo <= 0) {
        tickets_redirect_error('Invalid technician selected.');
    }

    $techCheck = require_prepared(
        $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'techn' AND status = 'active' LIMIT 1"),
        $conn
    );
    $techCheck->bind_param('i', $assignedTo);
    $techCheck->execute();
    $techCheck->store_result();
    if ($techCheck->num_rows === 0) {
        $techCheck->close();
        tickets_redirect_error('That technician is not available for assignment.');
    }
    $techCheck->close();
}

$exists = require_prepared($conn->prepare('SELECT id FROM tickets WHERE id = ? LIMIT 1'), $conn);
$exists->bind_param('i', $ticketId);
$exists->execute();
$exists->store_result();
if ($exists->num_rows === 0) {
    $exists->close();
    tickets_redirect_error('Ticket not found.');
}
$exists->close();

if ($assignedTo === null) {
    $update = require_prepared(
        $conn->prepare('UPDATE tickets SET status = ?, assigned_to = NULL WHERE id = ?'),
        $conn
    );
    $update->bind_param('si', $status, $ticketId);
} else {
    $update = require_prepared(
        $conn->prepare('UPDATE tickets SET status = ?, assigned_to = ? WHERE id = ?'),
        $conn
    );
    $update->bind_param('sii', $status, $assignedTo, $ticketId);
}

if (!$update->execute()) {
    error_log('ticket_admin_mngmnt.php: execute() failed — ' . $conn->error);
    $update->close();
    tickets_redirect_error('Could not update that ticket. Please try again.');
}
$update->close();

$ticketLabel = '#' . str_pad((string) $ticketId, 4, '0', STR_PAD_LEFT);
tickets_redirect_success("Ticket $ticketLabel updated successfully.");
