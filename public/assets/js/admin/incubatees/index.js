(function () {
function init(root) {
    root = root || document;
    var on = window.AdminShell && typeof window.AdminShell.on === 'function'
        ? function (target, eventName, selectorOrHandler, handler, options) {
            return window.AdminShell.on(root, target, eventName, selectorOrHandler, handler, options);
        }
        : function (target, eventName, selectorOrHandler, handler, options) {
            var listener = typeof selectorOrHandler === 'function'
                ? selectorOrHandler
                : function (event) {
                    var matched = event.target && event.target.closest ? event.target.closest(selectorOrHandler) : null;
                    if (!matched || (target !== document && target !== window && !target.contains(matched))) return;
                    handler.call(matched, event, matched);
                };
            target.addEventListener(eventName, listener, options || false);
            return function () { target.removeEventListener(eventName, listener, options || false); };
        };

    var configEl = root.querySelector('#incubateesConfig');
    if (!configEl) {
        return;
    }

    var addUrl = configEl.dataset.addUrl || '';
    var deleteBaseUrl = configEl.dataset.deleteBaseUrl || '';
    var reorderUrl = configEl.dataset.reorderUrl || '';
    var csrfName = configEl.dataset.csrfTokenName || '';
    var csrfValue = configEl.dataset.csrfTokenValue || '';

    var cmOverlay = root.querySelector('#cmOverlay');
    var cmManageBtn = root.querySelector('#cmManageBtn');
    var cmCloseBtn = root.querySelector('#cmCloseBtn');
    var cmAddBtn = root.querySelector('#cmAddBtn');
    var cmBody = root.querySelector('#cmBody');
    var cmTotal = root.querySelector('#cmTotal');

    function getCohortCount() {
        return root.querySelectorAll('#cmBody tr[data-id]').length;
    }

    function updateCohortCount() {
        if (!cmTotal) return;
        var count = getCohortCount();
        cmTotal.textContent = count + ' cohort' + (count !== 1 ? 's' : '');
    }

    function openCohortModal() {
        if (!cmOverlay) return;
        cmOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeCohortModal() {
        if (!cmOverlay) return;
        cmOverlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    if (cmManageBtn) {
        on(cmManageBtn, 'click', openCohortModal);
    }

    if (cmCloseBtn) {
        on(cmCloseBtn, 'click', closeCohortModal);
    }

    if (cmOverlay) {
        on(cmOverlay, 'click', function (event) {
            if (event.target === cmOverlay) {
                closeCohortModal();
            }
        });
    }

    on(document, 'keydown', function (event) {
        if (event.key === 'Escape') {
            closeCohortModal();
        }
    });

    function buildDeleteButton(id, name) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'cm-del-btn';
        btn.title = 'Delete';
        btn.dataset.cohortId = String(id);
        btn.dataset.cohortName = name;
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
        return btn;
    }

    function addCohort() {
        if (!cmAddBtn || !addUrl) return;

        cmAddBtn.disabled = true;
        cmAddBtn.textContent = 'Adding...';

        var payload = {};
        payload[csrfName] = csrfValue;

        fetch(addUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data.ok || !data.cohort) {
                alert(data.error || 'Failed to add cohort');
                return;
            }

            var emptyState = root.querySelector('#cmEmptyState');
            if (emptyState) {
                emptyState.remove();
            }

            var cohort = data.cohort;
            var tr = document.createElement('tr');
            tr.dataset.id = String(cohort.id);

            var tdName = document.createElement('td');
            tdName.className = 'cm-name';
            tdName.textContent = cohort.name;

            var tdCnt = document.createElement('td');
            tdCnt.className = 'cm-cnt';
            tdCnt.textContent = '0 startups';

            var tdStatus = document.createElement('td');
            tdStatus.innerHTML = '<span class="cm-status cm-empty">Coming Soon</span>';

            var tdAction = document.createElement('td');
            tdAction.className = 'ta-right';
            tdAction.appendChild(buildDeleteButton(cohort.id, cohort.name));

            tr.appendChild(tdName);
            tr.appendChild(tdCnt);
            tr.appendChild(tdStatus);
            tr.appendChild(tdAction);

            if (cmBody) {
                cmBody.appendChild(tr);
            }

            updateCohortCount();
        })
        .catch(function () {
            alert('Network error');
        })
        .finally(function () {
            cmAddBtn.disabled = false;
            cmAddBtn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg> Add Cohort';
        });
    }

    function deleteCohort(id, name, rowEl) {
        if (!deleteBaseUrl) return;

        // Change note: cohort deletes are AJAX, so they use the shared modal promise before fetch.
        var confirmDelete = window.AdminDeleteConfirm
            ? window.AdminDeleteConfirm.ask({
                title: 'Delete cohort?',
                message: 'This removes the "' + name + '" cohort from the cohort filters and manager. This action cannot be undone.',
            })
            : Promise.resolve(confirm('Delete ' + name + '?\nThis cannot be undone.'));

        confirmDelete.then(function (confirmed) {
            if (!confirmed) {
                return;
            }

            var payload = {};
            payload[csrfName] = csrfValue;

            fetch(deleteBaseUrl + id + '/delete', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data.ok) {
                    alert(data.error || 'Failed to delete');
                    return;
                }

                if (rowEl) {
                    rowEl.remove();
                }

                updateCohortCount();

                if (getCohortCount() === 0) {
                    var body = root.querySelector('.cm-modal-body');
                    if (body && !root.querySelector('#cmEmptyState')) {
                        var empty = document.createElement('div');
                        empty.className = 'cm-empty-state';
                        empty.id = 'cmEmptyState';
                        empty.textContent = 'No cohorts yet. Add one below.';
                        body.appendChild(empty);
                    }
                }
            })
            .catch(function () {
                alert('Network error');
            });
        });
    }

    if (cmAddBtn) {
        on(cmAddBtn, 'click', addCohort);
    }

    if (cmBody) {
        on(cmBody, 'click', function (event) {
            var btn = event.target.closest('.cm-del-btn');
            if (!btn || btn.disabled) return;

            var row = btn.closest('tr');
            var id = btn.dataset.cohortId;
            var name = btn.dataset.cohortName || 'this cohort';
            deleteCohort(id, name, row);
        });
    }

    var table = root.querySelector('#incubateeTable');
    var filterButtons = root.querySelectorAll('#cohortFilterBtns .filter-btn');
    var reorderBtn = root.querySelector('#incReorderBtn');

    if (!table) {
        return;
    }

    var body = table.querySelector('tbody');
    var draggedRow = null;
    var dragPlaceholder = null;
    var dragPreview = null;
    var dragOffsetX = 0;
    var dragOffsetY = 0;
    var activePointerId = null;
    var isReorderMode = false;
    var activeFilter = 'all';
    var reorderSnapshot = '';

    function getRows() {
        return Array.from(body.querySelectorAll('tr.drag-row'));
    }

    function getVisibleRows() {
        return getRows().filter(function (row) {
            return row.style.display !== 'none';
        });
    }

    function rowsForCurrentScope() {
        return activeFilter === 'all' ? getRows() : getVisibleRows();
    }

    function currentOrderSignature() {
        return rowsForCurrentScope().map(function (row) {
            return row.dataset.id || '';
        }).join('|');
    }

    function createDragPlaceholder(row) {
        var placeholder = document.createElement('tr');
        var cell = document.createElement('td');
        placeholder.className = 'drag-placeholder-row';
        cell.colSpan = Math.max(row.children.length, 1);
        cell.style.height = Math.max(row.getBoundingClientRect().height, 52) + 'px';
        placeholder.appendChild(cell);
        return placeholder;
    }

    function setDragPreview(event, row) {
        if (!event.dataTransfer || typeof event.dataTransfer.setDragImage !== 'function') {
            return;
        }

        var previewTable = document.createElement('table');
        var previewBody = document.createElement('tbody');
        var previewRow = row.cloneNode(true);
        var rect = row.getBoundingClientRect();
        previewTable.className = 'inc-drag-preview';
        previewTable.style.width = rect.width + 'px';
        previewRow.classList.remove('dragging', 'drag-hidden', 'drop-target');
        previewBody.appendChild(previewRow);
        previewTable.appendChild(previewBody);
        document.body.appendChild(previewTable);
        dragPreview = previewTable;
        event.dataTransfer.setDragImage(previewTable, Math.min(32, rect.width / 2), Math.min(24, rect.height / 2));
        requestAnimationFrame(function () {
            if (dragPreview) {
                dragPreview.remove();
                dragPreview = null;
            }
        });
    }

    function createPointerPreview(row) {
        var previewTable = document.createElement('table');
        var previewBody = document.createElement('tbody');
        var previewRow = row.cloneNode(true);
        var rect = row.getBoundingClientRect();
        previewTable.className = 'inc-drag-preview inc-pointer-drag-preview';
        previewTable.style.width = rect.width + 'px';
        previewRow.classList.remove('dragging', 'drag-hidden', 'drop-target');
        previewBody.appendChild(previewRow);
        previewTable.appendChild(previewBody);
        document.body.appendChild(previewTable);
        dragPreview = previewTable;
    }

    function movePointerPreview(event) {
        if (!dragPreview) return;
        dragPreview.style.transform = 'translate3d(' + (event.clientX - dragOffsetX) + 'px,' + (event.clientY - dragOffsetY) + 'px,0)';
    }

    function movePlaceholderFromPoint(clientX, clientY) {
        if (!draggedRow || !dragPlaceholder) return;
        var targetRow = document.elementFromPoint(clientX, clientY)?.closest('tr.drag-row');
        if (!targetRow || !targetRow.classList.contains('is-reorderable') || targetRow === draggedRow) return;

        var targetRect = targetRow.getBoundingClientRect();
        var after = (clientY - targetRect.top) > (targetRect.height / 2);
        body.querySelectorAll('.drop-target').forEach(function (row) {
            row.classList.remove('drop-target');
        });
        targetRow.classList.add('drop-target');

        if (after) {
            targetRow.after(dragPlaceholder);
        } else {
            targetRow.before(dragPlaceholder);
        }
    }

    function onPointerMove(event) {
        if (activePointerId !== null && event.pointerId !== activePointerId) return;
        event.preventDefault();
        movePointerPreview(event);
        movePlaceholderFromPoint(event.clientX, event.clientY);
    }

    function onPointerUp(event) {
        if (activePointerId !== null && event.pointerId !== activePointerId) return;
        document.removeEventListener('pointermove', onPointerMove);
        document.removeEventListener('pointerup', onPointerUp);
        document.removeEventListener('pointercancel', onPointerCancel);
        clearDragState(true);
    }

    function onPointerCancel(event) {
        if (activePointerId !== null && event.pointerId !== activePointerId) return;
        document.removeEventListener('pointermove', onPointerMove);
        document.removeEventListener('pointerup', onPointerUp);
        document.removeEventListener('pointercancel', onPointerCancel);
        clearDragState(false);
    }

    function clearDragState(commitDrop) {
        body.querySelectorAll('.drop-target').forEach(function (row) {
            row.classList.remove('drop-target');
        });

        if (draggedRow) {
            draggedRow.classList.remove('dragging', 'drag-hidden');
            if (dragPlaceholder && dragPlaceholder.parentNode) {
                if (commitDrop) {
                    dragPlaceholder.parentNode.insertBefore(draggedRow, dragPlaceholder);
                }
                dragPlaceholder.remove();
            }
        } else if (dragPlaceholder) {
            dragPlaceholder.remove();
        }

        if (dragPreview) {
            dragPreview.remove();
        }

        draggedRow = null;
        dragPlaceholder = null;
        dragPreview = null;
        activePointerId = null;
    }

    function startPointerDrag(event, row) {
        if (!isReorderMode || !row || !row.classList.contains('is-reorderable')) return;
        if (event.button !== undefined && event.button !== 0) return;

        var rect = row.getBoundingClientRect();
        activePointerId = event.pointerId;
        draggedRow = row;
        dragOffsetX = event.clientX - rect.left;
        dragOffsetY = event.clientY - rect.top;
        dragPlaceholder = createDragPlaceholder(row);
        row.after(dragPlaceholder);
        createPointerPreview(row);
        movePointerPreview(event);
        row.classList.add('dragging', 'drag-hidden');
        event.preventDefault();

        document.addEventListener('pointermove', onPointerMove, { passive: false });
        document.addEventListener('pointerup', onPointerUp);
        document.addEventListener('pointercancel', onPointerCancel);
    }

    function isInteractiveTarget(target) {
        return Boolean(target.closest('a, button, input, select, textarea, label, .act-btn'));
    }

    function showAdminToast(type, message) {
        var styles = {
            success: { bg: '#f0fdf4', fg: '#166534', border: '#bbf7d0' },
            error: { bg: '#fef2f2', fg: '#991b1b', border: '#fecaca' },
            warning: { bg: '#fffbeb', fg: '#92400e', border: '#fde68a' },
            info: { bg: '#f0f7ff', fg: '#1e40af', border: '#bfdbfe' }
        };
        var tone = styles[type] || styles.info;
        var toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;top:1.1rem;right:1.1rem;z-index:9999;background:' + tone.bg + ';border:1px solid ' + tone.border + ';padding:.55rem .9rem;border-radius:.3rem;font-size:.78rem;font-family:\'DM Sans\',sans-serif;color:' + tone.fg + ';max-width:340px;opacity:0;transform:translateY(-8px);transition:opacity .25s,transform .25s';
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
        }, 3500);
    }

    function setReorderMode(enabled) {
        isReorderMode = enabled;
        reorderSnapshot = enabled ? currentOrderSignature() : '';
        table.classList.toggle('is-reorder-mode', enabled);
        if (reorderBtn) {
            reorderBtn.textContent = enabled ? 'Save order' : 'Re-order';
            reorderBtn.classList.toggle('btn-p', enabled);
            reorderBtn.classList.toggle('btn-o', !enabled);
        }
        filterButtons.forEach(function (button) {
            button.disabled = enabled && (button.dataset.filter || 'all') !== activeFilter;
        });

        getRows().forEach(function (row) {
            var canDrag = enabled && row.style.display !== 'none';
            row.draggable = false;
            row.classList.toggle('is-reorderable', canDrag);
        });
    }

    function updateReorderAvailability() {
        if (!reorderBtn) return;
        var canReorder = getVisibleRows().length > 1;
        reorderBtn.disabled = !canReorder;
        reorderBtn.title = canReorder ? '' : 'At least two visible incubatees are needed to reorder.';
        if (!canReorder && isReorderMode) {
            setReorderMode(false);
        } else {
            setReorderMode(isReorderMode);
        }
    }

    function saveOrder() {
        if (!reorderUrl) return Promise.resolve(false);

        var formData = new FormData();
        var rowsToSave = rowsForCurrentScope();
        rowsToSave.forEach(function (row) {
            formData.append('order[]', row.dataset.id);
        });
        formData.append('cohort', activeFilter);
        formData.append(csrfName, csrfValue);

        return fetch(reorderUrl, {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: formData
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.ok) {
                showAdminToast('success', 'Order saved.');
                return true;
            }
            showAdminToast('error', data.error || 'Unable to save order.');
            return false;
        })
        .catch(function () {
            showAdminToast('error', 'Network error while saving order.');
            return false;
        });
    }

    function normalizeCohort(value) {
        return String(value || '').trim().toLowerCase();
    }

    function applyFilter(filterName) {
        activeFilter = filterName || 'all';
        var wanted = normalizeCohort(filterName);
        var visibleCount = 0;
        getRows().forEach(function (row) {
            var rowCohort = normalizeCohort(row.dataset.cohort);
            var matches = wanted === 'all' || rowCohort === wanted;
            row.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        var emptyState = root.querySelector('#cohortEmptyState');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? '' : 'none';
        }

        updateReorderAvailability();
    }

    filterButtons.forEach(function (button) {
        on(button, 'click', function () {
            if (isReorderMode) {
                return;
            }
            filterButtons.forEach(function (btn) {
                btn.classList.remove('active');
            });
            button.classList.add('active');
            applyFilter(button.dataset.filter || 'all');
        });
    });

    if (reorderBtn) {
        on(reorderBtn, 'click', function () {
            if (reorderBtn.disabled) return;

            if (!isReorderMode) {
                setReorderMode(true);
                return;
            }

            if (currentOrderSignature() === reorderSnapshot) {
                showAdminToast('info', 'No order changes to save.');
                setReorderMode(false);
                updateReorderAvailability();
                return;
            }

            reorderBtn.disabled = true;
            reorderBtn.textContent = 'Saving...';
            saveOrder().then(function (saved) {
                if (saved) {
                    setReorderMode(false);
                } else {
                    setReorderMode(true);
                }
                updateReorderAvailability();
            });
        });
    }

    on(body, 'pointerdown', function (event) {
        var row = event.target.closest('tr.drag-row');
        if (!row) return;
        if (!event.target.closest('.drag-handle') && isInteractiveTarget(event.target)) return;
        startPointerDrag(event, row);
    });

    on(body, 'dragstart', function (event) {
        var row = event.target.closest('tr.drag-row');
        if (!isReorderMode || !row || !row.classList.contains('is-reorderable')) {
            event.preventDefault();
            return;
        }
        draggedRow = row;
        dragPlaceholder = createDragPlaceholder(row);
        row.after(dragPlaceholder);
        row.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', row.dataset.id || '');
        setDragPreview(event, row);
        requestAnimationFrame(function () {
            if (draggedRow === row) {
                row.classList.add('drag-hidden');
            }
        });
    });

    on(body, 'dragend', function () {
        clearDragState(false);
    });

    on(body, 'dragover', function (event) {
        if (!isReorderMode || !draggedRow || !dragPlaceholder) return;
        event.preventDefault();
        var targetRow = event.target.closest('tr.drag-row');
        if (!targetRow || !targetRow.classList.contains('is-reorderable') || targetRow === draggedRow) return;

        var targetRect = targetRow.getBoundingClientRect();
        var after = (event.clientY - targetRect.top) > (targetRect.height / 2);

        body.querySelectorAll('.drop-target').forEach(function (row) {
            row.classList.remove('drop-target');
        });
        targetRow.classList.add('drop-target');

        if (after) {
            targetRow.after(dragPlaceholder);
        } else {
            targetRow.before(dragPlaceholder);
        }
    });

    on(body, 'drop', function (event) {
        if (!isReorderMode || !draggedRow) return;
        event.preventDefault();
        clearDragState(true);
    });

    applyFilter('all');

    return function () {
        closeCohortModal();
        if (isReorderMode) {
            setReorderMode(false);
        }
        clearDragState(false);
        document.removeEventListener('pointermove', onPointerMove);
        document.removeEventListener('pointerup', onPointerUp);
        document.removeEventListener('pointercancel', onPointerCancel);
        document.body.style.overflow = '';
    };
}

if (window.AdminShell && typeof window.AdminShell.register === 'function') {
    window.AdminShell.register('incubatees', { init: init });
} else {
    document.addEventListener('DOMContentLoaded', function () { init(document); });
}
})();
