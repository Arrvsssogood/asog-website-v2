document.addEventListener('DOMContentLoaded', function () {
    var configEl = document.getElementById('incubateesConfig');
    if (!configEl) {
        return;
    }

    var addUrl = configEl.dataset.addUrl || '';
    var deleteBaseUrl = configEl.dataset.deleteBaseUrl || '';
    var reorderUrl = configEl.dataset.reorderUrl || '';
    var csrfName = configEl.dataset.csrfTokenName || '';
    var csrfValue = configEl.dataset.csrfTokenValue || '';

    var cmOverlay = document.getElementById('cmOverlay');
    var cmManageBtn = document.getElementById('cmManageBtn');
    var cmCloseBtn = document.getElementById('cmCloseBtn');
    var cmAddBtn = document.getElementById('cmAddBtn');
    var cmBody = document.getElementById('cmBody');
    var cmTotal = document.getElementById('cmTotal');

    function getCohortCount() {
        return document.querySelectorAll('#cmBody tr[data-id]').length;
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
        cmManageBtn.addEventListener('click', openCohortModal);
    }

    if (cmCloseBtn) {
        cmCloseBtn.addEventListener('click', closeCohortModal);
    }

    if (cmOverlay) {
        cmOverlay.addEventListener('click', function (event) {
            if (event.target === cmOverlay) {
                closeCohortModal();
            }
        });
    }

    document.addEventListener('keydown', function (event) {
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

            var emptyState = document.getElementById('cmEmptyState');
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
        if (!confirm('Delete ' + name + '?\nThis cannot be undone.')) {
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
                var body = document.querySelector('.cm-modal-body');
                if (body && !document.getElementById('cmEmptyState')) {
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
    }

    if (cmAddBtn) {
        cmAddBtn.addEventListener('click', addCohort);
    }

    if (cmBody) {
        cmBody.addEventListener('click', function (event) {
            var btn = event.target.closest('.cm-del-btn');
            if (!btn || btn.disabled) return;

            var row = btn.closest('tr');
            var id = btn.dataset.cohortId;
            var name = btn.dataset.cohortName || 'this cohort';
            deleteCohort(id, name, row);
        });
    }

    var table = document.getElementById('incubateeTable');
    var filterButtons = document.querySelectorAll('#cohortFilterBtns .filter-btn');
    var reorderBtn = document.getElementById('incReorderBtn');

    if (!table) {
        return;
    }

    var body = table.querySelector('tbody');
    var draggedRow = null;
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
            row.draggable = canDrag;
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

        var emptyState = document.getElementById('cohortEmptyState');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? '' : 'none';
        }

        updateReorderAvailability();
    }

    filterButtons.forEach(function (button) {
        button.addEventListener('click', function () {
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
        reorderBtn.addEventListener('click', function () {
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

    body.addEventListener('dragstart', function (event) {
        var row = event.target.closest('tr.drag-row');
        if (!isReorderMode || !row || !row.classList.contains('is-reorderable')) {
            event.preventDefault();
            return;
        }
        draggedRow = row;
        row.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
    });

    body.addEventListener('dragend', function () {
        if (draggedRow) {
            draggedRow.classList.remove('dragging');
        }
        draggedRow = null;
        body.querySelectorAll('.drop-target').forEach(function (row) {
            row.classList.remove('drop-target');
        });
    });

    body.addEventListener('dragover', function (event) {
        if (!isReorderMode || !draggedRow) return;
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
            targetRow.after(draggedRow);
        } else {
            targetRow.before(draggedRow);
        }
    });

    body.addEventListener('drop', function (event) {
        if (!isReorderMode) return;
        event.preventDefault();
        body.querySelectorAll('.drop-target').forEach(function (row) {
            row.classList.remove('drop-target');
        });
        if (draggedRow) {
            draggedRow.classList.remove('dragging');
        }
        draggedRow = null;
    });

    applyFilter('all');
});
