<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/main_interface.css?v=1.3">
    <title>ZPGC Services | Administrator</title>
</head>

<body data-page="dashboard">
    <main class="main-wrap">
        <header class="main-head">
            <div class="main-nav">
                <nav class="navbar">
                    <div class="navbar-nav">

                        <!-- Logo + collapse toggler -->
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
                            <li class="nav-list-item selected" data-nav="dashboard">
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

                            <!-- Tickets (NEW — Step 2) -->
                            <li class="nav-list-item" data-nav="tickets">
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
                            <li class="nav-list-item" data-nav="utilities">
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

                            <!-- Analytics -->
                            <li class="nav-list-item" data-nav="analytics">
                                <a href="#" class="nav-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path d="M4 2H2v19c0 .55.45 1 1 1h19v-2H4z"></path>
                                        <path d="M17 12h2v6h-2zm-5-8h2v14h-2zM7 9h2v9H7z"></path>
                                    </svg>
                                    <span class="link-text">Analytics</span>
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
                        <div class="profile-circle"></div>
                    </header>
                </div>

                <!-- Status Cards (Step 1) -->
                <div class="status-cards">

                    <!-- Ongoing -->
                    <div class="status-card">
                        <div class="status-card-info">
                            <span class="status-card-count">0</span>
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
                            <span class="status-card-count">0</span>
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
                            <span class="status-card-count">0</span>
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
                    <div class="status-card">
                        <div class="status-card-info">
                            <span class="status-card-count">0</span>
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

            </div>
            <!-- /page-dashboard -->


            <!-- ============================================================
                 TICKETS — body[data-page="tickets"]   (NEW — Step 2)
                 ============================================================
                 Admin view: ALL tickets across all users.
                 Columns: ID | Subject | Category | Priority | Status |
                          Assigned To | Actions
                 Toolbar:  filter tabs (All / Ongoing / Processing /
                           Resolved / Pending) — no New Ticket button
                           since admin doesn't submit tickets.
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
                        <div class="profile-circle"></div>
                    </header>
                </div>

                <!--
                    Toolbar: filter tabs on the left.
                    Admin doesn't create tickets, so no New Ticket button.
                    The active-tab class on "All" is the default visual
                    state — JS filtering logic is wired in the backend phase.
                -->
                <div class="tickets-toolbar">
                    <div class="tickets-filter-tabs">
                        <button class="filter-tab active-tab">All</button>
                        <button class="filter-tab">Ongoing</button>
                        <button class="filter-tab">Processing</button>
                        <button class="filter-tab">Resolved</button>
                        <button class="filter-tab">Pending</button>
                    </div>
                </div>

                <!--
                    Ticket table.
                    7 columns: ID, Subject, Category, Priority, Status,
                    Assigned To, Actions.
                    Header spans use .tcol-* classes (defined in
                    main_interface.css Step 2 additions) to avoid
                    conflicting with user.php's .tickets-col-* classes.
                    Body shows empty state until backend is wired up.
                -->
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
                    <div class="tickets-list-body">
                        <!--
                            Empty state: Boxicons ticket icon + message,
                            matching the icon/markup pattern already used
                            in user.php's .tickets-empty-state.
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
                            <p>No tickets found.</p>
                        </div>
                    </div>
                </div>

            </div>
            <!-- /page-tickets -->


            <!-- ============================================================
                 UTILITIES — body[data-page="utilities"]
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
                        <div class="profile-circle"></div>
                    </header>
                </div>
            </div>


            <!-- ============================================================
                 ANALYTICS — body[data-page="analytics"]
                 ============================================================ -->
            <div class="page-content" id="page-analytics">
                <div class="head">
                    <header>
                        <h1>Analytics</h1>
                        <div class="search-bar-wrapper">
                            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M18 10c0-4.41-3.59-8-8-8s-8 3.59-8 8 3.59 8 8 8c1.85 0 3.54-.63 4.9-1.69l5.1 5.1L21.41 20l-5.1-5.1A8 8 0 0 0 18 10M4 10c0-3.31 2.69-6 6-6s6 2.69 6 6-2.69 6-6 6-6-2.69-6-6">
                                </path>
                            </svg>
                            <input type="search" class="search-bar" placeholder="Search..." aria-label="Search">
                        </div>
                        <div class="profile-circle"></div>
                    </header>
                </div>
            </div>


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
                        <div class="profile-circle"></div>
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
                                <p>Once a user or technician sends a message on a ticket, the conversation will appear here.</p>
                            </div>
                        </div>
                    </aside>

                    <!-- RIGHT: Chat area -->
                    <div class="mailbox-chat">
                        <div class="mailbox-chat-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <!--Boxicons v3.0.8 https://boxicons.com | License https://docs.boxicons.com/free-->
                                <path
                                    d="M4 19h3v2c0 .36.19.69.51.87a1 1 0 0 0 1-.01L13.27 19h6.72c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2M4 5h16v12h-7c-.18 0-.36.05-.51.14L9 19.23V18c0-.55-.45-1-1-1H4z">
                                </path>
                            </svg>
                            <p class="mailbox-chat-empty-title">No conversation selected</p>
                            <p class="mailbox-chat-empty-sub">Select a conversation from the left to view messages between a user and their assigned technician.</p>
                        </div>
                    </div>

                </div>
            </div>
            <!-- /page-messages -->


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
                        <div class="profile-circle"></div>
                    </header>
                </div>
            </div>

        </section>
    </main>

    <script src="../js/behavior.js?v=1.3"></script>
</body>

</html>