(function () {
    var modal = document.getElementById('adminDeleteConfirm');
    if (!modal) return;

    var titleEl = document.getElementById('adminDeleteConfirmTitle');
    var messageEl = document.getElementById('adminDeleteConfirmMessage');
    var okBtn = document.getElementById('adminDeleteConfirmOk');
    var cancelBtns = modal.querySelectorAll('[data-admin-delete-cancel]');
    var activeResolver = null;
    var lastFocused = null;
    var isSubmittingForm = false;

    // Change note: form submissions and AJAX deletes share this promise API.
    function ask(options) {
        options = options || {};
        titleEl.textContent = options.title || 'Delete item?';
        messageEl.textContent = options.message || 'This removes the selected item from the admin panel. This action cannot be undone.';
        okBtn.textContent = options.confirmLabel || 'Delete';

        lastFocused = document.activeElement;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('admin-delete-confirm-open');
        window.setTimeout(function () { okBtn.focus(); }, 30);

        return new Promise(function (resolve) {
            activeResolver = resolve;
        });
    }

    function close(result) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('admin-delete-confirm-open');

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

    document.addEventListener('submit', function (event) {
        var form = event.target.closest && event.target.closest('form[data-admin-delete-confirm]');
        if (!form || isSubmittingForm) return;

        event.preventDefault();
        ask({
            title: form.dataset.confirmTitle,
            message: form.dataset.confirmMessage,
            confirmLabel: form.dataset.confirmLabel || 'Delete',
        }).then(function (confirmed) {
            if (!confirmed) return;
            isSubmittingForm = true;
            HTMLFormElement.prototype.submit.call(form);
            isSubmittingForm = false;
        });
    }, true);

    window.AdminDeleteConfirm = {
        ask: ask,
        close: close,
    };
})();
