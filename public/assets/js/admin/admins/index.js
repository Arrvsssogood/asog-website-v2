(function () {
    'use strict';

    function init(root) {
    var scope = root && root.querySelector ? root : document;
    var controller = new AbortController();
    var signal = controller.signal;
    var filterForm = scope.querySelector('#filterForm');
    var configNode = scope.querySelector('#adminAdminsConfig');
    var modalRoot = scope.querySelector('#adminAccountModalRoot');
    var baseUrl = configNode ? configNode.getAttribute('data-base-url') : '';
    var isFetchingPage = false;

    function bindTableEvents() {
        document.querySelectorAll('.tbl th.sortable a').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                loadPage(this.href);
            });
        });

        document.querySelectorAll('.tbl-pagination .pag-btn:not(.pag-disabled)').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                var href = this.getAttribute('href');
                if (!href || href === '#') return;
                e.preventDefault();
                loadPage(href);
            });
        });

        var clearBtn = document.querySelector('.app-btn-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var searchInput = document.getElementById('accountSearchInput');
                if (searchInput) searchInput.value = '';
                var statusSelect = document.getElementById('statusFilterSelect');
                if (statusSelect) statusSelect.value = 'all';
                var roleSelect = document.getElementById('roleFilterSelect');
                if (roleSelect) roleSelect.value = 'all';
                loadPage(this.href);
            });
        }
    }

    function loadPage(url, options) {
        options = options || {};
        if (isFetchingPage) return Promise.resolve();
        isFetchingPage = true;

        var tblWrap = document.querySelector('.tbl-wrap');
        if (tblWrap) {
            tblWrap.style.transition = 'opacity 0.15s ease';
            tblWrap.style.opacity = '0.5';
            tblWrap.style.pointerEvents = 'none';
        }

        return fetch(url, { cache: 'no-store', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { return res.text(); })
            .then(function (htmlText) {
                isFetchingPage = false;
                var parser = new DOMParser();
                var doc = parser.parseFromString(htmlText, 'text/html');

                swapNode(doc, '.accounts-admin-toolbar');
                swapNode(doc, '.grid-stats');
                swapNode(doc, '.tbl-wrap');
                syncClearButton(doc);

                if (!options.skipHistory) {
                    if (window.AdminShell && typeof window.AdminShell.updateHistory === 'function') {
                        window.AdminShell.updateHistory(url);
                    } else {
                        history.pushState(null, '', url);
                    }
                }

                bindTableEvents();
                bindFilterControls();
            })
            .catch(function () {
                isFetchingPage = false;
                if (tblWrap) {
                    tblWrap.style.opacity = '1';
                    tblWrap.style.pointerEvents = 'auto';
                }
            });
    }

    function swapNode(doc, selector) {
        var next = doc.querySelector(selector);
        var current = document.querySelector(selector);
        if (next && current) {
            current.parentNode.replaceChild(next, current);
        }
    }

    function syncClearButton(doc) {
        var newClear = doc.querySelector('.app-btn-clear');
        var currentClear = document.querySelector('.app-btn-clear');
        if (currentClear) {
            if (newClear) {
                currentClear.parentNode.replaceChild(newClear, currentClear);
            } else {
                currentClear.parentNode.removeChild(currentClear);
            }
        } else if (newClear) {
            var formNode = document.getElementById('filterForm');
            if (formNode) {
                formNode.appendChild(newClear);
            }
        }
    }

    function bindFilterControls() {
        filterForm = document.getElementById('filterForm');
        if (filterForm && filterForm.dataset.bound !== '1') {
            filterForm.dataset.bound = '1';
            filterForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var params = new URLSearchParams(new FormData(this));
                loadPage(baseUrl + '?' + params.toString());
            });
        }

        ['statusFilterSelect', 'roleFilterSelect'].forEach(function (id) {
            var select = document.getElementById(id);
            if (select && select.dataset.bound !== '1') {
                select.dataset.bound = '1';
                select.addEventListener('change', function () {
                    if (filterForm) {
                        filterForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                    }
                });
            }
        });

        if (window.AdminCustomSelect && typeof window.AdminCustomSelect.init === 'function') {
            window.AdminCustomSelect.init(document);
        }
    }

    function openModal(url) {
        if (!url || !modalRoot) return;

        fetch(url, {
            method: 'GET',
            cache: 'no-store',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (response) {
                if (!response.ok) throw new Error('Modal request failed.');
                return response.text();
            })
            .then(function (html) {
                modalRoot.innerHTML = html;
                attachModalHandlers();
            })
            .catch(function () {
                showAccountToast('error', 'Could not load the account form.');
            });
    }

    function closeModal() {
        if (modalRoot) {
            modalRoot.innerHTML = '';
        }
        document.body.classList.remove('account-modal-open');
        clearModalQuery();
    }

    function clearModalQuery() {
        var url = new URL(window.location.href);
        if (!url.searchParams.has('modal')) return;
        url.searchParams.delete('modal');
        url.searchParams.delete('accountId');
        history.replaceState(null, '', url.toString());
    }

    function attachModalHandlers() {
        var modal = modalRoot ? modalRoot.querySelector('[data-account-modal]') : null;
        if (!modal) return;

        document.body.classList.add('account-modal-open');

        if (window.AdminCustomSelect && typeof window.AdminCustomSelect.init === 'function') {
            window.AdminCustomSelect.init(modal);
        }

        bindRoleAction(modal);
        bindStatusAction(modal);

        // Dirty check
        var modalForm = modal.querySelector('form[data-account-modal-form]');
        if (modalForm && window.DirtyCheck) {
            var saveBtn = modalForm.querySelector('button[type="submit"]');
            if (saveBtn) {
                var tracker = window.DirtyCheck.watch(modalForm, {
                    buttons: [saveBtn],
                });
                requestAnimationFrame(function () {
                    setTimeout(function () { tracker.baseline(); }, 50);
                });
            }
        }

        var form = modal.querySelector('form[data-account-modal-form]');
        if (!form) return;

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.ok || result.data.ok === false) {
                        if (result.data.modalHtml) {
                            modalRoot.innerHTML = result.data.modalHtml;
                            attachModalHandlers();
                        } else if (result.data.message) {
                            showAccountToast('error', result.data.message);
                        }
                        return;
                    }

                    closeModal();
                    sendWelcomeEmailInBackground(result.data);
                    if (result.data.requiresReauth) {
                        showAccountToast('info', 'Your access was updated. Please sign in again to continue.');
                        window.setTimeout(function () {
                            window.location.href = result.data.logoutUrl || '/asog-admin/logout';
                        }, 900);
                        return;
                    }

                    showAccountToast('success', result.data.message || 'Account saved.');
                    loadPage(location.href, { skipHistory: true });
                })
                .catch(function () {
                    showAccountToast('error', 'Something went wrong while saving.');
                })
                .finally(function () {
                    if (submitBtn) submitBtn.disabled = false;
                });
        });
    }

    function sendWelcomeEmailInBackground(data) {
        if (!data || !data.welcomeEmailUrl) return;

        var formData = new FormData();
        if (data.csrfName && data.csrfHash) {
            formData.append(data.csrfName, data.csrfHash);
        }

        fetch(data.welcomeEmailUrl, {
            method: 'POST',
            body: formData,
            cache: 'no-store',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    return { ok: response.ok, data: payload };
                }).catch(function () {
                    return { ok: response.ok, data: {} };
                });
            })
            .then(function (result) {
                if (!result.ok || result.data.ok === false) {
                    showAccountToast('error', result.data.message || 'Account was created, but the welcome email could not be sent.');
                }
            })
            .catch(function () {
                showAccountToast('error', 'Account was created, but the welcome email could not be sent.');
            });
    }

    function bindRoleAction(modal) {
        var select = modal.querySelector('[data-account-role-select]');
        if (!select) return;

        var accountId = parseInt(modal.getAttribute('data-account-id') || '0', 10);
        var currentAdminId = parseInt(modal.getAttribute('data-current-admin-id') || '0', 10);
        var originalRole = select.getAttribute('data-original-role') || modal.getAttribute('data-original-role') || '';
        var previousRole = select.value;
        var suppressChange = false;

        function syncCustomSelect() {
            var wrap = select.closest('.csel');
            if (!wrap) return;
            var selectedOption = select.options[select.selectedIndex];
            var valueNode = wrap.querySelector('.csel-val');
            if (valueNode && selectedOption) {
                valueNode.textContent = selectedOption.text;
                valueNode.classList.toggle('csel-val--ph', !selectedOption.value);
            }
            wrap.querySelectorAll('.csel-opt').forEach(function (optionNode) {
                optionNode.classList.toggle('csel-opt--sel', optionNode.dataset.value === select.value);
            });
        }

        function restorePreviousRole() {
            suppressChange = true;
            select.value = previousRole;
            syncCustomSelect();
            window.setTimeout(function () {
                suppressChange = false;
            }, 0);
        }

        select.addEventListener('change', function () {
            if (suppressChange) return;

            var nextRole = select.value;
            var isSelfSuperadminDemotion = accountId > 0
                && accountId === currentAdminId
                && originalRole === 'superadmin'
                && nextRole !== 'superadmin';

            if (!isSelfSuperadminDemotion) {
                previousRole = nextRole;
                return;
            }

            if (!window.AdminDeleteConfirm || typeof window.AdminDeleteConfirm.ask !== 'function') {
                previousRole = nextRole;
                return;
            }

            window.AdminDeleteConfirm.ask({
                title: 'Change your role?',
                message: 'You are changing your own role from Super Admin. After you save changes, you will be signed out and your dashboard will use the new role access the next time you sign in. Continue only if another active super admin can still manage accounts.',
                confirmLabel: 'Continue',
                cancelLabel: 'Cancel',
            }).then(function (confirmed) {
                if (confirmed) {
                    previousRole = nextRole;
                    return;
                }
                restorePreviousRole();
            });
        });
    }

    function bindStatusAction(modal) {
        var wrap = modal.querySelector('[data-account-status-action]');
        if (!wrap) return;

        var input = wrap.querySelector('[data-account-status-input]');
        var title = wrap.querySelector('[data-account-status-title]');
        var copy = wrap.querySelector('[data-account-status-copy]');
        var button = wrap.querySelector('[data-account-status-toggle]');
        if (!input || !title || !copy || !button) return;
        var accountId = parseInt(modal.getAttribute('data-account-id') || '0', 10);
        var currentAdminId = parseInt(modal.getAttribute('data-current-admin-id') || '0', 10);

        function renderStatus() {
            var active = input.value === '1';
            title.textContent = active ? 'Active' : 'Inactive';
            copy.textContent = active
                ? 'This account can sign in. Save changes after deactivating to apply the update.'
                : 'This account cannot sign in. Save changes after activating to apply the update.';
            button.textContent = active ? 'Deactivate Account' : 'Activate Account';
            button.classList.toggle('btn-danger-soft', active);
            button.classList.toggle('btn-p', !active);
        }

        function flipStatus() {
            input.value = input.value === '1' ? '0' : '1';
            renderStatus();
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }

        button.addEventListener('click', function () {
            var isSelfDeactivation = input.value === '1' && accountId > 0 && accountId === currentAdminId;
            if (!isSelfDeactivation) {
                flipStatus();
                return;
            }

            if (!window.AdminDeleteConfirm || typeof window.AdminDeleteConfirm.ask !== 'function') {
                flipStatus();
                return;
            }

            window.AdminDeleteConfirm.ask({
                title: 'Deactivate your account?',
                message: 'You are deactivating your own account. After you save changes, you may be unable to sign in. Continue only if another active super admin can manage accounts.',
                confirmLabel: 'Continue',
                cancelLabel: 'Cancel',
            }).then(function (confirmed) {
                if (confirmed) {
                    flipStatus();
                }
            });
        });

        renderStatus();
    }

    function showAccountToast(type, message) {
        var toast = document.createElement('div');
        toast.className = 'org-admin-toast account-admin-toast';
        var bg = type === 'error' ? '#fee2e2' : (type === 'info' ? '#dbeafe' : '#dcfce7');
        var color = type === 'error' ? '#991b1b' : (type === 'info' ? '#0c4a6e' : '#166534');
        toast.style.background = bg;
        toast.style.color = color;
        toast.style.boxShadow = '0 10px 30px rgba(15,23,42,.12)';
        toast.textContent = message;
        document.body.appendChild(toast);
        requestAnimationFrame(function () {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });
        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-8px)';
            setTimeout(function () { toast.remove(); }, 250);
        }, 2600);
    }

    function openModalFromQuery() {
        var params = new URLSearchParams(window.location.search);
        var modal = params.get('modal');
        if (modal === 'add') {
            openModal(baseUrl + '/modal');
        } else if (modal === 'edit') {
            var accountId = parseInt(params.get('accountId') || '0', 10);
            if (accountId > 0) {
                openModal(baseUrl + '/modal/' + accountId);
            }
        }
    }

    document.addEventListener('click', function (event) {
        var closeTrigger = event.target.closest('[data-account-modal-close]');
        if (closeTrigger) {
            event.preventDefault();
            closeModal();
            return;
        }

        var trigger = event.target.closest('.js-account-modal-trigger');
        if (!trigger) return;
        event.preventDefault();
        openModal(trigger.getAttribute('data-modal-url'));
    }, { signal: signal });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modalRoot && modalRoot.querySelector('[data-account-modal]')) {
            closeModal();
        }
    }, { signal: signal });

    bindFilterControls();
    bindTableEvents();
    openModalFromQuery();

    return function () {
        controller.abort();
        if (modalRoot) modalRoot.innerHTML = '';
        document.body.classList.remove('account-modal-open');
    };
    }

    if (window.AdminShell && typeof window.AdminShell.register === 'function') {
        window.AdminShell.register('admins', { init: init });
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(document); });
    } else {
        init(document);
    }
})();
