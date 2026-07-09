(function () {
    'use strict';

    var modal = document.getElementById('adminDiscardConfirm');
    if (!modal) return;

    var titleEl = document.getElementById('adminDiscardConfirmTitle');
    var messageEl = document.getElementById('adminDiscardConfirmMessage');
    var okBtn = document.getElementById('adminDiscardConfirmOk');
    var cancelBtns = modal.querySelectorAll('[data-admin-discard-cancel]');
    var activeResolver = null;
    var lastFocused = null;

    /* ── Modal API ──────────────── */

    function ask(options) {
        options = options || {};
        titleEl.textContent = options.title || 'Discard unsaved changes?';
        messageEl.textContent = options.message || 'You have unsaved changes on this page. Leaving now will discard them.';
        okBtn.textContent = options.confirmLabel || 'Discard changes';

        lastFocused = document.activeElement;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        
        modal.setAttribute('tabindex', '-1'); 
        
        document.body.classList.add('admin-discard-confirm-open');
        window.setTimeout(function () { okBtn.focus(); }, 30);

        return new Promise(function (resolve) {
            activeResolver = resolve;
        });
    }

    function close(result) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('tabindex');
        document.body.classList.remove('admin-discard-confirm-open');

        var resolver = activeResolver;
        activeResolver = null;
        if (resolver) resolver(!!result);

        if (lastFocused && typeof lastFocused.focus === 'function') {
            window.setTimeout(function () { lastFocused.focus(); }, 30);
        }
    }

    okBtn.addEventListener('click', function () {
        close(true);
    });

    cancelBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            close(false);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (!modal.classList.contains('is-open')) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            close(false);
        }
    });

    window.AdminUnsavedChanges = {
        ask: ask,
        close: close,
    };

    /* ── When to show it ──────────────── */

    var isConfirmedNavigation = false;

    function hasUnsavedWork() {
        return !!(window.DirtyCheck && window.DirtyCheck.isAnyDirty());
    }

    // Real browser unload (tab close, refresh, typed URL, bookmark)
    window.addEventListener('beforeunload', function (event) {
        if (isConfirmedNavigation) return; // already confirmed via our own modal
        if (!hasUnsavedWork()) return;
        event.preventDefault();
        event.returnValue = '';
    });

    // Form submission is an intentional save — don't warn on the resulting navigation
    document.addEventListener('submit', function (event) {
        if (!event.defaultPrevented) {
            isConfirmedNavigation = true;
        }
    });

    // In-app link navigation (clicking to another admin section).
    document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return; // let "open in new tab" etc. behave normally

        var link = event.target.closest && event.target.closest('a[href]');
        if (!link) return;
        if (link.target && link.target !== '_self') return; // new tab / new window
        if (link.hasAttribute('download')) return;

        var rawHref = link.getAttribute('href') || '';
        if (rawHref === '' || rawHref.charAt(0) === '#') return; // in-page anchor or JS-handled placeholder link — not a real navigation

        var url;
        try {
            url = new URL(link.href, window.location.href);
        } catch (e) {
            return;
        }
        
        // Skip mailto:, tel:, javascript:, or external links
        if (url.origin !== window.location.origin || url.origin === 'null') return; 

        if (!hasUnsavedWork()) return;

        event.preventDefault();
        ask().then(function (confirmed) {
            if (!confirmed) return;
            isConfirmedNavigation = true;

            window.location.href = link.href;
        });
    });
})();