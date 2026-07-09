(function () {
    'use strict';

    function init(root) {
        var scope = root && root.querySelector ? root : document;
        var controller = new AbortController();
        var signal = controller.signal;
        var currentMsg = null;
        var selectedIds = new Set();
        var configNode = scope.querySelector('#adminMessagesConfig');
        var msgBaseUrl = configNode ? configNode.getAttribute('data-base-url') : '';
        var selectAll = null;
        var smartCaretBtn = null;
        var smartChkMenu = null;

        function lockListScroll() {
            document.documentElement.classList.add('admin-messages-list-lock');
        }

        function unlockListScroll() {
            document.documentElement.classList.remove('admin-messages-list-lock');
        }

        function refreshRefs() {
            selectAll = document.getElementById('selectAll');
            smartCaretBtn = document.getElementById('smartCaretBtn');
            smartChkMenu = document.getElementById('smartChkMenu');
        }

        function updateHistory(url) {
            if (window.AdminShell && typeof window.AdminShell.updateHistory === 'function') {
                window.AdminShell.updateHistory(url);
            } else {
                history.pushState(null, '', url);
            }
        }

        function getRows() {
            return Array.from(document.querySelectorAll('.msg-row'));
        }

        function getVisibleRows() {
            return getRows().filter(function (r) { return r.style.display !== 'none'; });
        }

        function showToast(text) {
            var t = document.getElementById('toast');
            if (!t) return;
            t.textContent = text;
            t.classList.add('show');
            setTimeout(function () { t.classList.remove('show'); }, 2600);
        }

        function formatDate(str) {
            var d = new Date(str);
            var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            var h = d.getHours();
            var m = d.getMinutes();
            var ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear() + ', ' + h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
        }

        function updateInboxCount() {
            var count = getRows().length;
            var el = document.getElementById('barCount');
            if (el) el.textContent = count + ' message' + (count !== 1 ? 's' : '');
        }

        function updateBulkState() {
            var count = selectedIds.size;
            var bar = document.getElementById('bulkActionsBar');
            var form = document.getElementById('filterForm');
            var countEl = document.getElementById('bulkCount');

            if (bar) bar.classList.toggle('bulk-visible', count > 0);
            if (form) form.classList.toggle('search-hidden', count > 0);
            if (countEl) countEl.textContent = count;

            getRows().forEach(function (row) {
                var id = row.getAttribute('data-id');
                var check = row.querySelector('.row-select');
                var selected = selectedIds.has(id);
                row.classList.toggle('row-selected', selected);
                if (check) check.checked = selected;
            });

            if (selectAll) {
                var visible = getVisibleRows();
                var selectedCount = visible.filter(function (r) { return selectedIds.has(r.getAttribute('data-id')); }).length;
                selectAll.checked = visible.length > 0 && selectedCount === visible.length;
                selectAll.indeterminate = selectedCount > 0 && selectedCount < visible.length;
            }

            var hasUnread = false;
            var hasRead = false;
            selectedIds.forEach(function (id) {
                var row = document.querySelector('.msg-row[data-id="' + id + '"]');
                if (!row) return;
                if (row.getAttribute('data-read') === '0') hasUnread = true;
                else hasRead = true;
            });

            var btnRead = document.querySelector('.bulk-act-read');
            var btnUnread = document.querySelector('.bulk-act-unread');
            if (btnRead) btnRead.disabled = !hasUnread;
            if (btnUnread) btnUnread.disabled = !hasRead;
        }

        function onRowCheck(checkbox, id) {
            if (checkbox.checked) selectedIds.add(String(id));
            else selectedIds.delete(String(id));
            updateBulkState();
        }

        function smartSelect(type) {
            var visible = getVisibleRows();
            if (type === 'none') {
                selectedIds.clear();
            } else {
                visible.forEach(function (row) {
                    var shouldSelect = type === 'all'
                        || (type === 'read' && row.getAttribute('data-read') === '1')
                        || (type === 'unread' && row.getAttribute('data-read') === '0');
                    if (shouldSelect) selectedIds.add(row.getAttribute('data-id'));
                });
            }
            updateBulkState();
        }

        function bindStaticControls() {
            refreshRefs();

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    smartSelect(this.checked ? 'all' : 'none');
                }, { signal: signal });
            }

            if (smartCaretBtn) {
                smartCaretBtn.addEventListener('click', function (event) {
                    event.stopPropagation();
                    if (smartChkMenu) smartChkMenu.classList.toggle('open');
                }, { signal: signal });
            }

            if (smartChkMenu) {
                smartChkMenu.addEventListener('click', function (event) {
                    var item = event.target.closest('.scm-item');
                    if (!item) return;
                    smartSelect(item.getAttribute('data-smart'));
                    smartChkMenu.classList.remove('open');
                }, { signal: signal });
            }

            var filterSelectEl = document.getElementById('filterSelect');
            if (filterSelectEl) {
                filterSelectEl.addEventListener('change', function () {
                    var val = this.value;
                    var viewInp = document.getElementById('viewInput');
                    var dateInp = document.getElementById('dateInput');
                    if (!viewInp || !dateInp) return;

                    if (val === 'archived') {
                        viewInp.value = 'archived';
                        dateInp.value = 'all';
                    } else {
                        var parts = val.split('-');
                        viewInp.value = 'inbox';
                        dateInp.value = parts[1] || 'all';
                    }

                    var filterForm = document.getElementById('filterForm');
                    if (filterForm) filterForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                }, { signal: signal });
            }

            var filterForm = document.getElementById('filterForm');
            if (filterForm) {
                filterForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    var params = new URLSearchParams(new FormData(this));
                    loadPage(msgBaseUrl + '?' + params.toString());
                }, { signal: signal });
            }
        }

        function bindInboxEvents() {
            refreshRefs();
            getRows().forEach(function (row) {
                var id = row.getAttribute('data-id');
                row.onclick = function () { openMsg(id); };
                var checkbox = row.querySelector('.row-select');
                if (checkbox) {
                    checkbox.onchange = function () { onRowCheck(this, id); };
                }
            });

            document.querySelectorAll('.tbl-pagination .pag-btn:not(.pag-disabled)').forEach(function (btn) {
                btn.addEventListener('click', function (event) {
                    var href = this.getAttribute('href');
                    if (!href || href === '#') return;
                    event.preventDefault();
                    loadPage(href);
                }, { signal: signal });
            });
        }

        function loadPage(url) {
            if (!url) return;
            fetch(url, { cache: 'no-store', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) { return response.text(); })
                .then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var newStats = doc.querySelector('.grid-stats');
                    var oldStats = document.querySelector('.grid-stats');
                    if (newStats && oldStats) oldStats.replaceWith(newStats);

                    var newInbox = doc.querySelector('.inbox-wrap');
                    var oldInbox = document.querySelector('.inbox-wrap');
                    if (newInbox && oldInbox) oldInbox.replaceWith(newInbox);

                    var newClear = doc.querySelector('.app-btn-clear');
                    var oldClear = document.querySelector('.app-btn-clear');
                    if (newClear && oldClear) oldClear.replaceWith(newClear);
                    else if (!newClear && oldClear) oldClear.remove();
                    else if (newClear) {
                        var filterSlot = document.querySelector('.filter-slot');
                        if (filterSlot) filterSlot.appendChild(newClear);
                    }

                    selectedIds.clear();
                    refreshRefs();
                    bindInboxEvents();
                    updateBulkState();
                    updateHistory(url);
                });
        }

        function runBulkAction(action, ids) {
            fetch(msgBaseUrl + '/bulk', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: action, ids: ids.map(Number) })
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data.success) {
                        showToast(data.error || 'Something went wrong.');
                        return;
                    }

                    if (action === 'delete' || action === 'archive' || action === 'unarchive') {
                        ids.forEach(function (id) {
                            var row = document.querySelector('.msg-row[data-id="' + id + '"]');
                            if (row) row.remove();
                        });
                        updateInboxCount();
                    } else {
                        var isRead = action === 'mark_read';
                        ids.forEach(function (id) {
                            var row = document.querySelector('.msg-row[data-id="' + id + '"]');
                            if (!row) return;
                            row.classList.toggle('unread', !isRead);
                            row.setAttribute('data-read', isRead ? '1' : '0');
                            var dot = row.querySelector('.dot');
                            if (dot) {
                                dot.classList.toggle('dot-unread', !isRead);
                                dot.classList.toggle('dot-read', isRead);
                            }
                        });
                    }

                    selectedIds.clear();
                    updateBulkState();
                    showToast(data.message);
                });
        }

        function bulkDo(action) {
            var ids = Array.from(selectedIds);
            if (!ids.length || !msgBaseUrl) return;

            if (action === 'delete') {
                var confirmBulkDelete = window.AdminDeleteConfirm
                    ? window.AdminDeleteConfirm.ask({
                        title: 'Delete selected messages?',
                        message: 'This removes ' + ids.length + ' selected contact message' + (ids.length !== 1 ? 's' : '') + ' from the inbox and archive. This action cannot be undone.',
                    })
                    : Promise.resolve(confirm('Permanently delete ' + ids.length + ' message' + (ids.length !== 1 ? 's' : '') + '? This cannot be undone.'));

                confirmBulkDelete.then(function (confirmed) {
                    if (confirmed) runBulkAction(action, ids);
                });
                return;
            }

            runBulkAction(action, ids);
        }

        function archiveSingle() {
            if (!currentMsg || !msgBaseUrl) return;
            var btn = document.getElementById('btnArchive');
            var action = (btn && btn.getAttribute('data-action')) || 'archive';

            fetch(msgBaseUrl + '/bulk', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ action: action, ids: [currentMsg.id] })
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data.success) {
                        showToast(data.error || 'Something went wrong.');
                        return;
                    }
                    var row = document.querySelector('.msg-row[data-id="' + currentMsg.id + '"]');
                    if (row) row.remove();
                    updateInboxCount();
                    backToInbox();
                    showToast(data.message);
                });
        }

        function openMsg(id) {
            if (!msgBaseUrl) return;
            getRows().forEach(function (row) { row.classList.remove('active'); });
            var row = document.querySelector('.msg-row[data-id="' + id + '"]');
            if (row) row.classList.add('active');

            fetch(msgBaseUrl + '/' + id)
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.error) {
                        showToast(data.error);
                        return;
                    }

                    currentMsg = data;
                    document.getElementById('rSubject').textContent = 'Message from ' + data.name;
                    document.getElementById('rAvatar').textContent = data.name.charAt(0);
                    document.getElementById('rName').textContent = data.name;
                    document.getElementById('rEmail').innerHTML = '<a href="mailto:' + data.email + '">' + data.email + '</a>';
                    document.getElementById('rDate').textContent = formatDate(data.createdAt);
                    document.getElementById('rBody').textContent = data.message;
                    document.getElementById('rReply').href = 'mailto:' + encodeURIComponent(data.email) + '?subject=' + encodeURIComponent('Re: Your message to ASOG TBI');
                    document.getElementById('toggleLabel').textContent = data.isRead == 1 ? 'Mark unread' : 'Mark read';

                    var archiveBtn = document.getElementById('btnArchive');
                    var archiveLabel = document.getElementById('archiveLabel');
                    if (data.isArchived == 1) {
                        archiveBtn.setAttribute('data-action', 'unarchive');
                        archiveLabel.textContent = 'Move to Inbox';
                    } else {
                        archiveBtn.setAttribute('data-action', 'archive');
                        archiveLabel.textContent = 'Archive';
                    }

                    if (row) {
                        row.classList.remove('unread');
                        row.setAttribute('data-read', '1');
                        var dot = row.querySelector('.dot');
                        if (dot) {
                            dot.classList.remove('dot-unread');
                            dot.classList.add('dot-read');
                        }
                    }

                    document.getElementById('inbox').style.display = 'none';
                    document.getElementById('reader').classList.add('open');
                    unlockListScroll();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
        }

        function backToInbox() {
            var reader = document.getElementById('reader');
            var inbox = document.getElementById('inbox');
            if (reader) reader.classList.remove('open');
            if (inbox) inbox.style.display = '';
            lockListScroll();
            currentMsg = null;
            getRows().forEach(function (row) { row.classList.remove('active'); });
        }

        function toggleRead() {
            if (!currentMsg || !msgBaseUrl) return;
            fetch(msgBaseUrl + '/' + currentMsg.id + '/read', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data.success) return;
                    currentMsg.isRead = data.isRead;
                    document.getElementById('toggleLabel').textContent = data.isRead ? 'Mark unread' : 'Mark read';

                    var row = document.querySelector('.msg-row[data-id="' + currentMsg.id + '"]');
                    if (row) {
                        row.setAttribute('data-read', data.isRead ? '1' : '0');
                        var dot = row.querySelector('.dot');
                        row.classList.toggle('unread', !data.isRead);
                        if (dot) {
                            dot.classList.toggle('dot-unread', !data.isRead);
                            dot.classList.toggle('dot-read', !!data.isRead);
                        }
                    }
                    showToast(data.message);
                });
        }

        function doDelete() {
            if (!currentMsg || !msgBaseUrl) return;
            var id = currentMsg.id;
            fetch(msgBaseUrl + '/' + id, {
                method: 'DELETE',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data.success) return;
                    var row = document.querySelector('.msg-row[data-id="' + id + '"]');
                    if (row) row.remove();
                    updateInboxCount();
                    backToInbox();
                    showToast('Message deleted.');
                });
        }

        function confirmDelete() {
            if (!currentMsg) return;
            var confirmSingleDelete = window.AdminDeleteConfirm
                ? window.AdminDeleteConfirm.ask({
                    title: 'Delete message?',
                    message: 'This removes the contact message from "' + currentMsg.name + '" from the inbox and archive. This action cannot be undone.',
                })
                : Promise.resolve(confirm('Delete message from "' + currentMsg.name + '"? This cannot be undone.'));

            confirmSingleDelete.then(function (confirmed) {
                if (confirmed) doDelete();
            });
        }

        document.addEventListener('click', function (event) {
            if (smartChkMenu) smartChkMenu.classList.remove('open');
            var clearBtn = event.target.closest('.app-btn-clear');
            if (clearBtn) {
                event.preventDefault();
                loadPage(clearBtn.getAttribute('href'));
            }
        }, { signal: signal });

        document.addEventListener('keydown', function (event) {
            var reader = document.getElementById('reader');
            if (event.key === 'Escape' && reader && reader.classList.contains('open')) {
                backToInbox();
            }
            if (event.key === 'u' && currentMsg && !event.ctrlKey && !event.metaKey && document.activeElement.tagName !== 'INPUT') {
                toggleRead();
            }
        }, { signal: signal });

        bindStaticControls();
        bindInboxEvents();
        lockListScroll();

        window.bulkDo = bulkDo;
        window.openMsg = openMsg;
        window.backToInbox = backToInbox;
        window.toggleRead = toggleRead;
        window.archiveSingle = archiveSingle;
        window.confirmDelete = confirmDelete;

        return function () {
            controller.abort();
            unlockListScroll();
            delete window.bulkDo;
            delete window.openMsg;
            delete window.backToInbox;
            delete window.toggleRead;
            delete window.archiveSingle;
            delete window.confirmDelete;
        };
    }

    if (window.AdminShell && typeof window.AdminShell.register === 'function') {
        window.AdminShell.register('messages', { init: init });
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(document); });
    } else {
        init(document);
    }
})();
