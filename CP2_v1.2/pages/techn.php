<?php
/**
 * techn.php — top-of-file PHP
 * ----------------------------------------------------------------------
 * Same session-guard pattern as admin.php/user.php. This file had no
 * PHP at all before this — meaning anyone could open it directly with
 * no login, and My Profile showed the literal placeholder text
 * "Technician Name" instead of whoever was actually logged in.
 */
require_once '../logic/session_config.php';
require_once '../logic/config.php';
require_once '../logic/mailbox_helpers.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'techn') {
    header("Location: ../pages/login_signup.php");
    exit();
}

$first_name = $_SESSION['first_name'];
$last_name  = $_SESSION['last_name'];
$email      = $_SESSION['email'];
$full_name  = trim($first_name . ' ' . $last_name);
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

$tickets_success = $_SESSION['tickets_success'] ?? '';
$tickets_error   = $_SESSION['tickets_error'] ?? '';
unset($_SESSION['tickets_success'], $_SESSION['tickets_error']);

$techn_id = (int) ($_SESSION['user_id'] ?? 0);
if ($techn_id <= 0) {
    $techn_id = mailbox_resolve_user_id($conn, $_SESSION['email'], 'techn');
}

$category_labels = [
    'hardware' => 'Hardware',
    'software' => 'Software',
    'network'  => 'Network',
    'account'  => 'Account',
    'other'    => 'Other',
];

$status_counts = [
    'pending'    => 0,
    'ongoing'    => 0,
    'processing' => 0,
    'resolved'   => 0,
];

$assigned_tickets = [];

if ($techn_id > 0) {
    $countStmt = $conn->prepare(
        'SELECT status, COUNT(*) AS cnt FROM tickets WHERE assigned_to = ? GROUP BY status'
    );
    if ($countStmt !== false) {
        $countStmt->bind_param('i', $techn_id);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        while ($row = $countResult->fetch_assoc()) {
            $key = $row['status'];
            if (isset($status_counts[$key])) {
                $status_counts[$key] = (int) $row['cnt'];
            }
        }
        $countStmt->close();
    } else {
        $tickets_error = $tickets_error ?: ('Could not load ticket counts: ' . $conn->error);
    }

    $ticketStmt = $conn->prepare(
        'SELECT id, subject, description, category, status, ai_severity, created_at
         FROM tickets
         WHERE assigned_to = ?
         ORDER BY created_at DESC'
    );
    if ($ticketStmt === false) {
        $tickets_error = $tickets_error ?: ('Could not load assigned tickets: ' . $conn->error);
    } else {
        $ticketStmt->bind_param('i', $techn_id);
        $ticketStmt->execute();
        $ticketResult = $ticketStmt->get_result();
        while ($row = $ticketResult->fetch_assoc()) {
            $assigned_tickets[] = $row;
        }
        $ticketStmt->close();
    }
}

$recent_tickets = array_slice($assigned_tickets, 0, 5);

$mailbox_success = $_SESSION['mailbox_success'] ?? '';
$mailbox_error   = $_SESSION['mailbox_error'] ?? '';
unset($_SESSION['mailbox_success'], $_SESSION['mailbox_error']);
$mailbox_role = 'techn';
$mailbox_current_user_id = $techn_id;
$mailbox_conversations = $techn_id > 0 ? mailbox_fetch_conversations($conn, 'techn', $techn_id) : [];
$mailbox_active_ticket_id = isset($_GET['ticket_id']) ? (int) $_GET['ticket_id'] : 0;
$mailbox_messages = [];
$mailbox_active_ticket = null;
if ($mailbox_active_ticket_id > 0 && mailbox_can_access_ticket($conn, $mailbox_active_ticket_id, 'techn', $techn_id)) {
    $mailbox_active_ticket = mailbox_fetch_ticket_header($conn, $mailbox_active_ticket_id);
    $mailbox_messages = mailbox_fetch_messages($conn, $mailbox_active_ticket_id);
} else {
    $mailbox_active_ticket_id = 0;
}

$allowed_nav_tabs = ['dashboard', 'tickets', 'messages', 'profile', 'settings'];
$active_nav_tab = $_GET['tab'] ?? 'dashboard';
if (!in_array($active_nav_tab, $allowed_nav_tabs, true)) {
    $active_nav_tab = 'dashboard';
}

function techn_desc_preview($text, $max = 80) {
    return mb_strlen($text) > $max ? mb_substr($text, 0, $max) . '…' : $text;
}

