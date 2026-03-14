import './bootstrap';
import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import Dashboard from './pages/Dashboard';

const TRANSITION_MS = 170;

function markReady() {
    document.body.classList.remove('page-entering', 'page-leaving');
    document.body.classList.add('page-ready');
}

function shouldHandleNavigation(event, link) {
    if (!link || event.defaultPrevented || event.button !== 0) {
        return false;
    }

    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return false;
    }

    if (link.target === '_blank' || link.hasAttribute('download')) {
        return false;
    }

    if (link.dataset.noTransition !== undefined) {
        return false;
    }

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) {
        return false;
    }

    let url;
    try {
        url = new URL(href, window.location.origin);
    } catch (_) {
        return false;
    }

    if (url.origin !== window.location.origin) {
        return false;
    }

    if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
        return false;
    }

    return true;
}

function setupAppModals() {
    const body = document.body;

    function closeModal(modal) {
        if (!(modal instanceof HTMLElement)) {
            return;
        }

        modal.hidden = true;
        body.classList.remove('overflow-hidden');
    }

    function openModal(modal) {
        if (!(modal instanceof HTMLElement)) {
            return;
        }

        modal.hidden = false;
        body.classList.add('overflow-hidden');

        const firstInput = modal.querySelector('input, select, textarea, button');
        if (firstInput instanceof HTMLElement) {
            window.setTimeout(() => firstInput.focus(), 40);
        }
    }

    document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const modalId = trigger.getAttribute('data-modal-open');
            const modal = modalId ? document.getElementById(modalId) : null;
            openModal(modal);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const modal = trigger.closest('.app-modal');
            closeModal(modal);
        });
    });

    document.querySelectorAll('.app-modal').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target instanceof HTMLElement && event.target.hasAttribute('data-modal-dismiss')) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const openedModal = document.querySelector('.app-modal:not([hidden])');
        if (openedModal instanceof HTMLElement) {
            closeModal(openedModal);
        }
    });
}

function mountFinanceDashboard() {
    const rootElement = document.querySelector('[data-finance-dashboard-root]');
    if (!(rootElement instanceof HTMLElement)) {
        return;
    }

    const payload = window.__FINANCE_DASHBOARD__;
    if (!payload || typeof payload !== 'object') {
        return;
    }

    createRoot(rootElement).render(createElement(Dashboard, payload));
}

document.addEventListener('DOMContentLoaded', () => {
    requestAnimationFrame(markReady);
    setupAppModals();
    mountFinanceDashboard();

    document.addEventListener('click', (event) => {
        const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
        if (!shouldHandleNavigation(event, link)) {
            return;
        }

        event.preventDefault();
        const destination = link.href;
        const currentLocation = window.location.href;

        document.body.classList.remove('page-ready');
        document.body.classList.add('page-leaving');

        window.setTimeout(() => {
            window.location.assign(destination);
        }, TRANSITION_MS);

        // Fallback: if navigation does not actually happen (e.g. file download),
        // restore normal page state so the UI does not remain opaque.
        window.setTimeout(() => {
            if (window.location.href === currentLocation) {
                markReady();
            }
        }, 2200);
    });
});

window.addEventListener('pageshow', () => {
    requestAnimationFrame(markReady);
});
