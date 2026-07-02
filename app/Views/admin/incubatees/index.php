<link rel="stylesheet" href="<?= base_url('assets/css/adminIncubatees.css') ?>">

<div id="incubateesConfig"
    data-add-url="<?= site_url('admin/cohorts/add') ?>"
    data-delete-base-url="<?= site_url('admin/cohorts/') ?>"
    data-reorder-url="<?= site_url('admin/incubatees/reorder') ?>"
    data-csrf-token-name="<?= csrf_token() ?>"
    data-csrf-token-value="<?= csrf_hash() ?>"></div>

<!-- ─── Cohort Manager Modal ─── -->
<div class="cm-overlay" id="cmOverlay">
    <div class="cm-modal">
        <div class="cm-modal-head">
            <h3>Manage Cohorts</h3>
            <button type="button" class="cm-modal-close" id="cmCloseBtn" title="Close">×</button>
        </div>
        <div class="cm-modal-body">
            <table class="cm-tbl" id="cmTable">
                <thead>
                    <tr>
                        <th>Cohort</th>
                        <th>Startups</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cmBody">
                    <?php foreach ($cohorts ?? [] as $c): ?>
                    <?php $cnt = (int) (($cohortStartupCounts[$c['name']] ?? 0)); ?>
                    <tr data-id="<?= $c['id'] ?>">
                        <td class="cm-name"><?= esc($c['name']) ?></td>
                        <td class="cm-cnt"><?= $cnt ?> startup<?= $cnt !== 1 ? 's' : '' ?></td>
                        <td>
                            <?php if ($cnt > 0): ?>
                                <span class="cm-status cm-active">Active</span>
                            <?php else: ?>
                                <span class="cm-status cm-empty">Coming Soon</span>
                            <?php endif; ?>
                        </td>
                        <td class="ta-right">
                            <button type="button" class="cm-del-btn" title="Delete" data-cohort-id="<?= $c['id'] ?>" data-cohort-name="<?= esc($c['name']) ?>" <?= $cnt > 0 ? 'disabled title="Remove incubatees first"' : '' ?>>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($cohorts)): ?>
            <div class="cm-empty-state" id="cmEmptyState">No cohorts yet. Add one below.</div>
            <?php endif; ?>
        </div>
        <div class="cm-modal-foot">
            <span class="cm-total" id="cmTotal"><?= count($cohorts ?? []) ?> cohort<?= count($cohorts ?? []) !== 1 ? 's' : '' ?></span>
            <button type="button" class="cm-add-btn" id="cmAddBtn">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Add Cohort
            </button>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/admin/incubatees/index.js') ?>"></script>

<div class="inc-admin-toolbar">
    <div>
        <span class="inc-admin-count"><?= count($incubatees ?? []) ?> incubatees</span>
        <p>Manage startups and MSMEs shown on the public Incubatees page.</p>
    </div>
    <div class="inc-admin-toolbar-actions">
        <div class="toolbar-actions">
            <button type="button" class="cm-manage-btn" id="cmManageBtn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Add Cohort
            </button>
            <a href="<?= site_url('admin/incubatees/create') ?>" class="btn btn-p">New incubatee</a>
        </div>
    </div>
</div>

<div class="filter-bar">
    <div class="filter-btns" id="cohortFilterBtns">
        <button type="button" class="filter-btn active" data-filter="all">All cohorts</button>
        <?php foreach (($cohorts ?? []) as $cohort): ?>
            <button type="button" class="filter-btn" data-filter="<?= esc($cohort['name']) ?>">
                <?= esc($cohort['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-o reorder-mode-btn" id="incReorderBtn">Re-order</button>
</div>

<table class="inc-tbl" id="incubateeTable">
    <thead>
        <tr>
            <th class="drag-col"></th>
            <th>Logo</th>
            <th>Company</th>
            <th>Cohort</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($incubatees)): ?>
        <tr>
            <td colspan="6" class="empty-row" style="padding:2.5rem 1rem;text-align:center;color:#94a3b8;font-size:.82rem">
                No incubatees yet.
                <a href="<?= site_url('admin/incubatees/create') ?>">Add one.</a>
            </td>
        </tr>
    <?php else: ?>
        <?php foreach ($incubatees as $inc): ?>
            <tr class="drag-row" data-id="<?= (int) $inc['id'] ?>" data-cohort="<?= esc((string) ($inc['cohort'] ?? '')) ?>">
                <td class="drag-cell">
                    <span class="drag-handle" title="Drag to reorder" aria-label="Drag to reorder">⋮⋮</span>
                </td>
                <td>
                    <?php if (! empty($inc['logoPath'])): ?>
                        <img src="<?= site_url($inc['logoPath']) ?>" alt="" class="tbl-logo"/>
                    <?php else: ?>
                        <span class="tbl-logo-empty"><?= strtoupper(mb_substr($inc['companyName'], 0, 2)) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="tbl-name"><?= esc($inc['companyName']) ?></span>
                </td>
                <td>
                    <?php if (! empty($inc['cohort'])): ?>
                        <span class="tag tag-cohort"><?= esc($inc['cohort']) ?></span>
                    <?php else: ?>
                        <span class="cohort-empty">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="tag <?= $inc['isPublished'] ? 'tag-live' : 'tag-draft' ?>"><?= $inc['isPublished'] ? 'Published' : 'Draft' ?></span>
                </td>
                <td>
                    <div class="acts">
                        <a href="<?= site_url('admin/incubatees/' . $inc['id'] . '/edit') ?>" class="act-btn edit" title="Edit" aria-label="Edit incubatee">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                            </svg>
                        </a>
                        <form action="<?= site_url('admin/incubatees/' . $inc['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Delete this incubatee?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="act-btn delete" title="Delete" aria-label="Delete incubatee">
                                <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr id="cohortEmptyState" style="display:none">
            <td colspan="6" class="empty-row" style="padding:2.5rem 1rem;text-align:center;color:#94a3b8;font-size:.82rem">
                No incubatees in this cohort yet.
            </td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
