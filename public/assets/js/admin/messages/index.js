var currentMsg       = null;
var selectedIds      = new Set();
var configNode       = document.getElementById('adminMessagesConfig');
var msgBaseUrl       = configNode ? configNode.getAttribute('data-base-url') : '';

var selectAll     = document.getElementById('selectAll');
var smartCaretBtn = document.getElementById('smartCaretBtn');
var smartChkMenu  = document.getElementById('smartChkMenu');

function loadPage(url) {
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.text(); })
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
            if (newClear && oldClear) {
                oldClear.replaceWith(newClear);
            } else if (!newClear && oldClear) {
                oldClear.remove();
            } else if (newClear && !oldClear) {
                var filterSlot = document.querySelector('.filter-slot');
                if (filterSlot) filterSlot.appendChild(newClear);
            }

            selectedIds.clear();
            updateBulkState();
            bindInboxEvents();
            history.pushState(null, '', url);
        });
}

function bindInboxEvents() {
    document.querySelectorAll('.msg-row').forEach(function (row) {
        var id = row.getAttribute('data-id');
        row.onclick = function () { openMsg(id); };
        var chk = row.querySelector('.row-select');
        if (chk) {
            chk.onchange = function () { onRowCheck(this, id); };
        }
    });

    document.querySelectorAll('.tbl-pagination .pag-btn:not(.pag-disabled)').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            var href = this.getAttribute('href');
            if (!href || href === '#') return;
            e.preventDefault();
            loadPage(href);
        });
    });
}

bindInboxEvents();

function getRows() {
    return Array.from(document.querySelectorAll('.msg-row'));
}

function getVisibleRows() {
    return getRows().filter(function (r) { return r.style.display !== 'none'; });
}

function showToast(text) {
    var t = document.getElementById('toast');
    t.textContent = text;
    t.classList.add('show');
    setTimeout(function () { t.classList.remove('show'); }, 2600);
}

function formatDate(str) {
    var d = new Date(str);
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var h = d.getHours(), m = d.getMinutes();
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
    var count   = selectedIds.size;
    var bar     = document.getElementById('bulkActionsBar');
    var form    = document.getElementById('filterForm');
    var countEl = document.getElementById('bulkCount');

    if (bar)     bar.classList.toggle('bulk-visible', count > 0);
    if (form)    form.classList.toggle('search-hidden', count > 0);
    if (countEl) countEl.textContent = count;

    getRows().forEach(function (row) {
        var id    = row.getAttribute('data-id');
        var check = row.querySelector('.row-select');
        var sel   = selectedIds.has(id);
        row.classList.toggle('row-selected', sel);
        if (check) check.checked = sel;
    });

    if (selectAll) {
        var visible  = getVisibleRows();
        var selCount = visible.filter(function (r) { return selectedIds.has(r.getAttribute('data-id')); }).length;
        selectAll.checked       = visible.length > 0 && selCount === visible.length;
        selectAll.indeterminate = selCount > 0 && selCount < visible.length;
    }

    // Smart button state: only enable Mark Read/Unread when relevant
    var hasUnread = false;
    var hasRead   = false;
    selectedIds.forEach(function (id) {
        var row = document.querySelector('.msg-row[data-id="' + id + '"]');
        if (row) {
            if (row.getAttribute('data-read') === '0') hasUnread = true;
            else hasRead = true;
        }
    });
    var btnRead   = document.querySelector('.bulk-act-read');
    var btnUnread = document.querySelector('.bulk-act-unread');
    if (btnRead)   btnRead.disabled   = !hasUnread;
    if (btnUnread) btnUnread.disabled = !hasRead;
}

function smartSelect(type) {
    var visible = getVisibleRows();
    switch (type) {
        case 'all':
            visible.forEach(function (r) { selectedIds.add(r.getAttribute('data-id')); });
            break;
        case 'none':
            selectedIds.clear();
            break;
        case 'read':
            visible.forEach(function (r) {
                if (r.getAttribute('data-read') === '1') selectedIds.add(r.getAttribute('data-id'));
            });
            break;
        case 'unread':
            visible.forEach(function (r) {
                if (r.getAttribute('data-read') === '0') selectedIds.add(r.getAttribute('data-id'));
            });
            break;
    }
    updateBulkState();
}

if (selectAll) {
    selectAll.addEventListener('change', function () {
        smartSelect(this.checked ? 'all' : 'none');
    });
}

if (smartCaretBtn) {
    smartCaretBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        smartChkMenu && smartChkMenu.classList.toggle('open');
    });
}

if (smartChkMenu) {
    smartChkMenu.addEventListener('click', function (e) {
        var item = e.target.closest('.scm-item');
        if (!item) return;
        smartSelect(item.getAttribute('data-smart'));
        smartChkMenu.classList.remove('open');
    });
}

document.addEventListener('click', function () {
    if (smartChkMenu) smartChkMenu.classList.remove('open');
});
function onRowCheck(checkbox, id) {
    if (checkbox.checked) {
        selectedIds.add(String(id));
    } else {
        selectedIds.delete(String(id));
    }
    updateBulkState();
}

function bulkDo(action) {
    var ids = Array.from(selectedIds);
    if (!ids.length || !msgBaseUrl) return;

    if (action === 'delete') {
        // Change note: bulk delete now uses the shared admin modal instead of native confirm().
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

function runBulkAction(action, ids) {
    fetch(msgBaseUrl + '/bulk', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body:    JSON.stringify({ action: action, ids: ids.map(Number) })
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
        if (!d.success) { showToast(d.error || 'Something went wrong.'); return; }

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
                    dot.classList.toggle('dot-read',   isRead);
                }
            });
        }

        selectedIds.clear();
        updateBulkState();
        showToast(d.message);
    });
}

