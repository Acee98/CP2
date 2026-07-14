<?php
/**
 * user.php — top-of-file PHP
 * ----------------------------------------------------------------------
 * This page previously had zero PHP — pure static markup. It now reads
 * the session that user_mngmnt.php's login flow populates
 * ($_SESSION['first_name'/'last_name'/'email'/'role']) so the My
 * Profile page (further down) can render the actual registered
 * account instead of the "Juan De La Cruz" placeholder.
 *
 * Access guard: matches SS3 ("role-based access control to restrict
 * system features based on user roles"). Anyone without a session, or
 * logged in as techn/admin, is sent back to the login page rather than
 * seeing the User dashboard — mirrors the redirect-by-role logic
 * user_mngmnt.php already uses right after a successful login.
 *
 * Note on scope: only first_name / last_name / email / role are ever
 * read into display variables below. The password hash is never
 * pulled into a page — it isn't a value that should be displayed or
 * echoed anywhere, only verified server-side when the Settings page's
 * password-change form is eventually wired up.
 */
// FIX: session setup now centralized in session_config.php — see
// that file for why (dedicated session storage folder + consistent
// cookie config across every session-using page).
require_once '../logic/session_config.php';

if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'user') {
    header("Location: ../pages/login_signup.php");
    exit();
}

$first_name = $_SESSION['first_name'];
$last_name  = $_SESSION['last_name'];
$email      = $_SESSION['email'];
$role       = $_SESSION['role'];

$full_name = trim($first_name . ' ' . $last_name);

// Avatar initials (e.g. "Juan De La Cruz" -> "JD"), used in place of
// an uploaded photo — no photo/avatar column exists in the users
// table, so initials are the closest "based on registered data"
// stand-in.
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// Human-readable role label for the badge on the Profile page.
$role_labels = ['user' => 'User', 'techn' => 'Technician', 'admin' => 'Administrator'];
$role_label = $role_labels[$role] ?? ucfirst($role);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/main_interface.css?v=1.9">
    <title>ZPGC Services | User</title>
</head>

