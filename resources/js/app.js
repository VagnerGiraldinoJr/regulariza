import './bootstrap';

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

document.addEventListener('DOMContentLoaded', () => {
    requestAnimationFrame(markReady);

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