var filterSelectEl = document.getElementById('filterSelect');
if (filterSelectEl) {
    filterSelectEl.addEventListener('change', function () {
        var val     = this.value;
        var viewInp = document.getElementById('viewInput');
        var dateInp = document.getElementById('dateInput');
        if (val === 'archived') {
            viewInp.value = 'archived';
            dateInp.value = 'all';
        } else {
            var parts     = val.split('-');
            viewInp.value = 'inbox';
            dateInp.value = parts[1] || 'all';
        }
        document.getElementById('filterForm').dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    });
}

var filterForm = document.getElementById('filterForm');
if (filterForm) {
    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var params  = new URLSearchParams(new FormData(this));
        var url     = msgBaseUrl + '?' + params.toString();
        loadPage(url);
    });
}

window.addEventListener('popstate', function () {
    loadPage(location.href);
});

document.addEventListener('click', function (e) {
    var clearBtn = e.target.closest('.app-btn-clear');
    if (clearBtn) {
        e.preventDefault();
        loadPage(clearBtn.getAttribute('href'));
    }
});

function archiveSingle() {
    if (!currentMsg || !msgBaseUrl) return;
    var btn    = document.getElementById('btnArchive');
    var action = (btn && btn.getAttribute('data-action')) || 'archive';

    fetch(msgBaseUrl + '/bulk', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body:    JSON.stringify({ action: action, ids: [currentMsg.id] })
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
        if (!d.success) { showToast(d.error || 'Something went wrong.'); return; }
        var row = document.querySelector('.msg-row[data-id="' + currentMsg.id + '"]');
        if (row) row.remove();
        updateInboxCount();
        backToInbox();
        showToast(d.message);
    });
}

function openMsg(id) {
    if (!msgBaseUrl) return;
    getRows().forEach(function (r) { r.classList.remove('active'); });
    var row = document.querySelector('.msg-row[data-id="' + id + '"]');
    if (row) row.classList.add('active');

    fetch(msgBaseUrl + '/' + id)
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.error) { showToast(d.error); return; }
            currentMsg = d;

            document.getElementById('rSubject').textContent = 'Message from ' + d.name;
            document.getElementById('rAvatar').textContent  = d.name.charAt(0);
            document.getElementById('rName').textContent    = d.name;
            document.getElementById('rEmail').innerHTML     = '<a href="mailto:' + d.email + '">' + d.email + '</a>';
            document.getElementById('rDate').textContent    = formatDate(d.createdAt);
            document.getElementById('rBody').textContent    = d.message;
            document.getElementById('rReply').href         = 'mailto:' + encodeURIComponent(d.email) + '?subject=' + encodeURIComponent('Re: Your message to ASOG TBI');
            document.getElementById('toggleLabel').textContent = d.isRead == 1 ? 'Mark unread' : 'Mark read';

            var archiveBtn   = document.getElementById('btnArchive');
            var archiveLabel = document.getElementById('archiveLabel');
            if (d.isArchived == 1) {
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
                if (dot) { dot.classList.remove('dot-unread'); dot.classList.add('dot-read'); }
            }

            document.getElementById('inbox').style.display = 'none';
            document.getElementById('reader').classList.add('open');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
}

function backToInbox() {
    document.getElementById('reader').classList.remove('open');
    document.getElementById('inbox').style.display = '';
    currentMsg = null;
    getRows().forEach(function (r) { r.classList.remove('active'); });
}

function toggleRead() {
    if (!currentMsg || !msgBaseUrl) return;
    fetch(msgBaseUrl + '/' + currentMsg.id + '/read', {
        method:  'PUT',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
        if (!d.success) return;
        currentMsg.isRead = d.isRead;
        document.getElementById('toggleLabel').textContent = d.isRead ? 'Mark unread' : 'Mark read';

        var row = document.querySelector('.msg-row[data-id="' + currentMsg.id + '"]');
        if (row) {
            row.setAttribute('data-read', d.isRead ? '1' : '0');
            var dot = row.querySelector('.dot');
            if (d.isRead) {
                row.classList.remove('unread');
                if (dot) { dot.classList.remove('dot-unread'); dot.classList.add('dot-read'); }
            } else {
                row.classList.add('unread');
                if (dot) { dot.classList.add('dot-unread'); dot.classList.remove('dot-read'); }
            }
        }
        showToast(d.message);
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

function closeConfirm() {
    if (window.AdminDeleteConfirm) {
        window.AdminDeleteConfirm.close(false);
    }
}

function doDelete() {
    if (!currentMsg || !msgBaseUrl) return;
    var id = currentMsg.id;
    fetch(msgBaseUrl + '/' + id, {
        method:  'DELETE',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
        if (!d.success) return;
        var row = document.querySelector('.msg-row[data-id="' + id + '"]');
        if (row) row.remove();
        updateInboxCount();
        backToInbox();
        showToast('Message deleted.');
    });
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        if (document.getElementById('reader').classList.contains('open')) {
            backToInbox();
        }
    }
    if (e.key === 'u' && currentMsg && !e.ctrlKey && !e.metaKey && document.activeElement.tagName !== 'INPUT') {
        toggleRead();
    }
});