function render_techn_ticket_row(array $ticket, array $category_labels, bool $showActions = true): void {
    $tid = (int) $ticket['id'];
    $statusKey = preg_replace('/[^a-z]/', '', strtolower($ticket['status']));
    $aiPriority = trim((string) ($ticket['ai_severity'] ?? ''));
    $priorityKey = $aiPriority !== ''
        ? preg_replace('/[^a-z]/', '', strtolower($aiPriority))
        : '';
    $catLabel = $category_labels[$ticket['category']] ?? ucfirst($ticket['category']);
    $descPreview = techn_desc_preview($ticket['description']);
    $searchBlob = strtolower(implode(' ', [
        '#' . $tid,
        $ticket['subject'],
        $ticket['description'],
        $catLabel,
        $ticket['category'],
        $ticket['status'],
        $aiPriority,
    ]));
    ?>
    <div class="ticket-row" data-status="<?= htmlspecialchars($ticket['status']) ?>"
        data-search="<?= htmlspecialchars($searchBlob) ?>">
        <span class="ttcol-id">#<?= str_pad((string) $tid, 4, '0', STR_PAD_LEFT) ?></span>
        <span class="ttcol-subject with-description"><?= htmlspecialchars($ticket['subject']) ?></span>
        <span class="ttcol-description"><?= htmlspecialchars($descPreview) ?></span>
        <span class="ttcol-category"><?= htmlspecialchars($catLabel) ?></span>
        <span class="ttcol-status">
            <span class="status-badge <?= htmlspecialchars($statusKey) ?>">
                <?= htmlspecialchars(ucfirst($ticket['status'])) ?>
            </span>
        </span>
        <span class="ttcol-severity">
            <?php if ($aiPriority !== ''): ?>
                <span class="severity-badge <?= htmlspecialchars($priorityKey) ?>">
                    <?= htmlspecialchars(ucfirst($aiPriority)) ?>
                </span>
            <?php else: ?>
                <span class="severity-badge undefined">Undefined</span>
            <?php endif; ?>
        </span>
        <span class="ttcol-action">
            <?php if ($showActions): ?>
                <form class="techn-ticket-form" action="../logic/ticket_techn_mngmnt.php" method="post">
                    <input type="hidden" name="ticket_id" value="<?= $tid ?>">
                    <select name="status" class="admin-ticket-select" aria-label="Ticket status">
                        <?php foreach (['pending', 'ongoing', 'processing', 'resolved'] as $st): ?>
                            <option value="<?= $st ?>" <?= $ticket['status'] === $st ? 'selected' : '' ?>>
                                <?= ucfirst($st) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="save_ticket" class="btn-save-ticket">Save</button>
                </form>
            <?php endif; ?>
        </span>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/main_interface.css?v=2.2">
    <title>ZPGC Services | Technician</title>
</head>

<body data-page="<?= htmlspecialchars($active_nav_tab) ?>">
    <main class="main-wrap">
        <header class="main-head">
            <div class="main-nav">
                <nav class="navbar">
                    <div class="navbar-nav">

                        <div class="logo">
                            <img src="../images/ZPGC.com2.png" alt="">
                            <button class="showcase-toggler">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path d="M3 4h18v2H3zm0 7h18v2H3zm0 7h18v2H3z"></path>
                                </svg>
                            </button>
                        </div>

                        <ul class="nav-list">

                            <!-- Dashboard -->
                            <li class="nav-list-item<?= $active_nav_tab === 'dashboard' ? ' selected' : '' ?>" data-nav="dashboard">
                                <a href="#" class="nav-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M20 11h-6c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-8c0-.55-.45-1-1-1m-1 8h-4v-6h4zm-9-4H4c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-4c0-.55-.45-1-1-1m-1 4H5v-2h4zM20 3h-6c-.55 0-1 .45-1 1v4c0 .55.45 1 1 1h6c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1m-1 4h-4V5h4zm-9-4H4c-.55 0-1 .45-1 1v8c0 .55.45 1 1 1h6c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1m-1 8H5V5h4z">
                                        </path>
                                    </svg>
                                    <span class="link-text">Dashboard</span>
                                </a>
                            </li>

                            <!-- Tickets -->
                            <li class="nav-list-item<?= $active_nav_tab === 'tickets' ? ' selected' : '' ?>" data-nav="tickets">
                                <a href="#" class="nav-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M21 8h-2V3a1 1 0 0 0-1.37-.93l-15 6c-.09.04-.16.1-.24.16-.03.02-.06.03-.09.06-.04.04-.05.08-.08.12-.05.06-.1.12-.13.19-.01.02 0 .05-.02.08-.03.1-.06.2-.06.31v3.55c0 .48.33.89.8.98a1.499 1.499 0 0 1 0 2.94c-.47.09-.8.5-.8.98v3.55c0 .55.45 1 1 1h18c.55 0 1-.45 1-1v-3.55c0-.48-.33-.89-.8-.98a1.499 1.499 0 0 1 0-2.94c.47-.09.8-.5.8-.98V8.99c0-.55-.45-1-1-1Zm-4 0H8.19L17 4.48zm3 3.84c-1.2.57-2 1.79-2 3.16s.8 2.59 2 3.16V20h-4v-2h-1v2H4v-1.84c1.2-.57 2-1.79 2-3.16s-.8-2.59-2-3.16V10h11v1h1v-1h4z">
                                        </path>
                                        <path d="M15 12h1v2h-1zm0 3h1v2h-1z"></path>
                                    </svg>
                                    <span class="link-text">Tickets</span>
                                </a>
                            </li>

                            <!-- Mailbox -->
                            <li class="nav-list-item<?= $active_nav_tab === 'messages' ? ' selected' : '' ?>" data-nav="messages">
                                <a href="#" class="nav-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2m0 2v.51l-8 6.22-8-6.22V6zM4 18V9.04l7.39 5.74c.18.14.4.21.61.21s.43-.07.61-.21L20 9.03v8.96H4Z">
                                        </path>
                                    </svg>
                                    <span class="link-text">Mailbox</span>
                                </a>
                            </li>

                            <!-- My Profile -->
                            <li class="nav-list-item<?= $active_nav_tab === 'profile' ? ' selected' : '' ?>" data-nav="profile">
                                <a href="#" class="nav-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5m0-8c1.65 0 3 1.35 3 3s-1.35 3-3 3-3-1.35-3-3 1.35-3 3-3m0 9c-3.87 0-7 2.24-7 5v2h14v-2c0-2.76-3.13-5-7-5m-5 5c.22-.98 2.68-3 5-3s4.78 2.02 5 3z">
                                        </path>
                                    </svg>
                                    <span class="link-text">My Profile</span>
                                </a>
                            </li>

                            <!-- Settings -->
                            <li class="nav-list-item<?= $active_nav_tab === 'settings' ? ' selected' : '' ?>" data-nav="settings">
                                <a href="#" class="nav-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4m0 6c-1.08 0-2-.92-2-2s.92-2 2-2 2 .92 2 2-.92 2-2 2">
                                        </path>
                                        <path
                                            d="m20.42 13.4-.51-.29c.05-.37.08-.74.08-1.11s-.03-.74-.08-1.11l.51-.29c.96-.55 1.28-1.78.73-2.73l-1-1.73a2.006 2.006 0 0 0-2.73-.73l-.53.31c-.58-.46-1.22-.83-1.9-1.11v-.6c0-1.1-.9-2-2-2h-2c-1.1 0-2 .9-2 2v.6c-.67.28-1.31.66-1.9 1.11l-.53-.31c-.96-.55-2.18-.22-2.73.73l-1 1.73c-.55.96-.22 2.18.73 2.73l.51.29c-.05.37-.08.74-.08 1.11s.03.74.08 1.11l-.51.29c-.96.55-1.28 1.78-.73 2.73l1 1.73c.55.95 1.77 1.28 2.73.73l.53-.31c.58.46 1.22.83 1.9 1.11v.6c0 1.1.9 2 2 2h2c1.1 0 2-.9 2-2v-.6a8.7 8.7 0 0 0 1.9-1.11l.53.31c.95.55 2.18.22 2.73-.73l1-1.73c.55-.96.22-2.18-.73-2.73m-2.59-2.78c.11.45.17.92.17 1.38s-.06.92-.17 1.38a1 1 0 0 0 .47 1.11l1.12.65-1 1.73-1.14-.66c-.38-.22-.87-.16-1.19.14-.68.65-1.51 1.13-2.38 1.4-.42.13-.71.52-.71.96v1.3h-2v-1.3c0-.44-.29-.83-.71-.96-.88-.27-1.7-.75-2.38-1.4a1.01 1.01 0 0 0-1.19-.15l-1.14.66-1-1.73 1.12-.65c.39-.22.58-.68.47-1.11-.11-.45-.17-.92-.17-1.38s.06-.93.17-1.38A1 1 0 0 0 5.7 9.5l-1.12-.65 1-1.73 1.14.66c.38.22.87.16 1.19-.14.68-.65 1.51-1.13 2.38-1.4.42-.13.71-.52.71-.96v-1.3h2v1.3c0 .44.29.83.71.96.88.27 1.7.75 2.38 1.4.32.31.81.36 1.19.14l1.14-.66 1 1.73-1.12.65c-.39.22-.58.68-.47 1.11Z">
                                        </path>
                                    </svg>
                                    <span class="link-text">Settings</span>
                                </a>
                            </li>

                            <!-- Logout -->
                            <li class="nav-list-item">
                                <a href="../logic/logout.php" class="nav-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path d="M9 13h7v-2H9V7l-6 5 6 5z"></path>
                                        <path d="M19 3h-7v2h7v14h-7v2h7c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2"></path>
                                    </svg>
                                    <span class="link-text">Logout</span>
                                </a>
                            </li>

                        </ul>
                    </div>
                </nav>
            </div>
        </header>

        <div class="sidebar-spacer"></div>

        <section class="showcase">

            <!-- ============================================================
                 DASHBOARD — body[data-page="dashboard"]
                 ============================================================ -->
            <div class="page-content techn-dashboard-page" id="page-dashboard">

                <div class="head">
                    <header>
                        <h1>Dashboard</h1>
                        <div class="search-bar-wrapper">
                            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M18 10c0-4.41-3.59-8-8-8s-8 3.59-8 8 3.59 8 8 8c1.85 0 3.54-.63 4.9-1.69l5.1 5.1L21.41 20l-5.1-5.1A8 8 0 0 0 18 10M4 10c0-3.31 2.69-6 6-6s6 2.69 6 6-2.69 6-6 6-6-2.69-6-6">
                                </path>
                            </svg>
                            <input type="search" class="search-bar" placeholder="Search..." aria-label="Search">
                        </div>
                    </header>
                </div>

                <div class="status-cards">

                    <!-- Ongoing -->
                    <div class="status-card tech-accent">
                        <div class="status-card-info">
                            <span class="status-card-count"><?= (int) $status_counts['ongoing'] ?></span>
                            <span class="status-card-label">Ongoing</span>
                        </div>
                        <div class="status-card-icon filled" style="--status-color: #00ABB1;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M4.88 8.42 3.1 7.5a10 10 0 0 0-.98 2.95l1.97.32c.13-.81.39-1.6.78-2.35Zm-2.76 5.14c.17 1.02.5 2.01.98 2.94l1.78-.92c-.38-.74-.65-1.53-.78-2.35l-1.97.32ZM4.92 19c.73.74 1.57 1.36 2.48 1.85l.94-1.77c-.73-.39-1.4-.89-1.99-1.49L4.93 19ZM8.33 4.92l-.94-1.77C6.48 3.64 5.64 4.26 4.91 5l1.42 1.41c.59-.6 1.26-1.1 1.99-1.49ZM12 2c-.56 0-1.12.05-1.67.14l.34 1.97c.44-.08.88-.11 1.32-.11 4.34 0 8 3.66 8 8s-3.66 8-8 8c-.44 0-.89-.04-1.32-.11l-.34 1.97c.55.1 1.11.14 1.67.14 5.42 0 10-4.58 10-10S17.42 2 12 2">
                                </path>
                                <path d="M11 7v6h6v-2h-4V7z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Processing -->
                    <div class="status-card tech-accent">
                        <div class="status-card-info">
                            <span class="status-card-count"><?= (int) $status_counts['processing'] ?></span>
                            <span class="status-card-label">Processing</span>
                        </div>
                        <div class="status-card-icon filled" style="--status-color: #FF8D28;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M18.13 17.13c-.15.18-.31.36-.48.52-.73.74-1.59 1.31-2.54 1.71-1.97.83-4.26.83-6.23 0-.95-.4-1.81-.98-2.54-1.72a7.8 7.8 0 0 1-1.71-2.54c-.42-.99-.63-2.03-.63-3.11H2c0 1.35.26 2.66.79 3.89.5 1.19 1.23 2.26 2.14 3.18s1.99 1.64 3.18 2.14c1.23.52 2.54.79 3.89.79s2.66-.26 3.89-.79c1.19-.5 2.26-1.23 3.18-2.14.17-.17.32-.35.48-.52L22 20.99v-6h-6l2.13 2.13Zm.94-12.2a9.9 9.9 0 0 0-3.18-2.14 10.12 10.12 0 0 0-7.79 0c-1.19.5-2.26 1.23-3.18 2.14-.17.17-.32.35-.48.52L1.99 3v6h6L5.86 6.87c.15-.18.31-.36.48-.52.73-.74 1.59-1.31 2.54-1.71 1.97-.83 4.26-.83 6.23 0 .95.4 1.81.98 2.54 1.72.74.73 1.31 1.59 1.71 2.54.42.99.63 2.03.63 3.11h2c0-1.35-.26-2.66-.79-3.89-.5-1.19-1.23-2.26-2.14-3.18Z">
                                </path>
                            </svg>
                        </div>
                    </div>

                    <!-- Resolved -->
                    <div class="status-card tech-accent">
                        <div class="status-card-info">
                            <span class="status-card-count"><?= (int) $status_counts['resolved'] ?></span>
                            <span class="status-card-label">Resolved</span>
                        </div>
                        <div class="status-card-icon filled" style="--status-color: #34C759;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M12 22C6.49 22 2 17.51 2 12S6.49 2 12 2s10 4.49 10 10-4.49 10-10 10m0-18c-4.41 0-8 3.59-8 8s3.59 8 8 8 8-3.59 8-8-3.59-8-8-8">
                                </path>
                                <path
                                    d="M10 16c-.26 0-.51-.1-.71-.29l-3-3L7.7 11.3l2.29 2.29 5.29-5.29 1.41 1.41-6 6c-.2.2-.45.29-.71.29Z">
                                </path>
                            </svg>
                        </div>
                    </div>

                    <!-- Pending -->
                    <div class="status-card tech-accent">
                        <div class="status-card-info">
                            <span class="status-card-count"><?= (int) $status_counts['pending'] ?></span>
                            <span class="status-card-label">Pending</span>
                        </div>
                        <div class="status-card-icon filled" style="--status-color: #000000;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M5 2H4v2h1v1c0 2.46 1.32 4.77 3.43 6.02.35.21.57.55.57.9v.16c0 .35-.21.69-.57.9A7.01 7.01 0 0 0 5 19v1H4v2h16v-2h-1v-1c0-2.46-1.32-4.77-3.43-6.02-.36-.21-.57-.55-.57-.9v-.16c0-.35.21-.69.57-.9A7.01 7.01 0 0 0 19 5V4h1V2zm12 3c0 1.76-.94 3.41-2.45 4.3-.97.57-1.55 1.55-1.55 2.62v.16c0 1.07.58 2.05 1.55 2.62 1.51.89 2.45 2.54 2.45 4.3v1H7v-1c0-1.76.94-3.41 2.45-4.3.97-.57 1.55-1.55 1.55-2.62v-.16c0-1.07-.58-2.05-1.55-2.62A5.01 5.01 0 0 1 7 5V4h10z">
                                </path>
                            </svg>
                        </div>
                    </div>

                </div>

                <!-- Recent ticket history preview (same table style used in
                     the Tickets page below), so the dashboard mirrors the
                     reference design's single-page table view. -->
                <div class="tickets-toolbar techn-dashboard-toolbar">
                    <div class="tickets-filter-tabs">
                        <span class="techn-dashboard-history-title">Recent Ticket History</span>
                    </div>
                </div>

                <div class="tickets-list techn-dashboard-history-list">
                    <div class="tickets-list-header">
                        <span class="ttcol-id">ID</span>
                        <span class="ttcol-subject with-description">Subject</span>
                        <span class="ttcol-description">Description</span>
                        <span class="ttcol-category">Category</span>
                        <span class="ttcol-status">Status</span>
                        <span class="ttcol-severity">Severity Level</span>
                        <span class="ttcol-action">Action</span>
                    </div>
                    <div class="tickets-list-body">
                        <?php if (empty($recent_tickets)): ?>
                            <div class="tickets-empty-state">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M21 8h-2V3a1 1 0 0 0-1.37-.93l-15 6c-.09.04-.16.1-.24.16-.03.02-.06.03-.09.06-.04.04-.05.08-.08.12-.05.06-.1.12-.13.19-.01.02 0 .05-.02.08-.03.1-.06.2-.06.31v3.55c0 .48.33.89.8.98a1.499 1.499 0 0 1 0 2.94c-.47.09-.8.5-.8.98v3.55c0 .55.45 1 1 1h18c.55 0 1-.45 1-1v-3.55c0-.48-.33-.89-.8-.98a1.499 1.499 0 0 1 0-2.94c.47-.09.8-.5.8-.98V8.99c0-.55-.45-1-1-1Zm-4 0H8.19L17 4.48zm3 3.84c-1.2.57-2 1.79-2 3.16s.8 2.59 2 3.16V20h-4v-2h-1v2H4v-1.84c1.2-.57 2-1.79 2-3.16s-.8-2.59-2-3.16V10h11v1h1v-1h4z">
                                    </path>
                                    <path d="M15 12h1v2h-1zm0 3h1v2h-1z"></path>
                                </svg>
                                <p>No tickets assigned yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_tickets as $ticket): ?>
                                <?php render_techn_ticket_row($ticket, $category_labels, false); ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            <!-- /page-dashboard -->


            <!-- ============================================================
                 TICKETS — body[data-page="tickets"]
                 ============================================================
                 Technician view: tickets ASSIGNED to this technician.
                 7 columns: ID | Subject | Description | Category | Status |
                            Severity Level | Action (share + message icons)
                 Toolbar: filter tabs only, no New Ticket button
                          (technicians don't submit tickets).
                 ============================================================ -->
            <div class="page-content" id="page-tickets">

                <div class="head">
                    <header>
                        <h1>Tickets</h1>
                        <div class="search-bar-wrapper">
                            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M18 10c0-4.41-3.59-8-8-8s-8 3.59-8 8 3.59 8 8 8c1.85 0 3.54-.63 4.9-1.69l5.1 5.1L21.41 20l-5.1-5.1A8 8 0 0 0 18 10M4 10c0-3.31 2.69-6 6-6s6 2.69 6 6-2.69 6-6 6-6-2.69-6-6">
                                </path>
                            </svg>
                            <input type="search" class="search-bar tickets-search-input" placeholder="Search tickets…" aria-label="Search tickets">
                        </div>
                    </header>
                </div>

                <div class="tickets-toolbar">
                    <div class="tickets-filter-tabs" id="techn-tickets-filter-tabs">
                        <button class="filter-tab active-tab" data-filter="all">All</button>
                        <button class="filter-tab" data-filter="ongoing">Ongoing</button>
                        <button class="filter-tab" data-filter="processing">Processing</button>
                        <button class="filter-tab" data-filter="resolved">Resolved</button>
                        <button class="filter-tab" data-filter="pending">Pending</button>
                    </div>
                </div>

                <?php if ($tickets_success): ?>
                    <div class="utilities-notice"><?= htmlspecialchars($tickets_success) ?></div>
                <?php endif; ?>
                <?php if ($tickets_error): ?>
                    <div class="utilities-notice-error"><?= htmlspecialchars($tickets_error) ?></div>
                <?php endif; ?>

                <div class="tickets-list">
                    <div class="tickets-list-header">
                        <span class="ttcol-id">ID</span>
                        <span class="ttcol-subject with-description">Subject</span>
                        <span class="ttcol-description">Description</span>
                        <span class="ttcol-category">Category</span>
                        <span class="ttcol-status">Status</span>
                        <span class="ttcol-severity">Severity Level</span>
                        <span class="ttcol-action">Action</span>
                    </div>
                    <div class="tickets-list-body" id="techn-tickets-body">
                        <?php if (empty($assigned_tickets)): ?>
                            <div class="tickets-empty-state">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M21 8h-2V3a1 1 0 0 0-1.37-.93l-15 6c-.09.04-.16.1-.24.16-.03.02-.06.03-.09.06-.04.04-.05.08-.08.12-.05.06-.1.12-.13.19-.01.02 0 .05-.02.08-.03.1-.06.2-.06.31v3.55c0 .48.33.89.8.98a1.499 1.499 0 0 1 0 2.94c-.47.09-.8.5-.8.98v3.55c0 .55.45 1 1 1h18c.55 0 1-.45 1-1v-3.55c0-.48-.33-.89-.8-.98a1.499 1.499 0 0 1 0-2.94c.47-.09.8-.5.8-.98V8.99c0-.55-.45-1-1-1Zm-4 0H8.19L17 4.48zm3 3.84c-1.2.57-2 1.79-2 3.16s.8 2.59 2 3.16V20h-4v-2h-1v2H4v-1.84c1.2-.57 2-1.79 2-3.16s-.8-2.59-2-3.16V10h11v1h1v-1h4z">
                                    </path>
                                    <path d="M15 12h1v2h-1zm0 3h1v2h-1z"></path>
                                </svg>
                                <p>No tickets assigned yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($assigned_tickets as $ticket): ?>
                                <?php render_techn_ticket_row($ticket, $category_labels, true); ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            <!-- /page-tickets -->


            <!-- ============================================================
                 MAILBOX — body[data-page="messages"]
                 ============================================================ -->
            <div class="page-content" id="page-messages">
                <div class="head">
                    <header>
                        <h1>Mailbox</h1>
                        <div class="search-bar-wrapper">
                            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M18 10c0-4.41-3.59-8-8-8s-8 3.59-8 8 3.59 8 8 8c1.85 0 3.54-.63 4.9-1.69l5.1 5.1L21.41 20l-5.1-5.1A8 8 0 0 0 18 10M4 10c0-3.31 2.69-6 6-6s6 2.69 6 6-2.69 6-6 6-6-2.69-6-6">
                                </path>
                            </svg>
                            <input type="search" class="search-bar" placeholder="Search..." aria-label="Search">
                        </div>
                    </header>
                </div>

                <?php include __DIR__ . '/partials/mailbox_section.php'; ?>

            </div>
            <!-- /page-messages -->


            <!-- ============================================================
                 MY PROFILE — body[data-page="profile"]
                 ============================================================
                 Reuses the .profile-container / .profile-cover-card /
                 .profile-card CSS already defined in main_interface.css
                 (built for user.php's My Profile page) — same shared file,
                 same classes, no new CSS needed for this section.
                 ============================================================ -->
            <div class="page-content" id="page-profile">
                <div class="head">
                    <header>
                        <h1>My Profile</h1>
                        <div class="search-bar-wrapper">
                            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M18 10c0-4.41-3.59-8-8-8s-8 3.59-8 8 3.59 8 8 8c1.85 0 3.54-.63 4.9-1.69l5.1 5.1L21.41 20l-5.1-5.1A8 8 0 0 0 18 10M4 10c0-3.31 2.69-6 6-6s6 2.69 6 6-2.69 6-6 6-6-2.69-6-6">
                                </path>
                            </svg>
                            <input type="search" class="search-bar" placeholder="Search..." aria-label="Search">
                        </div>
                    </header>
                </div>

                <div class="profile-container">

                    <!-- Cover card: avatar + name + role badge -->
                    <div class="profile-cover-card">
                        <div class="profile-avatar-wrap">
                            <div class="profile-avatar-lg"><?= htmlspecialchars($initials) ?></div>
                            <button class="profile-avatar-edit" title="Change photo">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M9 3 7.17 5H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2h-3.17L15 3zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="profile-display-name"><?= htmlspecialchars($full_name) ?></div>
                        <span class="profile-role-badge role-techn">Technician</span>
                    </div>

                    <!-- Personal Information + Contact Preferences -->
                    <div class="profile-row">
                        <div class="profile-card">
                            <h2>Personal Information</h2>

                            <div class="profile-info-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5m0-8c1.65 0 3 1.35 3 3s-1.35 3-3 3-3-1.35-3-3 1.35-3 3-3m0 9c-3.87 0-7 2.24-7 5v2h14v-2c0-2.76-3.13-5-7-5"></path>
                                </svg>
                                <div class="profile-info-text">
                                    <span class="profile-info-label">Full Name</span>
                                    <span class="profile-info-value"><?= htmlspecialchars($full_name) ?></span>
                                </div>
                                <button class="profile-info-edit"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zm17.71-10.04a1 1 0 0 0 0-1.41l-2.51-2.51a1 1 0 0 0-1.41 0l-1.96 1.96 3.75 3.75z"></path></svg></button>
                            </div>

                            <div class="profile-info-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2m0 2v.51l-8 6.22-8-6.22V6zM4 18V9.04l7.39 5.74c.18.14.4.21.61.21s.43-.07.61-.21L20 9.03v8.96H4Z"></path>
                                </svg>
                                <div class="profile-info-text">
                                    <span class="profile-info-label">Email Address</span>
                                    <span class="profile-info-value"><?= htmlspecialchars($email) ?></span>
                                </div>
                                <button class="profile-info-edit"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zm17.71-10.04a1 1 0 0 0 0-1.41l-2.51-2.51a1 1 0 0 0-1.41 0l-1.96 1.96 3.75 3.75z"></path></svg></button>
                            </div>

                            <div class="profile-info-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02z"></path>
                                </svg>
                                <div class="profile-info-text">
                                    <span class="profile-info-label">Phone Number</span>
                                    <span class="profile-info-value profile-info-empty">Not set</span>
                                </div>
                                <button class="profile-info-edit"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zm17.71-10.04a1 1 0 0 0 0-1.41l-2.51-2.51a1 1 0 0 0-1.41 0l-1.96 1.96 3.75 3.75z"></path></svg></button>
                            </div>
                        </div>

                        <div class="profile-card">
                            <h2>Contact Preferences</h2>

                            <div class="profile-pref-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2m6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1z"></path>
                                </svg>
                                <span class="profile-pref-label">Ticket Notifications</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="profile-pref-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2m0 2v.51l-8 6.22-8-6.22V6zM4 18V9.04l7.39 5.74c.18.14.4.21.61.21s.43-.07.61-.21L20 9.03v8.96H4Z"></path>
                                </svg>
                                <span class="profile-pref-label">Email Alerts</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="profile-pref-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2m6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56M12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96M4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56A7.99 7.99 0 0 1 5.08 16m2.95-8H5.08a7.99 7.99 0 0 1 4.33-3.56C8.81 5.55 8.35 6.75 8.03 8M12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96M14.34 14H9.66c-.09-.66-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.34-.16 2m.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95a8.03 8.03 0 0 1-4.33 3.56M16.36 14c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2z"></path>
                                </svg>
                                <span class="profile-pref-label">Language</span>
                                <select class="profile-pref-select">
                                    <option>English</option>
                                    <option>Filipino</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Connected Devices -->
                    <div class="profile-card profile-card-full">
                        <h2>Connected Devices</h2>
                        <div class="device-empty-state">
                            <p>No active sessions to show right now.</p>
                        </div>
                    </div>

                </div>
            </div>
            <!-- /page-profile -->


            <!-- ============================================================
                 SETTINGS — body[data-page="settings"]
                 ============================================================ -->
            <div class="page-content" id="page-settings">
                <div class="head">
                    <header>
                        <h1>Settings</h1>
                        <div class="search-bar-wrapper">
                            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M18 10c0-4.41-3.59-8-8-8s-8 3.59-8 8 3.59 8 8 8c1.85 0 3.54-.63 4.9-1.69l5.1 5.1L21.41 20l-5.1-5.1A8 8 0 0 0 18 10M4 10c0-3.31 2.69-6 6-6s6 2.69 6 6-2.69 6-6 6-6-2.69-6-6">
                                </path>
                            </svg>
                            <input type="search" class="search-bar" placeholder="Search..." aria-label="Search">
                        </div>
                    </header>
                </div>

                <div class="settings-container">

                    <!-- Appearance -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h2>Appearance</h2>
                            <p>Control how the dashboard looks on this device.</p>
                        </div>

                        <div class="settings-pref-row">
                            <span class="settings-pref-label">Theme</span>
                            <select class="settings-pref-select">
                                <option>Light</option>
                                <option>Dark</option>
                                <option>System Default</option>
                            </select>
                        </div>

                        <div class="settings-pref-row settings-pref-row-last">
                            <span class="settings-pref-label">Sidebar Behavior</span>
                            <select class="settings-pref-select">
                                <option>Collapse on hover</option>
                                <option>Always expanded</option>
                                <option>Always collapsed</option>
                            </select>
                        </div>
                    </div>

                    <!-- Security / Sign-In Methods -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h2>Security</h2>
                            <p>Manage how you sign in and keep your account secure.</p>
                        </div>

                        <div class="settings-subheading">Sign-In Methods</div>
                        <div class="settings-subtitle">Choose how you'd like to access your account.</div>

                        <a href="#" class="security-row">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 17c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2m6-9c1.1 0 2 .9 2 2v10c0 1.1-.9 2-2 2H6c-1.1 0-2-.9-2-2V10c0-1.1.9-2 2-2h1V6c0-2.76 2.24-5 5-5s5 2.24 5 5v2zm-6-5c-1.66 0-3 1.34-3 3v2h6V6c0-1.66-1.34-3-3-3"></path>
                            </svg>
                            <span class="security-row-label">Change Password</span>
                        </a>

                        <a href="#" class="security-row">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 1 3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11z"></path>
                            </svg>
                            <span class="security-row-label">Two-Factor Authentication</span>
                        </a>

                        <a href="#" class="security-row security-row-last">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M15.55 5.55 11 1 6.45 5.55C5.06 6.94 4.24 8.83 4.24 11c0 4.28 3.48 7.76 7.76 7.76s7.76-3.48 7.76-7.76c0-2.17-.82-4.06-2.21-5.45M12 16.76c-3.17 0-5.76-2.59-5.76-5.76 0-1.6.65-3.07 1.71-4.13L12 3.15v13.61Z"></path>
                            </svg>
                            <span class="security-row-label">Active Sessions</span>
                        </a>
                    </div>

                </div>
            </div>

        </section>
    </main>

    <script src="../js/behavior.js?v=2.1"></script>
    <script src="../js/tickets_filter.js"></script>
    <script src="../js/mailbox.js"></script>
</body>


</html>