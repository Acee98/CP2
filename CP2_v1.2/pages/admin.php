<?php
/**
 * admin.php — top-of-file PHP
 * ----------------------------------------------------------------------
 * Same session-guard pattern as user.php: only a logged-in admin may
 * load this page. Also pulls in the DB connection and, specifically
 * for the Utilities tab, the full user-accounts list plus whatever
 * view state the URL asks for (?action=add, ?edit_id=N), and any
 * success/error message left by user_admin_mngmnt.php after an
 * add/edit/activate/deactivate action.
 */
// Session setup centralized in session_config.php (dedicated session
// storage folder + consistent cookie config across every session-using
// page) — see that file for details.
require_once '../logic/session_config.php';
require_once '../logic/config.php';
require_once '../logic/mailbox_helpers.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../pages/login_signup.php");
    exit();
}

$first_name = $_SESSION['first_name'];
$last_name  = $_SESSION['last_name'];
$initials   = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// One-shot session messages from user_admin_mngmnt.php (add/edit/
// activate/deactivate results) — read once, then cleared, same as the
// login/signup error pattern in login_signup.php.
$utilities_success = $_SESSION['utilities_success'] ?? '';
$utilities_error   = $_SESSION['utilities_error'] ?? '';
unset($_SESSION['utilities_success'], $_SESSION['utilities_error']);

// View state for the Utilities tab, driven entirely by the query
// string so a redirect back here after a POST can land on the right
// view (list / add form / edit form) without needing JS state.
$utilities_action = $_GET['action'] ?? '';
$utilities_edit_id = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;

// The account currently being edited, if any — fetched fresh from the
// DB (not trusted from the URL) so the edit form always shows real
// current values.
$editing_user = null;
if ($utilities_edit_id > 0) {
    $editStmt = $conn->prepare("SELECT id, first_name, last_name, email, role FROM users WHERE id = ?");
    if ($editStmt === false) {
        $utilities_error = 'Could not load that account: ' . $conn->error;
    } else {
        $editStmt->bind_param("i", $utilities_edit_id);
        $editStmt->execute();
        $editStmt->bind_result($eu_id, $eu_first, $eu_last, $eu_email, $eu_role);
        if ($editStmt->fetch()) {
            $editing_user = compact('eu_id', 'eu_first', 'eu_last', 'eu_email', 'eu_role');
        }
        $editStmt->close();
    }
}

// Full user-accounts list for the Utilities table.
$all_users = [];
$usersResult = $conn->query(
    "SELECT id, first_name, last_name, email, role, status FROM users ORDER BY (status = 'active'), id"
);
if ($usersResult === false) {
    $utilities_error = 'Could not load user accounts: ' . $conn->error;
} else {
    while ($row = $usersResult->fetch_assoc()) {
        $all_users[] = $row;
    }
}

$role_labels = ['user' => 'User', 'techn' => 'Technician', 'admin' => 'Administrator'];

// One-shot session messages from ticket_admin_mngmnt.php (assign / status).
$tickets_success = $_SESSION['tickets_success'] ?? '';
$tickets_error   = $_SESSION['tickets_error'] ?? '';
unset($_SESSION['tickets_success'], $_SESSION['tickets_error']);

// Active technicians for the assign dropdown on the Tickets tab.
$technicians = [];
$techResult = $conn->query(
    "SELECT id, first_name, last_name FROM users
     WHERE role = 'techn' AND status = 'active'
     ORDER BY first_name, last_name"
);
if ($techResult === false) {
    $tickets_error = $tickets_error ?: ('Could not load technicians: ' . $conn->error);
} else {
    while ($row = $techResult->fetch_assoc()) {
        $technicians[] = $row;
    }
}

// Dashboard status-card counts.
$status_counts = [
    'pending'    => 0,
    'ongoing'    => 0,
    'processing' => 0,
    'resolved'   => 0,
];
$countResult = $conn->query(
    "SELECT status, COUNT(*) AS cnt FROM tickets GROUP BY status"
);
if ($countResult === false) {
    $tickets_error = $tickets_error ?: ('Could not load ticket counts: ' . $conn->error);
} else {
    while ($row = $countResult->fetch_assoc()) {
        $key = $row['status'];
        if (isset($status_counts[$key])) {
            $status_counts[$key] = (int) $row['cnt'];
        }
    }
}

// Full ticket list for the Tickets tab (all users, with submitter + assignee names).
$all_tickets = [];
$ticketsResult = $conn->query(
    "SELECT
        t.id,
        t.subject,
        t.category,
        t.severity,
        t.ai_severity,
        t.status,
        t.assigned_to,
        u.first_name AS owner_first,
        u.last_name  AS owner_last,
        tech.first_name AS tech_first,
        tech.last_name  AS tech_last
     FROM tickets t
     INNER JOIN users u ON t.user_id = u.id
     LEFT JOIN users tech ON t.assigned_to = tech.id
     ORDER BY t.created_at DESC"
);
if ($ticketsResult === false) {
    $tickets_error = $tickets_error ?: ('Could not load tickets: ' . $conn->error);
} else {
    while ($row = $ticketsResult->fetch_assoc()) {
        $all_tickets[] = $row;
    }
}

