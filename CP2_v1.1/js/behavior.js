/**
 * behavior.js
 * ----------------------------------------------------------------------
 * Controls the collapsible/expandable sidebar behavior used on the
 * dashboard pages (user.php, techn.php, admin.php), which all share
 * the .main-head sidebar structure defined in main_interface.css.
 *
 * Overall behavior:
 * - The sidebar starts collapsed (narrow, icons only) when the page
 *   first loads.
 * - Hovering the mouse over the sidebar expands it (shows full labels).
 * - Moving the mouse away from the sidebar collapses it again.
 * - While collapsed, a toggler button appears; clicking it also
 *   expands the sidebar (useful for touch devices where there's no
 *   hover state).
 * - Dashboard is selected on load; clicking any sidebar item moves
 *   .selected to that item (see docs/SIDEBAR_NAVIGATION.md).
 * ----------------------------------------------------------------------
 */

/**
 * Applies .selected to one nav item and removes it from all siblings.
 * Also syncs body[data-page] when the item has a data-nav value.
 */
function setSelectedNavItem(item) {
    if (!item) {
        return;
    }

    // FIX: scope the lookup to the nav-list that actually contains this
    // item (item.closest), instead of always grabbing the *first*
    // ".nav-list" in the document via document.querySelector. There's
    // only one ".nav-list" per page today, so this wasn't causing a
    // visible bug yet — but it's the kind of implicit global lookup that
    // breaks silently if the markup ever changes (e.g. a second nav
    // list added anywhere), so scoping it properly removes that risk.
    const navList = item.closest('.nav-list');
    if (!navList) {
        return;
    }

    navList.querySelectorAll('.nav-list-item').forEach((navItem) => {
        navItem.classList.remove('selected');
    });

    item.classList.add('selected');

    // FIX: always set data-page explicitly, including clearing it when
    // an item has no data-nav, so the body attribute never keeps a
    // value left over from a previous selection.
    const navId = item.getAttribute('data-nav');
    if (navId) {
        document.body.setAttribute('data-page', navId);
    } else {
        document.body.removeAttribute('data-page');
    }
}

/**
 * On load, prefers a ?tab=<data-nav> query param (used by admin.php's
 * Utilities actions — user_admin_mngmnt.php redirects back to
 * admin.php?tab=utilities after add/edit/activate/deactivate, so the
 * sidebar should land on Utilities rather than resetting to
 * Dashboard). Falls back to an existing .selected item, then
 * Dashboard, same as before.
 */
function initNavSelection() {
    const navList = document.querySelector('.nav-list');
    if (!navList) {
        return;
    }

    const tabParam = new URLSearchParams(window.location.search).get('tab');
    const tabItem = tabParam ? navList.querySelector(`[data-nav="${tabParam}"]`) : null;

    const selectedItem = tabItem
        || navList.querySelector('.nav-list-item.selected')
        || navList.querySelector('[data-nav="dashboard"]')
        || navList.querySelector('.nav-list-item:first-child');

    setSelectedNavItem(selectedItem);
}

/**
 * Clicking a sidebar item selects it immediately. Placeholder links
 * (href="#") are prevented from navigating. Real links such as Logout
 * are left alone so the browser can follow them.
 */
function initNavClickSelection() {
    const navList = document.querySelector('.nav-list');
    if (!navList) {
        return;
    }

    navList.querySelectorAll('.nav-list-item').forEach((item) => {
        const link = item.querySelector('.nav-link');
        if (!link) {
            return;
        }

        link.addEventListener('click', (event) => {
            const href = link.getAttribute('href') || '';

            if (href && href !== '#') {
                return;
            }

            event.preventDefault();
            setSelectedNavItem(item);
        });
    });
}

function initSidebarCollapse() {
    const mainHead = document.querySelector('.main-head');
    const showcaseToggler = document.querySelector('.showcase-toggler');

    if (!mainHead) {
        return;
    }

    mainHead.classList.add('active');

    if (!showcaseToggler) {
        return;
    }

    showcaseToggler.addEventListener('click', () => {
        mainHead.classList.remove('active');
    });

    mainHead.addEventListener('mouseleave', () => {
        mainHead.classList.add('active');
    });

    mainHead.addEventListener('mouseenter', () => {
        mainHead.classList.remove('active');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initNavSelection();
    initNavClickSelection();
    initSidebarCollapse();
});