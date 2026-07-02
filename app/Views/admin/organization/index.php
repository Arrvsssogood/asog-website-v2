<link rel="stylesheet" href="<?= base_url('assets/css/adminOrganization.css') ?>">

<div class="org-admin-toolbar">
    <div>
        <span class="org-admin-count"><?= array_sum(array_map('count', $membersBySection ?? [])) ?> members</span>
        <p>Manage team members shown on the public Organization page.</p>
    </div>
    <div class="org-admin-toolbar-actions">
        <a href="<?= site_url('organization') ?>" target="_blank" rel="noopener" class="btn btn-o">View page</a>
        <?php if (($activeSection ?? '') !== 'mentor'): ?>
            <?php $addUrl = site_url('admin/organization/modal?section=' . rawurlencode($activeSection ?? 'core_team')); ?>
            <a href="<?= $addUrl ?>" class="btn btn-p js-org-modal-trigger" data-modal-url="<?= $addUrl ?>">Add member</a>
        <?php endif; ?>
    </div>
</div>

<div class="org-admin-tab-row">
    <div class="org-admin-tabs">
        <?php foreach (($sectionLabels ?? []) as $sectionKey => $sectionLabel): ?>
            <a href="<?= site_url('admin/organization?section=' . $sectionKey) ?>"
               class="org-admin-tab <?= ($activeSection ?? '') === $sectionKey ? 'on' : '' ?>">
                <?= esc($sectionLabel) ?>
                <span><?= count($membersBySection[$sectionKey] ?? []) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-o org-reorder-mode-btn" id="orgReorderBtn">Re-order</button>
</div>

<?php if (($activeSection ?? '') === 'mentor'): ?>
    <div class="org-admin-mentor-groups">
        <?php foreach (($mentorGroups ?? []) as $group): ?>
            <?= view('admin/organization/_mentor_group', [
                'group' => $group,
                'activeMentorCategory' => $activeMentorCategory ?? '',
            ]) ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div id="org-section-list">
        <?= view('admin/organization/_section_list', [
            'members' => $membersBySection[$activeSection] ?? [],
            'activeSection' => $activeSection ?? '',
        ]) ?>
    </div>
<?php endif; ?>

<div id="orgModalRoot"></div>
<div id="orgReorderConfig"
    data-reorder-url="<?= site_url('admin/organization/reorder') ?>"
    data-csrf-token-name="<?= csrf_token() ?>"
    data-csrf-token-value="<?= csrf_hash() ?>"></div>

