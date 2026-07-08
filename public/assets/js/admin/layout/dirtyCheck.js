(function (global) {
    'use strict';

    function serializeForm(form) {
        var parts = [];
        var elements = form.elements;
        for (var i = 0; i < elements.length; i++) {
            var el = elements[i];
            if (!el.name || el.type === 'submit' || el.type === 'button') continue;

            var val;
            if (el.type === 'checkbox' || el.type === 'radio') {
                val = el.checked ? '1' : '0';
            } else if (el.type === 'file') {
                // Track identity (name+size), not just count
                val = el.files
                    ? Array.prototype.map.call(el.files, function (f) { return f.name + ':' + f.size; }).join(',')
                    : '';
            } else {
                val = el.value;
            }
            parts.push(el.name + '=' + val);
        }
        return parts.join('&');
    }

    function watch(form, options) {
        options = options || {};
        var buttons = options.buttons || [];
        var baselineState = null;

        function applyButtonState(isDirty) {
            buttons.forEach(function (btn) {
                btn.disabled = !isDirty;
                btn.style.opacity = isDirty ? '1' : '0.6';
                btn.style.cursor = isDirty ? 'pointer' : 'not-allowed';
            });
        }

        function check() {
            if (baselineState === null) return;
            applyButtonState(serializeForm(form) !== baselineState);
        }

        function baseline() {
            baselineState = serializeForm(form);
            check();
        }

        form.addEventListener('input', check);
        form.addEventListener('change', check);

        (options.watchContainers || []).forEach(function (container) {
            if (!container || typeof MutationObserver === 'undefined') return;
            new MutationObserver(check).observe(container, { childList: true, subtree: true });
        });

        return {
            baseline: baseline,
            check: check,
            isDirty: function () {
                return baselineState !== null && serializeForm(form) !== baselineState;
            }
        };
    }

    global.DirtyCheck = { watch: watch };

    // Auto-init for static forms with data-dirty-check attribute
    function autoInit() {
        document.querySelectorAll('form[data-dirty-check]').forEach(function (form) {
            // Guard against double-binding if autoInit ever runs more than once,
            // and skip forms already wired up explicitly via DirtyCheck.watch().
            if (form.dataset.dirtyCheckBound === '1') return;
            form.dataset.dirtyCheckBound = '1';

            var btnSelector = form.dataset.dirtyBtn || 'button[type="submit"]';
            var buttons = Array.from(form.querySelectorAll(btnSelector));
            if (!buttons.length) return;

            var tracker = watch(form, { buttons: buttons });
            tracker.baseline();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        autoInit();
    }
    if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(autoInit).observe(document.body, { childList: true, subtree: true });
    }
})(window);