(function () {
    'use strict';

    function init(root) {
        root = root || document;
        const shellOn = window.AdminShell && typeof window.AdminShell.on === 'function'
            ? (target, eventName, selectorOrHandler, handler, options) => window.AdminShell.on(root, target, eventName, selectorOrHandler, handler, options)
            : (target, eventName, selectorOrHandler, handler, options) => {
                const listener = typeof selectorOrHandler === 'function'
                    ? selectorOrHandler
                    : (event) => {
                        const matched = event.target && event.target.closest ? event.target.closest(selectorOrHandler) : null;
                        if (!matched || (target !== document && target !== window && !target.contains(matched))) return;
                        handler.call(matched, event, matched);
                    };
                target.addEventListener(eventName, listener, options || false);
                return () => target.removeEventListener(eventName, listener, options || false);
            };

    const modalRoot = root.querySelector('#orgModalRoot');
    if (!modalRoot) return;

    const modalCache = new Map();

    const reorderConfig = root.querySelector('#orgReorderConfig');
    const reorderUrl = reorderConfig?.dataset?.reorderUrl || '';
    const csrfName = reorderConfig?.dataset?.csrfTokenName || '';
    const csrfValue = reorderConfig?.dataset?.csrfTokenValue || '';
    const reorderBtn = root.querySelector('#orgReorderBtn');

    let draggedCard = null;
    let dragContainer = null;
    let dragPlaceholder = null;
    let dragPreview = null;
    let dragOffsetX = 0;
    let dragOffsetY = 0;
    let activePointerId = null;
    let isReorderMode = false;
    const changedContainers = new Set();

    const saveOrder = (container) => {
        if (!reorderUrl || !container) return Promise.resolve(false);

        const section = container.getAttribute('data-section') || '';
        const category = container.getAttribute('data-category') || '';

        const formData = new FormData();
        container.querySelectorAll('.org-drag-row.is-reorderable').forEach((row) => {
            formData.append('order[]', row.getAttribute('data-id'));
        });
        formData.append('section', section);
        formData.append('category', category);
        if (csrfName && csrfValue) {
            formData.append(csrfName, csrfValue);
        }

        return fetch(reorderUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        })
            .then((r) => r.json())
            .then((d) => {
                if (!d.ok) {
                    showOrgToast('error', d.error || 'Unable to save order.');
                    return false;
                }
                return true;
            })
            .catch(() => {
                showOrgToast('error', 'Network error while saving order.');
                return false;
            });
    };

    const showOrgToast = (type, message) => {
        const styles = {
            success: { bg: '#f0fdf4', fg: '#166534', border: '#bbf7d0' },
            error: { bg: '#fef2f2', fg: '#991b1b', border: '#fecaca' },
            info: { bg: '#f0f7ff', fg: '#1e40af', border: '#bfdbfe' },
        };
        const tone = styles[type] || styles.info;
        const toast = document.createElement('div');
        toast.className = 'org-admin-toast';
        toast.style.cssText = `background:${tone.bg};color:${tone.fg};border:1px solid ${tone.border}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-8px)';
            setTimeout(() => toast.remove(), 250);
        }, 3500);
    };

    const reorderContainers = () => Array.from(root.querySelectorAll('[data-org-reorder-list]'));

    const reorderableRows = (container) => Array.from(
        container.querySelectorAll('.org-drag-row[data-reorderable="1"]')
    );

    const createDragPlaceholder = (row) => {
        const placeholder = document.createElement(row.tagName.toLowerCase());
        placeholder.className = row.classList.contains('org-admin-member-card')
            ? 'org-drag-placeholder is-card'
            : 'org-drag-placeholder is-row';
        placeholder.style.minHeight = `${Math.max(row.getBoundingClientRect().height, 64)}px`;
        return placeholder;
    };

    const setDragPreview = (event, row) => {
        if (!event.dataTransfer || typeof event.dataTransfer.setDragImage !== 'function') {
            return;
        }

        const rect = row.getBoundingClientRect();
        const preview = row.cloneNode(true);
        preview.classList.remove('is-dragging', 'is-drag-hidden', 'org-drag-over');
        preview.classList.add('org-drag-preview');
        preview.style.width = `${rect.width}px`;
        document.body.appendChild(preview);
        dragPreview = preview;
        event.dataTransfer.setDragImage(preview, Math.min(36, rect.width / 2), Math.min(28, rect.height / 2));
        requestAnimationFrame(() => {
            dragPreview?.remove();
            dragPreview = null;
        });
    };

    const createPointerPreview = (row) => {
        const rect = row.getBoundingClientRect();
        const preview = row.cloneNode(true);
        preview.classList.remove('is-dragging', 'is-drag-hidden', 'org-drag-over');
        preview.classList.add('org-drag-preview', 'org-pointer-drag-preview');
        preview.style.width = `${rect.width}px`;
        document.body.appendChild(preview);
        dragPreview = preview;
    };

    const movePointerPreview = (event) => {
        if (!dragPreview) return;
        dragPreview.style.transform = `translate3d(${event.clientX - dragOffsetX}px, ${event.clientY - dragOffsetY}px, 0)`;
    };

    const movePlaceholderFromPoint = (clientX, clientY) => {
        if (!draggedCard || !dragContainer || !dragPlaceholder) return;

        const target = document.elementFromPoint(clientX, clientY)?.closest('.org-drag-row');
        if (!target || target === draggedCard) return;
        if (target.closest('[data-org-reorder-list]') !== dragContainer) return;
        if (!target.classList.contains('is-reorderable')) return;

        const targetRect = target.getBoundingClientRect();
        const isCardGrid = dragContainer.classList.contains('org-admin-card-grid');
        const after = isCardGrid
            ? (
                Math.abs(clientY - (targetRect.top + targetRect.height / 2)) < targetRect.height * 0.45
                    ? clientX > (targetRect.left + targetRect.width / 2)
                    : clientY > (targetRect.top + targetRect.height / 2)
            )
            : (clientY - targetRect.top) > (targetRect.height / 2);

        root.querySelectorAll('.org-drag-over').forEach((el) => el.classList.remove('org-drag-over'));
        target.classList.add('org-drag-over');

        if (after) {
            target.after(dragPlaceholder);
        } else {
            target.before(dragPlaceholder);
        }
    };

    const onPointerMove = (event) => {
        if (activePointerId !== null && event.pointerId !== activePointerId) return;
        event.preventDefault();
        movePointerPreview(event);
        movePlaceholderFromPoint(event.clientX, event.clientY);
    };

    const finishPointerDrag = (event, commitDrop) => {
        if (activePointerId !== null && event.pointerId !== activePointerId) return;
        const changedContainer = dragContainer;
        document.removeEventListener('pointermove', onPointerMove);
        document.removeEventListener('pointerup', onPointerUp);
        document.removeEventListener('pointercancel', onPointerCancel);
        clearDragState(commitDrop);
        if (commitDrop && changedContainer) {
            changedContainers.add(changedContainer);
        }
    };

    const onPointerUp = (event) => {
        finishPointerDrag(event, true);
    };

    const onPointerCancel = (event) => {
        finishPointerDrag(event, false);
    };

    const clearDragState = (commitDrop = false) => {
        root.querySelectorAll('.org-drag-over').forEach((el) => el.classList.remove('org-drag-over'));

        if (draggedCard) {
            draggedCard.classList.remove('is-dragging', 'is-drag-hidden');
            if (dragPlaceholder?.parentNode) {
                if (commitDrop) {
                    dragPlaceholder.parentNode.insertBefore(draggedCard, dragPlaceholder);
                }
                dragPlaceholder.remove();
            }
        } else {
            dragPlaceholder?.remove();
        }

        dragPreview?.remove();

        draggedCard = null;
        dragContainer = null;
        dragPlaceholder = null;
        dragPreview = null;
        activePointerId = null;
    };

    const startPointerDrag = (event, row) => {
        if (!isReorderMode || !row || !row.classList.contains('is-reorderable')) return;
        if (event.button !== undefined && event.button !== 0) return;

        const rect = row.getBoundingClientRect();
        activePointerId = event.pointerId;
        draggedCard = row;
        dragContainer = row.closest('[data-org-reorder-list]');
        dragOffsetX = event.clientX - rect.left;
        dragOffsetY = event.clientY - rect.top;
        dragPlaceholder = createDragPlaceholder(row);
        row.after(dragPlaceholder);
        createPointerPreview(row);
        movePointerPreview(event);
        row.classList.add('is-dragging', 'is-drag-hidden');
        event.preventDefault();

        document.addEventListener('pointermove', onPointerMove, { passive: false });
        document.addEventListener('pointerup', onPointerUp);
        document.addEventListener('pointercancel', onPointerCancel);
    };

    const isInteractiveTarget = (target) => Boolean(
        target.closest('a, button, input, select, textarea, label, .act-btn, .acts')
    );

    const setReorderMode = (enabled) => {
        isReorderMode = enabled;
        document.body.classList.toggle('org-reorder-mode', enabled);

        if (reorderBtn) {
            reorderBtn.textContent = enabled ? 'Save order' : 'Re-order';
            reorderBtn.classList.toggle('btn-p', enabled);
            reorderBtn.classList.toggle('btn-o', !enabled);
        }

        reorderContainers().forEach((container) => {
            const rows = reorderableRows(container);
            const canReorder = enabled && rows.length > 1;
            container.classList.toggle('is-org-reorder-active', canReorder);
            rows.forEach((row) => {
                row.draggable = false;
                row.classList.toggle('is-reorderable', canReorder);
            });
        });
    };

    const updateReorderAvailability = () => {
        if (!reorderBtn) return;
        const canReorder = reorderContainers().some((container) => reorderableRows(container).length > 1);
        reorderBtn.disabled = !canReorder;
        reorderBtn.title = canReorder ? '' : 'At least two reorderable members are needed.';
        if (!canReorder && isReorderMode) {
            setReorderMode(false);
        } else {
            setReorderMode(isReorderMode);
        }
    };

    const saveChangedOrders = async () => {
        const containers = Array.from(changedContainers).filter((container) => document.body.contains(container));
        if (containers.length === 0) {
            showOrgToast('info', 'No order changes to save.');
            return true;
        }

        const results = await Promise.all(containers.map((container) => saveOrder(container)));
        const allSaved = results.every(Boolean);
        if (allSaved) {
            changedContainers.clear();
            showOrgToast('success', 'Order saved.');
        }
        return allSaved;
    };

    const memberIdFromUrl = (url) => {
        const match = url.match(/\/modal\/(\d+)(?:\?|$)/);
        return match ? match[1] : null;
    };

    const appendSince = (url, since) => {
        if (!since) return url;
        const joiner = url.includes('?') ? '&' : '?';
        return `${url}${joiner}since=${encodeURIComponent(since)}`;
    };

    const closeModal = () => {
        modalRoot.innerHTML = '';
        document.body.classList.remove('org-modal-open');
    };

    const cacheModal = (memberId, updatedAt, html) => {
        if (!memberId || !html) return;
        modalCache.set(memberId, { updatedAt: updatedAt || '', html });
    };

    const updateSectionCounts = (sectionCounts, totalCount) => {
        if (!sectionCounts) return;
        root.querySelectorAll('.org-admin-tab').forEach((tab) => {
            const href = tab.getAttribute('href') || '';
            const match = href.match(/section=([^&]+)/);
            if (!match) return;
            const badge = tab.querySelector('span');
            if (badge && Object.prototype.hasOwnProperty.call(sectionCounts, match[1])) {
                badge.textContent = String(sectionCounts[match[1]]);
            }
        });
        const total = root.querySelector('.org-admin-count');
        if (total && typeof totalCount === 'number') {
            total.textContent = `${totalCount} members`;
        }
    };

    const patchRows = (rows) => {
        (rows || []).forEach((patch) => {
            const row = root.querySelector(`#org-member-row-${patch.id}`);
            if (row && patch.rowHtml) {
                row.outerHTML = patch.rowHtml;
            }
        });
    };

    const insertRow = (listSelector, rowHtml, section = '', category = '') => {
        const container = root.querySelector(listSelector);
        if (!container || !rowHtml) return;

        const empty = container.querySelector('.org-admin-empty');
        if (empty) {
            const listClass = section === 'mentor' ? 'org-admin-list' : 'org-admin-list org-admin-card-grid';
            empty.outerHTML = `<div class="${listClass}" data-org-reorder-list data-section="${escapeAttr(section)}" data-category="${escapeAttr(category)}">${rowHtml}</div>`;
            return;
        }

        const list = container.querySelector('.org-admin-list');
        if (list) {
            list.insertAdjacentHTML('beforeend', rowHtml);
        }
    };

    const applyListEmpty = (listEmpty) => {
        if (!listEmpty?.selector || !listEmpty.html) return;
        const container = root.querySelector(listEmpty.selector);
        if (!container) return;
        const list = container.querySelector('.org-admin-list');
        if (list) {
            list.outerHTML = listEmpty.html;
        }
    };

    const escapeAttr = (value) => String(value).replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));

    const applySaveResult = (data) => {
        updateSectionCounts(data.sectionCounts, data.totalCount);

        if (data.action === 'update') {
            const row = root.querySelector(`#org-member-row-${data.memberId}`);
            if (row && data.rowHtml) {
                row.outerHTML = data.rowHtml;
            }
        } else if (data.action === 'insert') {
            insertRow(data.listSelector, data.rowHtml, data.section || '', data.category || '');
        } else if (data.action === 'relocate') {
            root.querySelector(`#org-member-row-${data.memberId}`)?.remove();
            insertRow(data.listSelector, data.rowHtml, data.section || '', data.category || '');
            applyListEmpty(data.listEmpty);
        }

        patchRows(data.patchRows);

        if (data.memberId) {
            modalCache.delete(String(data.memberId));
        }

        updateReorderAvailability();
    };

    const attachModalHandlers = () => {
        const modal = modalRoot.querySelector('[data-org-modal]');
        if (!modal) {
            document.body.classList.remove('org-modal-open');
            return;
        }

        if (window.AdminCustomSelect && typeof window.AdminCustomSelect.init === 'function') {
            window.AdminCustomSelect.init(modal);
        }

        document.body.classList.add('org-modal-open');
        const sectionField = modal.querySelector('#orgSection');
        const roleFields = modal.querySelector('#orgRoleFields');
        const mentorField = modal.querySelector('#orgMentorCategoryField');
        const mentorSelect = modal.querySelector('#orgMentorCategory');
        const photoField = modal.querySelector('#orgPhotoField');
        const featuredField = modal.querySelector('#orgFeaturedField');

        const bindPhotoTile = () => {
            const input = modal.querySelector('.org-photo-input');
            const zone = modal.querySelector('.org-photo-upload-zone');
            if (!input || !zone) return;

            const renderPreview = (file) => {
                if (!file || !file.type || !file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = (event) => {
                    const preview = zone.querySelector('.org-photo-upload-preview');
                    if (!preview) return;
                    preview.innerHTML = '';
                    const img = document.createElement('img');
                    img.className = 'org-photo-preview';
                    img.alt = 'Selected photo preview';
                    img.src = event.target.result;
                    preview.appendChild(img);
                    zone.classList.add('has-preview');
                };
                reader.readAsDataURL(file);
            };

            zone.addEventListener('dragover', (event) => {
                event.preventDefault();
                zone.classList.add('is-dragover');
            });

            zone.addEventListener('dragleave', () => {
                zone.classList.remove('is-dragover');
            });

            zone.addEventListener('drop', (event) => {
                event.preventDefault();
                zone.classList.remove('is-dragover');
                const files = event.dataTransfer && event.dataTransfer.files;
                if (!files || !files.length) return;
                const transfer = new DataTransfer();
                transfer.items.add(files[0]);
                input.files = transfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });

            input.addEventListener('change', () => {
                renderPreview(input.files && input.files[0]);
            });
        };

        const syncFeaturedNote = () => {
            const checkbox = featuredField?.querySelector('input[type="checkbox"]');
            const note = featuredField?.querySelector('.org-featured-replace-note');
            if (!checkbox || !note) return;
            const wasFeatured = featuredField.getAttribute('data-original-featured') === '1';
            note.classList.toggle('is-hidden', wasFeatured || !checkbox.checked);
        };

        const syncFields = () => {
            const sectionValue = sectionField?.value || 'core_team';
            const isMentor = sectionValue === 'mentor';
            roleFields?.classList.toggle('is-hidden', isMentor);
            mentorField?.classList.toggle('is-hidden', !isMentor);
            if (mentorSelect) {
                mentorSelect.required = isMentor;
                mentorSelect.disabled = !isMentor;
            }
            photoField?.classList.toggle('is-hidden', isMentor);
            featuredField?.classList.toggle('is-hidden', isMentor);
            syncFeaturedNote();
        };
        if (sectionField && sectionField.tagName === 'SELECT') {
            sectionField.addEventListener('change', syncFields);
        }
        featuredField?.querySelector('input[type="checkbox"]')?.addEventListener('change', syncFeaturedNote);
        bindPhotoTile();
        syncFields();

        const form = modal.querySelector('form[data-modal-form]');
        if (!form) return;
        
        // Dirty check
        if (window.DirtyCheck) {
            const saveBtn = form.querySelector('.btn-p');
            if (saveBtn) {
                const tracker = window.DirtyCheck.watch(form, { buttons: [saveBtn] });
                requestAnimationFrame(function () {
                    setTimeout(function () { tracker.baseline(); }, 50);
                });
            }
        }
        
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    cache: 'no-store',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                const data = await response.json();
                if (!response.ok || data.ok === false) {
                    if (data.modalHtml) {
                        modalRoot.innerHTML = data.modalHtml;
                        attachModalHandlers();
                    } else if (data.message) {
                        showOrgToast('error', data.message);
                    }
                    return;
                }

                applySaveResult(data);
                closeModal();
                if (data.message) {
                    showOrgToast('success', data.message);
                }
            } catch (error) {
                console.error(error);
                showOrgToast('error', 'Something went wrong while saving.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    };

    const openModalFromHtml = (html, memberId, updatedAt) => {
        modalRoot.innerHTML = html;
        attachModalHandlers();
        cacheModal(memberId, updatedAt, html);
    };

    const openModal = async (trigger) => {
        const url = trigger.getAttribute('data-modal-url');
        if (!url) return;

        const memberId = memberIdFromUrl(url);
        const updatedAt = trigger.getAttribute('data-member-updated-at') || '';
        const cached = memberId ? modalCache.get(memberId) : null;

        if (cached && cached.updatedAt === updatedAt && cached.html) {
            openModalFromHtml(cached.html, memberId, updatedAt);
            try {
                const check = await fetch(appendSince(url, updatedAt), {
                    method: 'GET',
                    cache: 'no-store',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (check.status === 204) return;
                if (check.ok) {
                    const fresh = await check.text();
                    const freshUpdatedAt = check.headers.get('X-Member-Updated-At') || updatedAt;
                    if (modalRoot.querySelector('[data-org-modal]')) {
                        openModalFromHtml(fresh, memberId, freshUpdatedAt);
                    }
                }
            } catch (error) {
                console.error(error);
            }
            return;
        }

        try {
            let requestUrl = memberId ? appendSince(url, updatedAt) : url;
            let response = await fetch(requestUrl, {
                method: 'GET',
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (response.status === 204) {
                response = await fetch(url, {
                    method: 'GET',
                    cache: 'no-store',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
            }

            if (!response.ok) return;
            const html = await response.text();
            const freshUpdatedAt = response.headers.get('X-Member-Updated-At')
                || trigger.getAttribute('data-member-updated-at')
                || '';
            openModalFromHtml(html, memberId, freshUpdatedAt);
        } catch (error) {
            console.error(error);
            closeModal();
            showOrgToast('error', 'Could not load the form.');
        }
    };

    shellOn(document, 'click', (event) => {
        const trigger = event.target.closest('.js-org-modal-trigger');
        if (!trigger) return;
        event.preventDefault();
        openModal(trigger);
    });

    shellOn(document, 'click', (event) => {
        if (event.target.closest('[data-org-modal-close]')) {
            event.preventDefault();
            closeModal();
        }
    });

    shellOn(document, 'keydown', (event) => {
        if (event.key === 'Escape' && modalRoot.querySelector('[data-org-modal]')) {
            closeModal();
        }
    });

    if (reorderBtn) {
        shellOn(reorderBtn, 'click', async () => {
            if (reorderBtn.disabled) return;

            if (!isReorderMode) {
                setReorderMode(true);
                return;
            }

            reorderBtn.disabled = true;
            reorderBtn.textContent = 'Saving...';
            const saved = await saveChangedOrders();
            if (saved) {
                setReorderMode(false);
            } else {
                setReorderMode(true);
            }
            updateReorderAvailability();
        });
    }

    shellOn(document, 'pointerdown', (event) => {
        const row = event.target.closest('.org-drag-row');
        if (!row) return;
        if (!root.contains(row)) return;
        if (!event.target.closest('.org-drag-handle') && isInteractiveTarget(event.target)) return;
        startPointerDrag(event, row);
    });

    shellOn(document, 'dragstart', (event) => {
        const row = event.target.closest('.org-drag-row');
        if (row && !root.contains(row)) return;
        if (!isReorderMode || !row || !row.classList.contains('is-reorderable')) {
            event.preventDefault();
            return;
        }
        draggedCard = row;
        dragContainer = row.closest('[data-org-reorder-list]');
        dragPlaceholder = createDragPlaceholder(row);
        row.after(dragPlaceholder);
        row.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', row.dataset.id || '');
        setDragPreview(event, row);
        requestAnimationFrame(() => {
            if (draggedCard === row) {
                row.classList.add('is-drag-hidden');
            }
        });
    });

    shellOn(document, 'dragend', () => {
        clearDragState(false);
    });

    shellOn(document, 'dragover', (event) => {
        if (!draggedCard || !dragContainer || !dragPlaceholder) return;

        const target = event.target.closest('.org-drag-row');
        if (!target || target === draggedCard) return;
        if (target.closest('[data-org-reorder-list]') !== dragContainer) return;
        if (!target.classList.contains('is-reorderable')) return;

        event.preventDefault();

        const targetRect = target.getBoundingClientRect();
        const isCardGrid = dragContainer.classList.contains('org-admin-card-grid');
        const after = isCardGrid
            ? (
                Math.abs(event.clientY - (targetRect.top + targetRect.height / 2)) < targetRect.height * 0.45
                    ? event.clientX > (targetRect.left + targetRect.width / 2)
                    : event.clientY > (targetRect.top + targetRect.height / 2)
            )
            : (event.clientY - targetRect.top) > (targetRect.height / 2);

        root.querySelectorAll('.org-drag-over').forEach((el) => el.classList.remove('org-drag-over'));
        target.classList.add('org-drag-over');

        if (after) {
            target.after(dragPlaceholder);
        } else {
            target.before(dragPlaceholder);
        }
    });

    shellOn(document, 'drop', (event) => {
        if (!draggedCard || !dragContainer) return;
        event.preventDefault();
        const changedContainer = dragContainer;
        clearDragState(true);
        changedContainers.add(changedContainer);
    });

    updateReorderAvailability();


        return () => {
            closeModal();
            setReorderMode(false);
            clearDragState(false);
            document.removeEventListener('pointermove', onPointerMove);
            document.removeEventListener('pointerup', onPointerUp);
            document.removeEventListener('pointercancel', onPointerCancel);
            document.body.classList.remove('org-modal-open', 'org-reorder-mode');
        };
    }

    if (window.AdminShell && typeof window.AdminShell.register === 'function') {
        window.AdminShell.register('organization', { init });
    } else {
        document.addEventListener('DOMContentLoaded', () => init(document));
    }
})();