$category_labels = [
    'hardware' => 'Hardware',
    'software' => 'Software',
    'network'  => 'Network',
    'account'  => 'Account',
    'other'    => 'Other',
];

$admin_user_id = mailbox_resolve_user_id($conn, $_SESSION['email'], 'admin');
$mailbox_success = $_SESSION['mailbox_success'] ?? '';
$mailbox_error   = $_SESSION['mailbox_error'] ?? '';
unset($_SESSION['mailbox_success'], $_SESSION['mailbox_error']);
$mailbox_role = 'admin';
$mailbox_current_user_id = $admin_user_id;
$mailbox_conversations = mailbox_fetch_conversations($conn, 'admin', $admin_user_id);
$mailbox_active_ticket_id = isset($_GET['ticket_id']) ? (int) $_GET['ticket_id'] : 0;
$mailbox_messages = [];
$mailbox_active_ticket = null;
if ($mailbox_active_ticket_id > 0 && mailbox_can_access_ticket($conn, $mailbox_active_ticket_id, 'admin', $admin_user_id)) {
    $mailbox_active_ticket = mailbox_fetch_ticket_header($conn, $mailbox_active_ticket_id);
    $mailbox_messages = mailbox_fetch_messages($conn, $mailbox_active_ticket_id);
} else {
    $mailbox_active_ticket_id = 0;
}