<body data-page="tickets">
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

                            <!-- Tickets -->
                            <li class="nav-list-item selected" data-nav="tickets">
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
                            <li class="nav-list-item" data-nav="messages">
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
                            <li class="nav-list-item" data-nav="profile">
                                <a href="#" class="nav-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M12 12c2.7 0 8 1.34 8 4v2H4v-2c0-2.66 5.3-4 8-4m0-10a4 4 0 0 1 4 4 4 4 0 0 1-4 4 4 4 0 0 1-4-4 4 4 0 0 1 4-4">
                                        </path>
                                    </svg>
                                    <span class="link-text">My Profile</span>
                                </a>
                            </li>

                            <!-- Settings -->
                            <li class="nav-list-item" data-nav="settings">
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
                                <a href="../pages/login_signup.php" class="nav-link">
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
                            <input type="search" class="search-bar" placeholder="Search..." aria-label="Search">
                        </div>
                    </header>
                </div>

                <!-- Toolbar: filter tabs left, New Ticket button right -->
                <div class="tickets-toolbar">
                    <div class="tickets-filter-tabs">
                        <button class="filter-tab active-tab">All</button>
                        <button class="filter-tab">Ongoing</button>
                        <button class="filter-tab">Processing</button>
                        <button class="filter-tab">Resolved</button>
                        <button class="filter-tab">Pending</button>
                    </div>
                    <a href="../pages/ticket.php" class="btn-new-ticket">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path d="M3 13h8v8h2v-8h8v-2h-8V3h-2v8H3z"></path>
                        </svg>
                        New Ticket
                    </a>
                </div>

                <!-- Ticket table — white background, light-gray header -->
                <div class="tickets-list">
                    <div class="tickets-list-header">
                        <span class="tickets-col-id">ID</span>
                        <span class="tickets-col-subject">Subject</span>
                        <span class="tickets-col-description">Description</span>
                        <span class="tickets-col-status">Status</span>
                    </div>
                    <div class="tickets-list-body">
                        <!--
                            Empty state: Boxicons ticket icon + message.
                            Icon is sized to 48px via .tickets-empty-state svg
                            in main_interface.css; color is #c8c8c8 (muted).
                        -->
                        <div class="tickets-empty-state">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <!--Boxicons v3.0.8 https://boxicons.com | License https://docs.boxicons.com/free-->
                                <path
                                    d="M21 8h-2V3a1 1 0 0 0-1.37-.93l-15 6c-.09.04-.16.1-.24.16-.03.02-.06.03-.09.06-.04.04-.05.08-.08.12-.05.06-.1.12-.13.19-.01.02 0 .05-.02.08-.03.1-.06.2-.06.31v3.55c0 .48.33.89.8.98a1.499 1.499 0 0 1 0 2.94c-.47.09-.8.5-.8.98v3.55c0 .55.45 1 1 1h18c.55 0 1-.45 1-1v-3.55c0-.48-.33-.89-.8-.98a1.499 1.499 0 0 1 0-2.94c.47-.09.8-.5.8-.98V8.99c0-.55-.45-1-1-1Zm-4 0H8.19L17 4.48zm3 3.84c-1.2.57-2 1.79-2 3.16s.8 2.59 2 3.16V20h-4v-2h-1v2H4v-1.84c1.2-.57 2-1.79 2-3.16s-.8-2.59-2-3.16V10h11v1h1v-1h4z">
                                </path>
                                <path d="M15 12h1v2h-1zm0 3h1v2h-1z"></path>
                            </svg>
                            <p>No tickets submitted yet.</p>
                        </div>
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

                <div class="mailbox-container">

                    <!-- LEFT: Conversation thread list -->
                    <aside class="mailbox-threads">
                        <div class="mailbox-threads-header">
                            <span class="mailbox-threads-title">Conversations</span>
                        </div>
                        <div class="mailbox-threads-list">
                            <div class="mailbox-threads-empty">
                                <p>No conversations yet.</p>
                                <p>Once a technician responds to your ticket, the conversation will appear here.</p>
                            </div>
                        </div>
                    </aside>

                    <!-- RIGHT: Chat area -->
                    <div class="mailbox-chat">
                        <div class="mailbox-chat-empty">
                            <!--
                                Boxicons v3.0.8 chat icon — replaces the
                                generic speech bubble used previously.
                                Sized to 52px by .mailbox-chat-empty svg.
                            -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <!--Boxicons v3.0.8 https://boxicons.com | License https://docs.boxicons.com/free-->
                                <path
                                    d="M4 19h3v2c0 .36.19.69.51.87a1 1 0 0 0 1-.01L13.27 19h6.72c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2M4 5h16v12h-7c-.18 0-.36.05-.51.14L9 19.23V18c0-.55-.45-1-1-1H4z">
                                </path>
                            </svg>
                            <p class="mailbox-chat-empty-title">No conversation selected</p>
                            <p class="mailbox-chat-empty-sub">Select a conversation from the left to view messages with your assigned technician.</p>
                        </div>
                    </div>

                </div>

            </div>
            <!-- /page-messages -->


            <!-- ============================================================
                 MY PROFILE — body[data-page="profile"]
                 ------------------------------------------------------------
                 Identity + account-presence page: who you are (name, email,
                 phone), how you want to be reached (notification/language
                 preferences), and where you're currently signed in
                 (connected devices). Distinct purpose from Settings below —
                 Settings is about the app itself and how you sign in, this
                 page is about you.
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

                    <!-- Cover card: large avatar + display name + role -->
                    <div class="profile-cover-card">
                        <div class="profile-avatar-wrap">
                            <div class="profile-avatar-lg"><?= htmlspecialchars($initials) ?></div>
                            <button type="button" class="profile-avatar-edit" aria-label="Change photo">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M9 3 7.17 5H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2h-3.17L15 3zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5m0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3">
                                    </path>
                                </svg>
                            </button>
                        </div>
                        <p class="profile-display-name"><?= htmlspecialchars($full_name) ?></p>
                        <span class="profile-role-badge role-<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role_label) ?></span>
                    </div>

                    <!-- Personal Information + Contact Preferences, side by side -->
                    <div class="profile-row">

                        <div class="profile-card">
                            <h2>Personal Information</h2>

                            <div class="profile-info-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5m0-8c1.65 0 3 1.35 3 3s-1.35 3-3 3-3-1.35-3-3 1.35-3 3-3M4 22h16c.55 0 1-.45 1-1v-1c0-3.86-3.14-7-7-7h-4c-3.86 0-7 3.14-7 7v1c0 .55.45 1 1 1m6-7h4c2.76 0 5 2.24 5 5H5c0-2.76 2.24-5 5-5">
                                    </path>
                                </svg>
                                <div class="profile-info-text">
                                    <span class="profile-info-label">Name</span>
                                    <span class="profile-info-value"><?= htmlspecialchars($full_name) ?></span>
                                </div>
                                <button type="button" class="profile-info-edit" aria-label="Edit name">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zm17.71-10.04a1 1 0 0 0 0-1.41l-2.51-2.51a1 1 0 0 0-1.41 0l-1.96 1.96 3.75 3.75z">
                                        </path>
                                    </svg>
                                </button>
                            </div>

                            <div class="profile-info-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M12 2C6.49 2 2 6.49 2 12s4.49 10 10 10c1.47 0 2.96-.37 4.44-1.1l-.89-1.79c-1.2.59-2.4.9-3.56.9-4.41 0-8-3.59-8-8S7.59 4 12 4s8 3.59 8 8v1c0 .69-.31 2-1.5 2-1.4 0-1.49-1.82-1.5-2V8h-2v.03C14.16 7.4 13.13 7 12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5c1.45 0 2.75-.63 3.66-1.62.52.89 1.41 1.62 2.84 1.62 2.27 0 3.5-2.06 3.5-4v-1c0-5.51-4.49-10-10-10m0 13c-1.65 0-3-1.35-3-3s1.35-3 3-3 3 1.35 3 3-1.35 3-3 3">
                                    </path>
                                </svg>
                                <div class="profile-info-text">
                                    <span class="profile-info-label">E-Mail</span>
                                    <span class="profile-info-value"><?= htmlspecialchars($email) ?></span>
                                </div>
                                <button type="button" class="profile-info-edit" aria-label="Edit email">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zm17.71-10.04a1 1 0 0 0 0-1.41l-2.51-2.51a1 1 0 0 0-1.41 0l-1.96 1.96 3.75 3.75z">
                                        </path>
                                    </svg>
                                </button>
                            </div>

                            <div class="profile-info-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M18.07 22h.35c.47-.02.9-.26 1.17-.64l2.14-3.09c.23-.33.32-.74.24-1.14s-.31-.74-.64-.97l-4.64-3.09a1.47 1.47 0 0 0-.83-.25c-.41 0-.81.16-1.1.48l-1.47 1.59c-.69-.43-1.61-1.07-2.36-1.82-.72-.72-1.37-1.64-1.82-2.36l1.59-1.47c.54-.5.64-1.32.23-1.93L7.84 2.67c-.22-.33-.57-.57-.97-.64a1.46 1.46 0 0 0-1.13.24L2.65 4.41c-.39.27-.62.7-.64 1.17-.03.69-.16 6.9 4.68 11.74 4.35 4.35 9.81 4.69 11.38 4.69ZM6.88 10.05c-.16.15-.21.39-.11.59.05.09 1.15 2.24 2.74 3.84 1.6 1.6 3.75 2.7 3.84 2.75.2.1.44.06.59-.11l1.99-2.15 3.86 2.57-1.7 2.46c-1.16 0-6.13-.24-9.99-4.1S4 7.06 4 5.91l2.46-1.7 2.57 3.86-2.15 1.99Z">
                                    </path>
                                </svg>
                                <div class="profile-info-text">
                                    <span class="profile-info-label">Phone Number</span>
                                    <span class="profile-info-value profile-info-empty">Not provided</span>
                                </div>
                                <button type="button" class="profile-info-edit" aria-label="Edit phone number">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                        fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75zm17.71-10.04a1 1 0 0 0 0-1.41l-2.51-2.51a1 1 0 0 0-1.41 0l-1.96 1.96 3.75 3.75z">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="profile-card">
                            <h2>Contact Preferences</h2>

                            <div class="profile-pref-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M12 2C6.49 2 2 6.49 2 12s4.49 10 10 10c1.47 0 2.96-.37 4.44-1.1l-.89-1.79c-1.2.59-2.4.9-3.56.9-4.41 0-8-3.59-8-8S7.59 4 12 4s8 3.59 8 8v1c0 .69-.31 2-1.5 2-1.4 0-1.49-1.82-1.5-2V8h-2v.03C14.16 7.4 13.13 7 12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5c1.45 0 2.75-.63 3.66-1.62.52.89 1.41 1.62 2.84 1.62 2.27 0 3.5-2.06 3.5-4v-1c0-5.51-4.49-10-10-10m0 13c-1.65 0-3-1.35-3-3s1.35-3 3-3 3 1.35 3 3-1.35 3-3 3">
                                    </path>
                                </svg>
                                <span class="profile-pref-label">E-Mail Notifications</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="profile-pref-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M4 19h3v2c0 .36.19.69.51.87a1 1 0 0 0 1-.01L13.27 19h6.72c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2M4 5h16v12h-7c-.18 0-.36.05-.51.14L9 19.23V18c0-.55-.45-1-1-1H4z">
                                    </path>
                                </svg>
                                <span class="profile-pref-label">SMS Notifications</span>
                                <label class="toggle-switch">
                                    <input type="checkbox">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="profile-pref-row">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M17 11c-.4 0-.75.23-.91.59l-4 9 1.83.81 1.07-2.41h4.03l1.07 2.41 1.83-.81-4-9a1 1 0 0 0-.91-.59Zm-1.13 6L17 14.46 18.13 17zm-3.62-2.03.49-1.94c-.13-.03-1.6-.43-3.17-1.42 1.4-1.41 2.49-3.26 2.74-5.61h1.68V4h-5V2h-2v2H2v2h8.3c-.25 1.91-1.19 3.34-2.31 4.4C7.3 9.75 6.68 8.96 6.25 8H4.12c.5 1.44 1.33 2.63 2.3 3.61-1.57.99-3.04 1.39-3.17 1.42l.49 1.94c1.18-.3 2.76-.96 4.26-2.02 1.49 1.06 3.08 1.72 4.25 2.02">
                                    </path>
                                </svg>
                                <span class="profile-pref-label">Preferred Language</span>
                                <select class="profile-pref-select">
                                    <option>English</option>
                                    <option>Filipino</option>
                                </select>
                            </div>
                        </div>

                    </div>

                    <!--
                        CONNECTED DEVICES
                        ------------------------------------------------------
                        This app has no sessions/devices table (config.php
                        only ever connects to a "users" table), so there is
                        no real way to know what devices an account is
                        signed in on. Rather than fabricate rows, this shows
                        the same icon+text empty state pattern already used
                        for "No tickets found." / "No conversations yet."
                        elsewhere in the dashboard.
                    -->
                    <div class="profile-card profile-card-full">
                        <h2>Connected Devices</h2>
                        <p class="profile-card-subtitle">Where your account is connected</p>

                        <div class="device-empty-state">
                            <p>No connected devices to show.</p>
                        </div>
                    </div>

                </div>
            </div>
            <!-- /page-profile -->


            <!-- ============================================================
                 SETTINGS — body[data-page="settings"]
                 ------------------------------------------------------------
                 App-level page, distinct from My Profile: Appearance
                 controls how the app looks to you; Security/Sign-In Methods
                 controls how you get into your account. Nothing here is
                 about who you are — that lives on the Profile page above.
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

                    <!-- Appearance: how the app looks -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h2>Appearance</h2>
                            <p>Stay in control of your browsing look.</p>
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
                        <a href="#" class="security-row">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M18.07 22h.35c.47-.02.9-.26 1.17-.64l2.14-3.09c.23-.33.32-.74.24-1.14s-.31-.74-.64-.97l-4.64-3.09a1.47 1.47 0 0 0-.83-.25c-.41 0-.81.16-1.1.48l-1.47 1.59c-.69-.43-1.61-1.07-2.36-1.82-.72-.72-1.37-1.64-1.82-2.36l1.59-1.47c.54-.5.64-1.32.23-1.93L7.84 2.67c-.22-.33-.57-.57-.97-.64a1.46 1.46 0 0 0-1.13.24L2.65 4.41c-.39.27-.62.7-.64 1.17-.03.69-.16 6.9 4.68 11.74 4.35 4.35 9.81 4.69 11.38 4.69ZM6.88 10.05c-.16.15-.21.39-.11.59.05.09 1.15 2.24 2.74 3.84 1.6 1.6 3.75 2.7 3.84 2.75.2.1.44.06.59-.11l1.99-2.15 3.86 2.57-1.7 2.46c-1.16 0-6.13-.24-9.99-4.1S4 7.06 4 5.91l2.46-1.7 2.57 3.86-2.15 1.99Z">
                                </path>
                            </svg>
                            <span class="security-row-label">Recovery Phone Number</span>
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
            <!-- /page-settings -->

        </section>
    </main>

    <script src="../js/behavior.js?v=1.9"></script>
</body>

</html>