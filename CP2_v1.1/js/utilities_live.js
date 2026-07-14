/**
 * utilities_live.js
 * ----------------------------------------------------------------------
 * Keeps admin.php's Utilities account list up to date in real time by
 * polling get_users.php on an interval and re-rendering the table when
 * the data actually changes — so a new pending self-signup (or any
 * change another admin makes) shows up automatically, without the
 * admin having to manually reload the page.
 *
 * Deliberately only touches the DOM when the fetched data differs from
 * what's already shown (cheap JSON-string compare), so it doesn't
 * fight with an admin who's mid-edit elsewhere on the page, and newly
 * appeared rows get a brief highlight so they're easy to notice.
 * ----------------------------------------------------------------------
 */

(function () {
    const POLL_INTERVAL_MS = 6000;

    const ROLE_LABELS = { user: 'User', techn: 'Technician', admin: 'Administrator' };

    let lastUsersJson = null;
    let lastKnownIds = new Set();

    /**
     * Builds the inner HTML for one account row, matching the same
     * markup/classes admin.php's PHP renders, so styling and the
     * existing Edit/Activate/Delete actions keep working identically.
     */
    function renderRow(u, isNew) {
        const isActive = u.status === 'active';
        const roleLabel = ROLE_LABELS[u.role] || (u.role.charAt(0).toUpperCase() + u.role.slice(1));
        const fullName = escapeHtml(`${u.first_name} ${u.last_name}`);
        const nameForConfirm = JSON.stringify(`${u.first_name} ${u.last_name}`);

        return `
            <div class="ticket-row${isNew ? ' row-new' : ''}">
                <span class="ucol-id">#${u.id}</span>
                <span class="ucol-name">${fullName}</span>
                <span class="ucol-email">${escapeHtml(u.email)}</span>
                <span class="ucol-role">
                    <span class="profile-role-badge role-${escapeHtml(u.role)}">${escapeHtml(roleLabel)}</span>
                </span>
                <span class="ucol-status">
                    <span class="status-badge ${isActive ? 'active-account' : 'inactive-account'}">
                        ${isActive ? 'Active' : 'Pending'}
                    </span>
                </span>
                <span class="ucol-action">
                    <a href="?tab=utilities&edit_id=${u.id}" class="btn-assign">Edit</a>
                    <form action="../logic/user_admin_mngmnt.php" method="post">
                        <input type="hidden" name="id" value="${u.id}">
                        <input type="hidden" name="status" value="${isActive ? 'inactive' : 'active'}">
                        <button type="submit" name="set_status" class="btn-update-status">
                            ${isActive ? 'Deactivate' : 'Activate'}
                        </button>
                    </form>
                    <form action="../logic/user_admin_mngmnt.php" method="post"
                        onsubmit="return confirm('Permanently delete ' + ${nameForConfirm} + '\\'s account? This can\\'t be undone.');">
                        <input type="hidden" name="id" value="${u.id}">
                        <button type="submit" name="delete_user" class="btn-delete">Delete</button>
                    </form>
                </span>
            </div>`;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderEmptyState() {
        return `
            <div class="tickets-empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3m0-4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1M12 13c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4m-6 5v-.99c.2-.72 3.3-2.01 6-2.01s5.8 1.29 6 2v1z"></path>
                    <path d="M17.24 12.02c1.7.6 3.76 1.83 3.76 2.98v3h-3v-3.03c0-1.24-.51-2.24-1.31-3.01.19.02.37.04.55.06"></path>
                    <path d="M16.5 8.5c0-1.32-.4-2.53-1.09-3.49.19-.01.39-.01.59-.01 1.93 0 3.5 1.57 3.5 3.5S17.93 12 16 12c-.2 0-.4 0-.59-.01.69-.96 1.09-2.17 1.09-3.49"></path>
                </svg>
                <p>No user accounts found.</p>
            </div>`;
    }

    async function pollUsers() {
        const container = document.getElementById('utilities-users-body');
        if (!container) {
            // Not on the Utilities view right now — nothing to update.
            return;
        }

        let data;
        try {
            const res = await fetch('../logic/get_users.php', { credentials: 'same-origin' });
            if (!res.ok) {
                // 401 most likely means the session expired between
                // polls — don't force a redirect from here; just stop
                // polling quietly and let the next real navigation
                // handle re-authentication normally.
                return;
            }
            data = await res.json();
        } catch (err) {
            // Network hiccup — just try again on the next interval.
            return;
        }

        if (data.error || !Array.isArray(data.users)) {
            return;
        }

        const usersJson = JSON.stringify(data.users);
        if (usersJson === lastUsersJson) {
            return; // nothing changed, don't touch the DOM
        }
        lastUsersJson = usersJson;

        const currentIds = new Set(data.users.map(u => u.id));
        const isFirstRun = lastKnownIds.size === 0;

        container.innerHTML = data.users.length === 0
            ? renderEmptyState()
            : data.users.map(u => renderRow(u, !isFirstRun && !lastKnownIds.has(u.id))).join('');

        lastKnownIds = currentIds;

        // utilities_filter.js owns the actual show/hide logic and
        // remembers which tab is active — re-run it now so a filtered
        // view (e.g. "Pending Approval") doesn't silently show every
        // role again just because a poll rebuilt the row list.
        if (typeof window.applyUtilitiesFilter === 'function') {
            window.applyUtilitiesFilter(window.currentUtilitiesFilter || 'all');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Seed lastUsersJson with what the server already rendered so
        // the very first poll doesn't immediately flag every existing
        // row as "new".
        const container = document.getElementById('utilities-users-body');
        if (container) {
            const ids = Array.from(container.querySelectorAll('.ucol-id'))
                .map(el => parseInt(el.textContent.replace('#', ''), 10))
                .filter(n => !isNaN(n));
            lastKnownIds = new Set(ids);
        }

        setInterval(pollUsers, POLL_INTERVAL_MS);
    });
})();