$allowed_nav_tabs = ['dashboard', 'tickets', 'utilities', 'performance', 'messages', 'profile', 'settings'];
$active_nav_tab = $_GET['tab'] ?? 'dashboard';
if (!in_array($active_nav_tab, $allowed_nav_tabs, true)) {
    $active_nav_tab = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/main_interface.css?v=2.1">
    <title>ZPGC Services | Administrator</title>
</head>

<body data-page="<?= htmlspecialchars($active_nav_tab) ?>">
    <main class="main-wrap">
        <header class="main-head">
            <div class="main-nav">
                <nav class="navbar">
                    <div class="navbar-nav">

                        <!-- Logo + collapse toggler -->
                        <div class="logo">
                            <img src="../images/ZPGC.com2.png" alt="ZPGC Services logo">
                            <button class="showcase-toggler" aria-label="Toggle sidebar">
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

                            <!-- Utilities -->
                            <li class="nav-list-item<?= $active_nav_tab === 'utilities' ? ' selected' : '' ?>" data-nav="utilities">
                                <a href="#" class="nav-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M20.71 6.04a.99.99 0 0 0-.9.27l-3.18 3.18-2.12-2.12 3.18-3.18a.98.98 0 0 0 .27-.9c-.07-.33-.29-.6-.6-.73A7.47 7.47 0 0 0 9.2 4.19a7.49 7.49 0 0 0-1.86 7.52L2.3 16.75c-.19.19-.29.44-.29.71s.11.52.29.71l3.54 3.54c.19.19.44.29.71.29s.52-.11.71-.29l5.04-5.04c2.64.82 5.53.12 7.52-1.86a7.47 7.47 0 0 0 1.63-8.16c-.13-.31-.4-.53-.73-.6Zm-2.32 7.34a5.51 5.51 0 0 1-5.98 1.2c-.37-.15-.8-.07-1.09.22l-4.78 4.78-2.12-2.12 4.78-4.78c.29-.29.37-.71.22-1.09a5.47 5.47 0 0 1 1.2-5.98 5.5 5.5 0 0 1 4.41-1.59l-2.65 2.65a.996.996 0 0 0 0 1.41l3.54 3.54c.19.19.44.29.71.29s.52-.11.71-.29l2.65-2.65c.16 1.61-.4 3.23-1.59 4.42Z">
                                        </path>
                                    </svg>
                                    <span class="link-text">Utilities</span>
                                </a>
                            </li>

                            <!-- Performance Tech -->
                            <li class="nav-list-item<?= $active_nav_tab === 'performance' ? ' selected' : '' ?>" data-nav="performance">
                                <a href="#" class="nav-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3m-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3m0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5m8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5">
                                        </path>
                                    </svg>
                                    <span class="link-text">Performance</span>
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
            <div class="page-content" id="page-dashboard">

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
                        <div class="profile-circle"><?= htmlspecialchars($initials) ?></div>
                    </header>
                </div>

                <!-- Status Cards — Ongoing / Processing / Resolved counts from DB -->
                <div class="status-cards">

                    <!-- Ongoing -->
                    <div class="status-card">
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
                    <div class="status-card">
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
                    <div class="status-card">
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

                </div>

                <!-- ============================================================
                     DASHBOARD CHARTS — Tickets Report / Tickets-Categories /
                     Customer Satisfaction / Severity Level
                     ------------------------------------------------------------
                     Rendered client-side with Chart.js (loaded once, below,
                     right before </body>). All 4 datasets are static
                     placeholder numbers for now, same "shell first, wire up
                     later" phase as every other list/table on this page —
                     swap the arrays in the <script> block once real ticket
                     stats are available.
                     ============================================================ -->
                <div class="dashboard-charts-grid">

                    <div class="chart-card">
                        <div class="chart-card-header">
                            <h2>Tickets Report</h2>
                            <button class="perf-filter-btn" type="button">This Week</button>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="ticketsReportChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-card-header">
                            <h2>Tickets - Categories</h2>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="ticketsCategoriesChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-card-header">
                            <h2>Customer Satisfaction</h2>
                        </div>
                        <div class="chart-card-body chart-card-body-split">
                            <ul class="chart-legend-list">
                                <li><span class="legend-dot" style="background:#7ED9A8"></span>5 &nbsp; Very Satisfied</li>
                                <li><span class="legend-dot" style="background:#2E8B8B"></span>4 &nbsp; Satisfied</li>
                                <li><span class="legend-dot" style="background:#5BC8E8"></span>3 &nbsp; Not Sure</li>
                                <li><span class="legend-dot" style="background:#F5A623"></span>2 &nbsp; Not Satisfied</li>
                                <li><span class="legend-dot" style="background:#D9435E"></span>1 &nbsp; Hate It</li>
                            </ul>
                            <div class="chart-canvas-wrap">
                                <canvas id="satisfactionChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-card-header">
                            <h2>Severity Level</h2>
                        </div>
                        <div class="chart-card-body">
                            <canvas id="severityChart"></canvas>
                        </div>
                    </div>

                </div>

            </div>
            <!-- /page-dashboard -->


            <!-- ============================================================
                 TICKETS — body[data-page="tickets"]
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
                        <div class="profile-circle"><?= htmlspecialchars($initials) ?></div>
                    </header>
                </div>

                <div class="tickets-toolbar">
                    <div class="tickets-filter-tabs" id="admin-tickets-filter-tabs">
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
                        <span class="tcol-id">ID</span>
                        <span class="tcol-subject">Subject</span>
                        <span class="tcol-category">Category</span>
                        <span class="tcol-priority">Priority</span>
                        <span class="tcol-status">Status</span>
                        <span class="tcol-assigned">Assigned To</span>
                        <span class="tcol-action">Actions</span>
                    </div>
                    <div class="tickets-list-body" id="admin-tickets-body">
                        <?php if (empty($all_tickets)): ?>
                            <div class="tickets-empty-state">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M21 8h-2V3a1 1 0 0 0-1.37-.93l-15 6c-.09.04-.16.1-.24.16-.03.02-.06.03-.09.06-.04.04-.05.08-.08.12-.05.06-.1.12-.13.19-.01.02 0 .05-.02.08-.03.1-.06.2-.06.31v3.55c0 .48.33.89.8.98a1.499 1.499 0 0 1 0 2.94c-.47.09-.8.5-.8.98v3.55c0 .55.45 1 1 1h18c.55 0 1-.45 1-1v-3.55c0-.48-.33-.89-.8-.98a1.499 1.499 0 0 1 0-2.94c.47-.09.8-.5.8-.98V8.99c0-.55-.45-1-1-1Zm-4 0H8.19L17 4.48zm3 3.84c-1.2.57-2 1.79-2 3.16s.8 2.59 2 3.16V20h-4v-2h-1v2H4v-1.84c1.2-.57 2-1.79 2-3.16s-.8-2.59-2-3.16V10h11v1h1v-1h4z">
                                    </path>
                                    <path d="M15 12h1v2h-1zm0 3h1v2h-1z"></path>
                                </svg>
                                <p>No tickets found.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($all_tickets as $ticket): ?>
                                <?php
                                    $tid = (int) $ticket['id'];
                                    $statusKey = preg_replace('/[^a-z]/', '', strtolower($ticket['status']));
                                    $aiPriority = trim((string) ($ticket['ai_severity'] ?? ''));
                                    $priorityKey = $aiPriority !== ''
                                        ? preg_replace('/[^a-z]/', '', strtolower($aiPriority))
                                        : '';
                                    $catLabel = $category_labels[$ticket['category']] ?? ucfirst($ticket['category']);
                                    $ownerName = trim(($ticket['owner_first'] ?? '') . ' ' . ($ticket['owner_last'] ?? ''));
                                    $techName  = trim(($ticket['tech_first'] ?? '') . ' ' . ($ticket['tech_last'] ?? ''));
                                    $searchBlob = strtolower(implode(' ', [
                                        '#' . $tid,
                                        $ticket['subject'],
                                        $catLabel,
                                        $ticket['category'],
                                        $ticket['status'],
                                        $aiPriority,
                                        $ownerName,
                                        $techName,
                                    ]));
                                ?>
                                <div class="ticket-row" data-status="<?= htmlspecialchars($ticket['status']) ?>"
                                    data-search="<?= htmlspecialchars($searchBlob) ?>">
                                    <span class="tcol-id">#<?= str_pad((string) $tid, 4, '0', STR_PAD_LEFT) ?></span>
                                    <span class="tcol-subject"><?= htmlspecialchars($ticket['subject']) ?></span>
                                    <span class="tcol-category"><?= htmlspecialchars($catLabel) ?></span>
                                    <span class="tcol-priority">
                                        <?php if ($aiPriority !== ''): ?>
                                            <span class="priority-badge <?= htmlspecialchars($priorityKey) ?>">
                                                <?= htmlspecialchars(ucfirst($aiPriority)) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="priority-badge undefined">Undefined</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="tcol-status">
                                        <span class="status-badge <?= htmlspecialchars($statusKey) ?>">
                                            <?= htmlspecialchars(ucfirst($ticket['status'])) ?>
                                        </span>
                                    </span>
                                    <span class="tcol-assigned">
                                        <select name="assigned_to" form="admin-ticket-form-<?= $tid ?>"
                                            class="admin-ticket-select admin-ticket-select-assign" aria-label="Assign technician">
                                            <option value="" <?= empty($ticket['assigned_to']) ? 'selected' : '' ?>>Unassigned</option>
                                            <?php foreach ($technicians as $tech): ?>
                                                <option value="<?= (int) $tech['id'] ?>"
                                                    <?= ((int) ($ticket['assigned_to'] ?? 0) === (int) $tech['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($tech['first_name'] . ' ' . $tech['last_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </span>
                                    <span class="tcol-action">
                                        <form id="admin-ticket-form-<?= $tid ?>" class="admin-ticket-form"
                                            action="../logic/ticket_admin_mngmnt.php" method="post">
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
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            <!-- /page-tickets -->


            <!-- ============================================================
                 UTILITIES — body[data-page="utilities"]
                 ------------------------------------------------------------
                 Maps to FR4 ("administrators shall manage user accounts —
                 add, edit, deactivate"). View state (list / add form / edit
                 form) is driven by ?action= and ?edit_id= in the URL rather
                 than JS, so a redirect back here after a POST to
                 user_admin_mngmnt.php can land on the right view with no
                 client-side state to keep in sync.
                 ============================================================ -->
            <div class="page-content" id="page-utilities">
                <div class="head">
                    <header>
                        <h1>Utilities</h1>
                        <div class="search-bar-wrapper">
                            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M18 10c0-4.41-3.59-8-8-8s-8 3.59-8 8 3.59 8 8 8c1.85 0 3.54-.63 4.9-1.69l5.1 5.1L21.41 20l-5.1-5.1A8 8 0 0 0 18 10M4 10c0-3.31 2.69-6 6-6s6 2.69 6 6-2.69 6-6 6-6-2.69-6-6">
                                </path>
                            </svg>
                            <input type="search" class="search-bar" placeholder="Search..." aria-label="Search">
                        </div>
                        <div class="profile-circle"><?= htmlspecialchars($initials) ?></div>
                    </header>
                </div>

                <?php if ($utilities_success): ?>
                    <div class="utilities-notice"><?= htmlspecialchars($utilities_success) ?></div>
                <?php endif; ?>
                <?php if ($utilities_error): ?>
                    <div class="utilities-notice-error"><?= htmlspecialchars($utilities_error) ?></div>
                <?php endif; ?>

                <?php if ($utilities_action === 'add'): ?>
                    <!-- ADD USER FORM -->
                    <div class="user-form-card">
                        <h2>Add User</h2>
                        <p class="form-subtitle">Create a new account directly — it's active immediately, no approval step needed.</p>
                        <form class="user-form" action="../logic/user_admin_mngmnt.php" method="post">
                            <div class="user-form-row">
                                <div class="user-form-field">
                                    <label for="add_first_name">First Name</label>
                                    <input type="text" id="add_first_name" name="first_name" required>
                                </div>
                                <div class="user-form-field">
                                    <label for="add_last_name">Last Name</label>
                                    <input type="text" id="add_last_name" name="last_name" required>
                                </div>
                            </div>
                            <div class="user-form-field">
                                <label for="add_email">Email Address</label>
                                <input type="email" id="add_email" name="email" required>
                            </div>
                            <div class="user-form-row">
                                <div class="user-form-field">
                                    <label for="add_role">Role</label>
                                    <select id="add_role" name="role" required>
                                        <option value="" disabled selected>Select a role</option>
                                        <option value="user">User</option>
                                        <option value="techn">Technician</option>
                                        <option value="admin">Administrator</option>
                                    </select>
                                </div>
                                <div class="user-form-field">
                                    <label for="add_password">Temporary Password</label>
                                    <input type="password" id="add_password" name="password" minlength="8" required>
                                    <small>At least 8 characters. Share this with the user directly.</small>
                                </div>
                            </div>
                            <div class="user-form-actions">
                                <a href="?tab=utilities" class="btn-cancel-user">Cancel</a>
                                <button type="submit" name="add_user" class="btn-new-ticket">Create Account</button>
                            </div>
                        </form>
                    </div>

                <?php elseif ($editing_user): ?>
                    <!-- EDIT USER FORM -->
                    <div class="user-form-card">
                        <h2>Edit User</h2>
                        <p class="form-subtitle">Update this account's name, email, or role.</p>
                        <form class="user-form" action="../logic/user_admin_mngmnt.php" method="post">
                            <input type="hidden" name="id" value="<?= (int) $editing_user['eu_id'] ?>">
                            <div class="user-form-row">
                                <div class="user-form-field">
                                    <label for="edit_first_name">First Name</label>
                                    <input type="text" id="edit_first_name" name="first_name"
                                        value="<?= htmlspecialchars($editing_user['eu_first']) ?>" required>
                                </div>
                                <div class="user-form-field">
                                    <label for="edit_last_name">Last Name</label>
                                    <input type="text" id="edit_last_name" name="last_name"
                                        value="<?= htmlspecialchars($editing_user['eu_last']) ?>" required>
                                </div>
                            </div>
                            <div class="user-form-field">
                                <label for="edit_email">Email Address</label>
                                <input type="email" id="edit_email" name="email"
                                    value="<?= htmlspecialchars($editing_user['eu_email']) ?>" required>
                            </div>
                            <div class="user-form-field">
                                <label for="edit_role">Role</label>
                                <select id="edit_role" name="role" required>
                                    <?php foreach (['user' => 'User', 'techn' => 'Technician', 'admin' => 'Administrator'] as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= $editing_user['eu_role'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="user-form-actions">
                                <a href="?tab=utilities" class="btn-cancel-user">Cancel</a>
                                <button type="submit" name="edit_user" class="btn-new-ticket">Save Changes</button>
                            </div>
                        </form>
                    </div>

                <?php else: ?>
                    <div class="tickets-toolbar">
                        <div class="tickets-filter-tabs" id="utilities-filter-tabs">
                            <button class="filter-tab active-tab" data-filter="all">All</button>
                            <button class="filter-tab" data-filter="user">User</button>
                            <button class="filter-tab" data-filter="techn">Technician</button>
                            <button class="filter-tab" data-filter="admin">Administrator</button>
                            <button class="filter-tab" data-filter="pending">Pending Approval</button>
                        </div>
                        <a href="?tab=utilities&action=add" class="btn-new-ticket">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z"></path>
                            </svg>
                            Add User
                        </a>
                    </div>

                    <div class="tickets-list">
                        <div class="tickets-list-header">
                            <span class="ucol-id">ID</span>
                            <span class="ucol-name">Name</span>
                            <span class="ucol-email">Email</span>
                            <span class="ucol-role">Role</span>
                            <span class="ucol-status">Status</span>
                            <span class="ucol-action">Actions</span>
                        </div>
                        <div class="tickets-list-body" id="utilities-users-body">
                            <?php if (empty($all_users)): ?>
                                <div class="tickets-empty-state">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M12 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3m0-4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1M12 13c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4m-6 5v-.99c.2-.72 3.3-2.01 6-2.01s5.8 1.29 6 2v1z">
                                        </path>
                                        <path
                                            d="M17.24 12.02c1.7.6 3.76 1.83 3.76 2.98v3h-3v-3.03c0-1.24-.51-2.24-1.31-3.01.19.02.37.04.55.06">
                                        </path>
                                        <path
                                            d="M16.5 8.5c0-1.32-.4-2.53-1.09-3.49.19-.01.39-.01.59-.01 1.93 0 3.5 1.57 3.5 3.5S17.93 12 16 12c-.2 0-.4 0-.59-.01.69-.96 1.09-2.17 1.09-3.49">
                                        </path>
                                    </svg>
                                    <p>No user accounts found.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($all_users as $u): ?>
                                    <?php
                                        $isActive = $u['status'] === 'active';
                                        $roleLabel = $role_labels[$u['role']] ?? ucfirst($u['role']);
                                    ?>
                                    <div class="ticket-row" data-role="<?= htmlspecialchars($u['role']) ?>" data-status="<?= htmlspecialchars($u['status']) ?>">
                                        <span class="ucol-id">#<?= (int) $u['id'] ?></span>
                                        <span class="ucol-name"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></span>
                                        <span class="ucol-email"><?= htmlspecialchars($u['email']) ?></span>
                                        <span class="ucol-role">
                                            <span class="profile-role-badge role-<?= htmlspecialchars($u['role']) ?>"><?= htmlspecialchars($roleLabel) ?></span>
                                        </span>
                                        <span class="ucol-status">
                                            <span class="status-badge <?= $isActive ? 'active-account' : 'inactive-account' ?>">
                                                <?= $isActive ? 'Active' : 'Pending' ?>
                                            </span>
                                        </span>
                                        <span class="ucol-action">
                                            <a href="?tab=utilities&edit_id=<?= (int) $u['id'] ?>" class="btn-assign">Edit</a>
                                            <form action="../logic/user_admin_mngmnt.php" method="post">
                                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $isActive ? 'inactive' : 'active' ?>">
                                                <button type="submit" name="set_status" class="btn-update-status">
                                                    <?= $isActive ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                            <?php
                                                $deleteConfirmName = htmlspecialchars(
                                                    json_encode($u['first_name'] . ' ' . $u['last_name']),
                                                    ENT_QUOTES
                                                );
                                            ?>
                                            <form action="../logic/user_admin_mngmnt.php" method="post"
                                                onsubmit="return confirm('Permanently delete ' + <?= $deleteConfirmName ?> + '\'s account? This can\'t be undone.');">
                                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                                <button type="submit" name="delete_user" class="btn-delete">Delete</button>
                                            </form>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
            <!-- /page-utilities -->


            <!-- ============================================================
                 PERFORMANCE TECH — body[data-page="performance"]
                 ------------------------------------------------------------
                 "Technician Team" — per-technician resolved-ticket counts
                 broken down by severity (table 1), plus a detailed ticket
                 log (table 2). Currently static placeholder rows —
                 (queried above) instead of the old hardcoded loops. Each
                 table falls back to the same .tickets-empty-state /
                 dedicated empty-row pattern used elsewhere on this page
                 when there's genuinely nothing to show.
                 ============================================================ -->
            <div class="page-content" id="page-performance">
                <div class="head">
                    <header>
                       
                        <div class="search-bar-wrapper">
                            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M18 10c0-4.41-3.59-8-8-8s-8 3.59-8 8 3.59 8 8 8c1.85 0 3.54-.63 4.9-1.69l5.1 5.1L21.41 20l-5.1-5.1A8 8 0 0 0 18 10M4 10c0-3.31 2.69-6 6-6s6 2.69 6 6-2.69 6-6 6-6-2.69-6-6">
                                </path>
                            </svg>
                            <input type="search" class="search-bar" placeholder="Search..." aria-label="Search">
                        </div>
                        <div class="profile-circle"><?= htmlspecialchars($initials) ?></div>
                    </header>
                </div>

                <!-- Table 1: Technician Record List -->
                <div class="perf-section-header">
                    <h2>Technician Record List</h2>
                    <button class="perf-filter-btn" type="button" data-dropdown="perf-date-range">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 11h2v2H7zm0 4h2v2H7zm4-4h2v2h-2zm0 4h2v2h-2zm4-4h2v2h-2zm0 4h2v2h-2zM5 22h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2M5 8h14v12H5z"></path>
                        </svg>
                        Date-Range
                        <svg class="perf-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 10l5 5 5-5z"></path>
                        </svg>
                    </button>
                </div>

                <div class="tickets-list">
                    <div class="tickets-list-header">
                        <span class="pcol-teamid">Team ID</span>
                        <span class="pcol-resolved">Ticket Resolved</span>
                        <span class="pcol-critical">Critical</span>
                        <span class="pcol-moderate">Moderate</span>
                        <span class="pcol-low">Low</span>
                    </div>
                    <div class="tickets-list-body">
                        <?php
                            // Reverted to placeholder rows — the previous
                            // version queried a "tickets" table that
                            // doesn't exist in this DB. Re-wire this once
                            // Allen confirms the real table/column names.
                            // Still uses the real .pcol-* / .perf-count
                            // classes so the layout/styling is correct
                            // once real rows are dropped back in.
                            for ($i = 0; $i < 5; $i++):
                        ?>
                            <div class="ticket-row">
                                <span class="pcol-teamid">#0000</span>
                                <span class="pcol-resolved">0</span>
                                <span class="pcol-critical"><span class="perf-count critical">0</span></span>
                                <span class="pcol-moderate"><span class="perf-count moderate">0</span></span>
                                <span class="pcol-low"><span class="perf-count low">0</span></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="perf-table-spacer"></div>

                <!-- Table 2: Ticket Detail Log -->
                <div class="perf-filters-row">
                    <div class="search-bar-wrapper">
                        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M18 10c0-4.41-3.59-8-8-8s-8 3.59-8 8 3.59 8 8 8c1.85 0 3.54-.63 4.9-1.69l5.1 5.1L21.41 20l-5.1-5.1A8 8 0 0 0 18 10M4 10c0-3.31 2.69-6 6-6s6 2.69 6 6-2.69 6-6 6-6-2.69-6-6">
                            </path>
                        </svg>
                        <input type="search" class="search-bar" id="perf-log-search" placeholder="Search..." aria-label="Search ticket log">
                    </div>
                    <button class="perf-filter-btn" type="button" data-dropdown="perf-severity">
                        Severity Level
                        <svg class="perf-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 10l5 5 5-5z"></path>
                        </svg>
                    </button>
                    <button class="perf-filter-btn" type="button" data-dropdown="perf-category">
                        Categories
                        <svg class="perf-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 10l5 5 5-5z"></path>
                        </svg>
                    </button>
                </div>

                <div class="tickets-list">
                    <div class="tickets-list-header">
                        <span class="dcol-team">Team ID</span>
                        <span class="dcol-ticket">Ticket ID</span>
                        <span class="dcol-subject">Subject</span>
                        <span class="dcol-description">Description</span>
                        <span class="dcol-category">Category</span>
                        <span class="dcol-reg">Registration Date &amp; Time<br>(MM/DD/YYYY)</span>
                        <span class="dcol-response">Response Time</span>
                        <span class="dcol-resolution">Resolution Time</span>
                        <span class="dcol-severity">Severity Level</span>
                    </div>
                    <div class="tickets-list-body">
                        <?php
                            // Reverted to placeholder rows — same reason
                            // as Table 1 above. Severity cycles through
                            // the palette so all three badge colors still
                            // preview correctly.
                            $placeholder_severities = ['moderate', 'moderate', 'critical', 'critical', 'moderate', 'low'];
                            foreach ($placeholder_severities as $sev):
                        ?>
                            <div class="ticket-row">
                                <span class="dcol-team">0000</span>
                                <span class="dcol-ticket">0000</span>
                                <span class="dcol-subject">The title of the issue</span>
                                <span class="dcol-description">A brief summary of the request</span>
                                <span class="dcol-category">Hardware</span>
                                <span class="dcol-reg">08/15/2027 (11:11)</span>
                                <span class="dcol-response">10 Minutes</span>
                                <span class="dcol-resolution">1 Hour</span>
                                <span class="dcol-severity"><span class="severity-badge <?= $sev ?>"><?= ucfirst($sev) ?></span></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <a href="#" class="perf-view-all">View all</a>

            </div>
            <!-- /page-performance -->


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
                        <div class="profile-circle"><?= htmlspecialchars($initials) ?></div>
                    </header>
                </div>

                <?php include __DIR__ . '/partials/mailbox_section.php'; ?>

            </div>
            <!-- /page-messages -->


            <!-- ============================================================
                 MY PROFILE — body[data-page="profile"]
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
                        <div class="profile-circle"><?= htmlspecialchars($initials) ?></div>
                    </header>
                </div>

                <div class="profile-container">

                    <!-- Cover card: avatar + name + role badge -->
                    <div class="profile-cover-card">
                        <div class="profile-avatar-wrap">
                            <div class="profile-avatar-lg"><?= htmlspecialchars($initials) ?></div>
                            <button class="profile-avatar-edit" type="button" title="Change photo">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M9 3 7.17 5H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2h-3.17L15 3zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="profile-display-name"><?= htmlspecialchars($first_name . ' ' . $last_name) ?></div>
                        <span class="profile-role-badge role-admin">Administrator</span>
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
                                    <span class="profile-info-value"><?= htmlspecialchars($first_name . ' ' . $last_name) ?></span>
                                </div>
                                <button class="profile-info-edit" type="button" aria-label="Edit full name"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zm17.71-10.04a1 1 0 0 0 0-1.41l-2.51-2.51a1 1 0 0 0-1.41 0l-1.96 1.96 3.75 3.75z"></path></svg></button>
                            </div>

                            <div class="profile-info-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2m0 2v.51l-8 6.22-8-6.22V6zM4 18V9.04l7.39 5.74c.18.14.4.21.61.21s.43-.07.61-.21L20 9.03v8.96H4Z"></path>
                                </svg>
                                <div class="profile-info-text">
                                    <span class="profile-info-label">Email Address</span>
                                    <span class="profile-info-value"><?= htmlspecialchars($_SESSION['email']) ?></span>
                                </div>
                                <button class="profile-info-edit" type="button" aria-label="Edit email address"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zm17.71-10.04a1 1 0 0 0 0-1.41l-2.51-2.51a1 1 0 0 0-1.41 0l-1.96 1.96 3.75 3.75z"></path></svg></button>
                            </div>

                            <div class="profile-info-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02z"></path>
                                </svg>
                                <div class="profile-info-text">
                                    <span class="profile-info-label">Phone Number</span>
                                    <span class="profile-info-value profile-info-empty">Not set</span>
                                </div>
                                <button class="profile-info-edit" type="button" aria-label="Edit phone number"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zm17.71-10.04a1 1 0 0 0 0-1.41l-2.51-2.51a1 1 0 0 0-1.41 0l-1.96 1.96 3.75 3.75z"></path></svg></button>
                            </div>
                        </div>

                        <div class="profile-card">
                            <h2>Contact Preferences</h2>

                            <div class="profile-pref-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2m6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1z"></path>
                                </svg>
                                <span class="profile-pref-label">System Notifications</span>
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
                 ------------------------------------------------------------
                 Was completely empty before — the other "wala" section
                 Allen flagged. Mirrors user.php's #page-settings 1:1:
                 same .settings-container / .settings-card /
                 .settings-pref-row / .security-row classes, already
                 defined in main_interface.css for Appearance + Sign-In
                 Methods — no new CSS needed, no visual divergence from
                 the format that page already established. No backend
                 wiring yet, same phase as user.php's version.
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
                        <div class="profile-circle"><?= htmlspecialchars($initials) ?></div>
                    </header>
                </div>

                <div class="settings-container">

                    <!-- Appearance: how the app looks -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h2>Appearance</h2>
                            <p>Stay in control of your dashboard's look.</p>
                        </div>

                        <div class="settings-pref-row">
                            <span class="settings-pref-label">Browser Preferences</span>
                            <select class="settings-pref-select">
                                <option>Light</option>
                                <option>Dark</option>
                            </select>
                        </div>
                        <div class="settings-pref-row settings-pref-row-last">
                            <span class="settings-pref-label">Font Style</span>
                            <select class="settings-pref-select">
                                <option>Inter</option>
                                <option>System Default</option>
                            </select>
                        </div>
                    </div>

                    <!-- Security: how you sign in -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h2>Security</h2>
                        </div>

                        <h3 class="settings-subheading">Sign-In Methods</h3>
                        <p class="settings-subtitle">Ensure you can always access your account by keeping this
                            information up-to-date.</p>

                        <a href="#" class="security-row">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="m20.42 6.11-7.97-4c-.28-.14-.62-.14-.9 0l-7.97 4c-.31.15-.51.45-.55.79-.01.11-.96 10.76 8.55 15.01a.98.98 0 0 0 .82 0C21.91 17.66 20.97 7 20.95 6.9a.98.98 0 0 0-.55-.79ZM12 19.9C5.26 16.63 4.94 9.64 5 7.64l7-3.51 7 3.51c.04 1.99-.33 9.02-7 12.26">
                                </path>
                                <path d="m11 12.59-1.29-1.3-1.42 1.42 2.71 2.7 4.71-4.7-1.42-1.42z"></path>
                            </svg>
                            <span class="security-row-label">2-Step Verification</span>
                        </a>
                        <a href="#" class="security-row">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2M9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9zm3 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2">
                                </path>
                            </svg>
                            <span class="security-row-label">Password</span>
                        </a>
                        <a href="#" class="security-row security-row-last">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M12 2C6.49 2 2 6.49 2 12s4.49 10 10 10c1.47 0 2.96-.37 4.44-1.1l-.89-1.79c-1.2.59-2.4.9-3.56.9-4.41 0-8-3.59-8-8S7.59 4 12 4s8 3.59 8 8v1c0 .69-.31 2-1.5 2-1.4 0-1.49-1.82-1.5-2V8h-2v.03C14.16 7.4 13.13 7 12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5c1.45 0 2.75-.63 3.66-1.62.52.89 1.41 1.62 2.84 1.62 2.27 0 3.5-2.06 3.5-4v-1c0-5.51-4.49-10-10-10m0 13c-1.65 0-3-1.35-3-3s1.35-3 3-3 3 1.35 3 3-1.35 3-3 3">
                                </path>
                            </svg>
                            <span class="security-row-label">Recovery E-Mail</span>
                        </a>
                    </div>

                </div>
            </div>

        </section>
    </main>

    <script src="../js/behavior.js?v=2.1"></script>
    <script src="../js/utilities_filter.js"></script>
    <script src="../js/utilities_live.js"></script>
    <script src="../js/tickets_filter.js"></script>
    <script src="../js/mailbox.js"></script>
    <script src="../js/chart_umd.js"></script>
    <script>
        /*
         * Dashboard charts (Chart.js) — all four datasets below are
         * static placeholder numbers, same phase as every other list/
         * table on this page ("shell first, real data later"). Swap
         * these arrays for real values once the ticket-stats endpoint
         * exists; the chart configs themselves don't need to change.
         */
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') return;

            const maroon = '#610107';
            const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

            // Tickets Report — line chart, two series (matches the
            // red/cyan two-line look in the mockup).
            const reportCtx = document.getElementById('ticketsReportChart');
            if (reportCtx) {
                new Chart(reportCtx, {
                    type: 'line',
                    data: {
                        labels: days,
                        datasets: [
                            {
                                label: 'Submitted',
                                data: [10, 22, 28, 35, 40, 48, 50],
                                borderColor: maroon,
                                backgroundColor: maroon,
                                tension: 0.35,
                                pointRadius: 3,
                            },
                            {
                                label: 'Resolved',
                                data: [15, 18, 30, 45, 45, 45, 50],
                                borderColor: '#5BC8E8',
                                backgroundColor: '#5BC8E8',
                                tension: 0.35,
                                pointRadius: 3,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                        scales: { y: { beginAtZero: true } },
                    },
                });
            }

            // Tickets - Categories — bar chart.
            const catCtx = document.getElementById('ticketsCategoriesChart');
            if (catCtx) {
                new Chart(catCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Hardware', 'Software', 'Network', 'Account', 'Other'],
                        datasets: [{
                            label: 'Tickets',
                            data: [70, 85, 65, 95, 45],
                            backgroundColor: maroon,
                            borderRadius: 4,
                            maxBarThickness: 42,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } },
                    },
                });
            }

            // Customer Satisfaction — bar chart with per-bar colors +
            // percentage labels, matching the legend list beside it.
            const satCtx = document.getElementById('satisfactionChart');
            if (satCtx) {
                const satColors = ['#7ED9A8', '#2E8B8B', '#5BC8E8', '#F5A623', '#D9435E'];
                const satValues = [35, 30, 20, 10, 5];
                new Chart(satCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Very satisfied', 'Satisfied', 'Not sure', 'Not satisfied', 'Hate it'],
                        datasets: [{
                            data: satValues,
                            backgroundColor: satColors,
                            borderRadius: 4,
                            maxBarThickness: 36,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (ctx) => ctx.parsed.y + '%' } },
                        },
                        scales: { y: { beginAtZero: true, max: 40, ticks: { callback: (v) => v + '%' } } },
                    },
                });
            }

            // Severity Level — donut chart.
            const sevCtx = document.getElementById('severityChart');
            if (sevCtx) {
                new Chart(sevCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Critical', 'Moderate', 'Low'],
                        datasets: [{
                            data: [30, 40, 30],
                            backgroundColor: ['#FF3B30', '#FF8D28', '#34C759'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                    },
                });
            }
        });
    </script>
</body>

</html>