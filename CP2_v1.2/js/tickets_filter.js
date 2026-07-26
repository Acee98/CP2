/**
 * tickets_filter.js
 * Status filter tabs + search for ticket tables (user, admin, techn).
 */
(function () {
    function rowMatchesSearch(row, query) {
        if (!query) {
            return true;
        }
        const haystack = (row.dataset.search || row.textContent || '').toLowerCase();
        return haystack.includes(query.toLowerCase().trim());
    }

    function applyTicketsFilter(container, statusFilter, searchQuery) {
        if (!container) {
            return;
        }

        const rows = container.querySelectorAll('.ticket-row');
        let anyVisible = false;

        rows.forEach((row) => {
            const rowStatus = row.dataset.status || '';
            const statusMatch = statusFilter === 'all' || rowStatus === statusFilter;
            const searchMatch = rowMatchesSearch(row, searchQuery);
            const matches = statusMatch && searchMatch;
            row.style.display = matches ? '' : 'none';
            if (matches) {
                anyVisible = true;
            }
        });

        let noMatchEl = container.querySelector('.tickets-filter-empty');
        if (!anyVisible && rows.length > 0) {
            if (!noMatchEl) {
                noMatchEl = document.createElement('div');
                noMatchEl.className = 'tickets-empty-state tickets-filter-empty';
                noMatchEl.innerHTML = '<p>No tickets match your filter or search.</p>';
                container.appendChild(noMatchEl);
            }
        } else if (noMatchEl) {
            noMatchEl.remove();
        }
    }

    function initTicketsFilter(config) {
        const tabs = document.getElementById(config.tabsId);
        const container = document.getElementById(config.bodyId);
        const page = document.getElementById(config.pageId);
        if (!tabs || !container || !page) {
            return;
        }

        const searchInput = page.querySelector('.tickets-search-input');
        let activeFilter = 'all';
        let searchQuery = '';

        function refresh() {
            applyTicketsFilter(container, activeFilter, searchQuery);
        }

        tabs.querySelectorAll('.filter-tab').forEach((btn) => {
            btn.addEventListener('click', () => {
                tabs.querySelectorAll('.filter-tab').forEach((b) => b.classList.remove('active-tab'));
                btn.classList.add('active-tab');
                activeFilter = btn.dataset.filter || 'all';
                refresh();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                searchQuery = searchInput.value;
                refresh();
            });
        }

        const activeBtn = tabs.querySelector('.filter-tab.active-tab');
        activeFilter = activeBtn ? (activeBtn.dataset.filter || 'all') : 'all';
        refresh();
    }

    document.addEventListener('DOMContentLoaded', () => {
        [
            {
                tabsId: 'user-tickets-filter-tabs',
                bodyId: 'user-tickets-body',
                pageId: 'page-tickets',
            },
            {
                tabsId: 'admin-tickets-filter-tabs',
                bodyId: 'admin-tickets-body',
                pageId: 'page-tickets',
            },
            {
                tabsId: 'techn-tickets-filter-tabs',
                bodyId: 'techn-tickets-body',
                pageId: 'page-tickets',
            },
        ].forEach(initTicketsFilter);
    });
})();
