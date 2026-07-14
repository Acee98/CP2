/**
 * utilities_filter.js
 * ----------------------------------------------------------------------
 * Wires up the All / User / Technician / Administrator / Pending
 * Approval tabs above the Utilities account list. Purely a client-side
 * show/hide filter over rows already rendered by admin.php (or
 * re-rendered by utilities_live.js) — the full account list is already
 * in the DOM, so no extra request is needed just to filter it.
 *
 * Exposes window.applyUtilitiesFilter(filter) and tracks the active
 * filter on window.currentUtilitiesFilter, so utilities_live.js can
 * re-apply the same filter after it rebuilds the row list on a poll —
 * otherwise a live update would silently reset the view back to "All"
 * every few seconds while someone's looking at a filtered list.
 * ----------------------------------------------------------------------
 */

(function () {
    window.currentUtilitiesFilter = window.currentUtilitiesFilter || 'all';

    function rowMatchesFilter(row, filter) {
        if (filter === 'all') {
            return true;
        }

        const statusBadge = row.querySelector('.status-badge');
        const isPending = statusBadge ? statusBadge.classList.contains('inactive-account') : false;

        if (filter === 'pending') {
            return isPending;
        }

        // user / techn / admin — matched against the role badge's own
        // role-<x> class, so this is only ever true for exactly one
        // filter per row, no matter the account's active/pending status.
        const roleBadge = row.querySelector('.profile-role-badge');
        return roleBadge ? roleBadge.classList.contains('role-' + filter) : false;
    }

    window.applyUtilitiesFilter = function (filter) {
        window.currentUtilitiesFilter = filter;
        const container = document.getElementById('utilities-users-body');
        if (!container) {
            return;
        }

        const rows = container.querySelectorAll('.ticket-row');
        let anyVisible = false;

        rows.forEach((row) => {
            const matches = rowMatchesFilter(row, filter);
            row.style.display = matches ? '' : 'none';
            if (matches) {
                anyVisible = true;
            }
        });

        // "No accounts match this filter" — distinct from admin.php's
        // own "no accounts exist at all" empty state, which stays
        // untouched since it only renders server-side when the whole
        // table is genuinely empty.
        let noMatchEl = container.querySelector('.utilities-filter-empty');
        if (!anyVisible && rows.length > 0) {
            if (!noMatchEl) {
                noMatchEl = document.createElement('div');
                noMatchEl.className = 'tickets-empty-state utilities-filter-empty';
                noMatchEl.innerHTML = '<p>No accounts match this filter.</p>';
                container.appendChild(noMatchEl);
            }
        } else if (noMatchEl) {
            noMatchEl.remove();
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.getElementById('utilities-filter-tabs');
        if (!tabs) {
            return;
        }

        tabs.querySelectorAll('.filter-tab').forEach((btn) => {
            btn.addEventListener('click', () => {
                tabs.querySelectorAll('.filter-tab').forEach((b) => b.classList.remove('active-tab'));
                btn.classList.add('active-tab');
                window.applyUtilitiesFilter(btn.dataset.filter);
            });
        });

        const activeBtn = tabs.querySelector('.filter-tab.active-tab');
        window.applyUtilitiesFilter(activeBtn ? activeBtn.dataset.filter : 'all');
    });
})();