<script>
(() => {
    const modalRoot = document.getElementById('orgModalRoot');
    if (!modalRoot) return;

    const modalCache = new Map();

    const reorderConfig = document.getElementById('orgReorderConfig');
    const reorderUrl = reorderConfig?.dataset?.reorderUrl || '';
    const csrfName = reorderConfig?.dataset?.csrfTokenName || '';
    const csrfValue = reorderConfig?.dataset?.csrfTokenValue || '';
    const reorderBtn = document.getElementById('orgReorderBtn');

    let draggedCard = null;
    let dragContainer = null;
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

    const reorderContainers = () => Array.from(document.querySelectorAll('[data-org-reorder-list]'));

    const reorderableRows = (container) => Array.from(
        container.querySelectorAll('.org-drag-row[data-reorderable="1"]')
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
                row.draggable = canReorder;
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
        document.querySelectorAll('.org-admin-tab').forEach((tab) => {
            const href = tab.getAttribute('href') || '';
            const match = href.match(/section=([^&]+)/);
            if (!match) return;
            const badge = tab.querySelector('span');
            if (badge && Object.prototype.hasOwnProperty.call(sectionCounts, match[1])) {
                badge.textContent = String(sectionCounts[match[1]]);
            }
        });
        const total = document.querySelector('.org-admin-count');
        if (total && typeof totalCount === 'number') {
            total.textContent = `${totalCount} members`;
        }
    };

    const patchRows = (rows) => {
        (rows || []).forEach((patch) => {
            const row = document.getElementById(`org-member-row-${patch.id}`);
            if (row && patch.rowHtml) {
                row.outerHTML = patch.rowHtml;
            }
        });
    };

    const insertRow = (listSelector, rowHtml, section = '', category = '') => {
        const container = document.querySelector(listSelector);
        if (!container || !rowHtml) return;

        const empty = container.querySelector('.org-admin-empty');
        if (empty) {
            empty.outerHTML = `<div class="org-admin-list" data-org-reorder-list data-section="${escapeAttr(section)}" data-category="${escapeAttr(category)}">${rowHtml}</div>`;
            return;
        }

        const list = container.querySelector('.org-admin-list');
        if (list) {
            list.insertAdjacentHTML('beforeend', rowHtml);
        }
    };

    const applyListEmpty = (listEmpty) => {
        if (!listEmpty?.selector || !listEmpty.html) return;
        const container = document.querySelector(listEmpty.selector);
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
            const row = document.getElementById(`org-member-row-${data.memberId}`);
            if (row && data.rowHtml) {
                row.outerHTML = data.rowHtml;
            }
        } else if (data.action === 'insert') {
            insertRow(data.listSelector, data.rowHtml, data.section || '', data.category || '');
        } else if (data.action === 'relocate') {
            document.getElementById(`org-member-row-${data.memberId}`)?.remove();
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
        };
        if (sectionField && sectionField.tagName === 'SELECT') {
            sectionField.addEventListener('change', syncFields);
        }
        syncFields();

        const form = modal.querySelector('form[data-modal-form]');
        if (!form) return;

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

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.js-org-modal-trigger');
        if (!trigger) return;
        event.preventDefault();
        openModal(trigger);
    });

    document.addEventListener('click', (event) => {
        if (event.target.matches('[data-org-modal-close]')) {
            event.preventDefault();
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modalRoot.querySelector('[data-org-modal]')) {
            closeModal();
        }
    });

    if (reorderBtn) {
        reorderBtn.addEventListener('click', async () => {
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

    document.addEventListener('dragstart', (event) => {
        const row = event.target.closest('.org-drag-row');
        if (!isReorderMode || !row || !row.classList.contains('is-reorderable')) {
            event.preventDefault();
            return;
        }
        draggedCard = row;
        dragContainer = row.closest('[data-org-reorder-list]');
        row.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
    });

    document.addEventListener('dragend', () => {
        draggedCard?.classList.remove('is-dragging');
        draggedCard = null;
        dragContainer = null;
        document.querySelectorAll('.org-drag-over').forEach((el) => el.classList.remove('org-drag-over'));
    });

    document.addEventListener('dragover', (event) => {
        if (!draggedCard || !dragContainer) return;

        const target = event.target.closest('.org-drag-row');
        if (!target || target === draggedCard) return;
        if (target.closest('[data-org-reorder-list]') !== dragContainer) return;
        if (!target.classList.contains('is-reorderable')) return;

        event.preventDefault();

        const targetRect = target.getBoundingClientRect();
        const after = (event.clientY - targetRect.top) > (targetRect.height / 2);

        document.querySelectorAll('.org-drag-over').forEach((el) => el.classList.remove('org-drag-over'));
        target.classList.add('org-drag-over');

        if (after) {
            target.after(draggedCard);
        } else {
            target.before(draggedCard);
        }
    });

    document.addEventListener('drop', (event) => {
        if (!draggedCard || !dragContainer) return;
        event.preventDefault();
        document.querySelectorAll('.org-drag-over').forEach((el) => el.classList.remove('org-drag-over'));
        draggedCard.classList.remove('is-dragging');
        changedContainers.add(dragContainer);
        draggedCard = null;
        dragContainer = null;
    });

    updateReorderAvailability();
})();
</script>
