<link rel="stylesheet" href="<?= base_url('assets/css/adminApplications.css') ?>">

<?php
$currentSort   = $sort      ?? 'createdAt';
$currentDir    = $direction ?? 'DESC';
$currentSearch = $search    ?? '';
$currentStatus = $status    ?? 'active';

function sortUrl(string $col, string $currentSort, string $currentDir, string $search, string $status): string {
    $dir = ($currentSort === $col && strtolower($currentDir) === 'asc') ? 'desc' : 'asc';
    return site_url('admin/applications') . '?' . http_build_query([
        'search'    => $search,
        'status'    => $status,
        'sort'      => $col,
        'direction' => $dir,
    ]);
}

function sortClass(string $col, string $currentSort, string $currentDir): string {
    if ($currentSort !== $col) return '';
    return 'sorted-' . strtolower($currentDir);
}
?>

<?php
$duplicateEmailSetting = old('allowDuplicateEmails');
$allowDuplicateEmails = $duplicateEmailSetting !== null
    ? $duplicateEmailSetting === '1'
    : ! empty($allowDuplicateEmails);
?>

<!-- Stats -->
<div class="grid-stats">
    <div class="stat">
        <div class="n" id="statTotal"><?= $counts['total'] ?></div>
        <div class="t">Total Active</div>
    </div>
    <div class="stat">
        <div class="n" id="statPending"><?= $counts['pending'] ?></div>
        <div class="t">For Review</div>
    </div>
    <div class="stat">
        <div class="n" id="statForRevalidation"><?= $counts['forRevalidation'] ?? 0 ?></div>
        <div class="t">For Revalidation</div>
    </div>
    <div class="stat">
        <div class="n" id="statAccepted"><?= $counts['accepted'] ?></div>
        <div class="t">Accepted</div>
    </div>
    <div class="stat">
        <div class="n" id="statRejected"><?= $counts['rejected'] ?></div>
        <div class="t">Rejected</div>
    </div>
    <div class="stat">
        <div class="n" id="statArchived"><?= $counts['archived'] ?></div>
        <div class="t">Archived</div>
    </div>
</div>

<section class="app-settings-card">
    <div class="app-settings-head">
        <span>Submission Rule</span>
        <h2>Application settings</h2>
        <p>Control whether applicants can submit more than once using the same email address.</p>
    </div>

    <form method="POST" action="<?= site_url('admin/applications/settings') ?>" class="app-settings-form">
        <?= csrf_field() ?>
        <label class="app-rule">
            <input type="hidden" name="allowDuplicateEmails" value="0">
            <input type="checkbox" name="allowDuplicateEmails" value="1" <?= $allowDuplicateEmails ? 'checked' : '' ?>>
            <span>
                <strong>Allow duplicate applicant emails</strong>
                <small>
                    <?= $allowDuplicateEmails
                        ? 'Applicants can submit more than once with the same email address.'
                        : 'Applicants will see an email-specific error if that address was already used before.' ?>
                </small>
            </span>
        </label>

        <div class="app-settings-actions">
            <button type="submit" class="btn btn-p">Save application settings</button>
        </div>
    </form>
</section>

<!-- Combined filter + bulk bar -->
<div class="app-filter-bar">
    <!-- Checkbox + smart-select caret — always visible -->
    <div class="filter-chk-area">
        <div class="smart-chk-wrap" id="smartChkWrap">
            <input type="checkbox" id="selectAll">
            <button type="button" class="smart-chk-caret" id="smartCaretBtn" title="Selection options">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div class="smart-chk-menu" id="smartChkMenu">
                <button type="button" class="scm-item" data-smart="all">All</button>
                <button type="button" class="scm-item" data-smart="none">None</button>
                <div class="scm-sep"></div>
                <button type="button" class="scm-item" data-smart="pending">For Review</button>
                <button type="button" class="scm-item" data-smart="for_revalidation">For Revalidation</button>
                <button type="button" class="scm-item" data-smart="accepted">Accepted</button>
                <button type="button" class="scm-item" data-smart="rejected">Rejected</button>
                <button type="button" class="scm-item" data-smart="archived">Archived</button>
            </div>
        </div>
    </div>
    <div class="filter-divider"></div>
    <div class="filter-slot">
    <form method="GET" action="<?= site_url('admin/applications') ?>" class="app-filter-form" id="filterForm">
        <div class="app-search-wrap">
            <svg class="app-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="search" id="appSearchInput" class="app-input-search"
                   placeholder="Search by name, startup, email…"
                   value="<?= esc($currentSearch) ?>">
        </div>
        <select name="status" class="app-select-filter">
            <option value="active"   <?= $currentStatus === 'active'   ? 'selected' : '' ?>>Active</option>
            <option value="pending"  <?= $currentStatus === 'pending'  ? 'selected' : '' ?>>For Review</option>
            <option value="for_revalidation" <?= $currentStatus === 'for_revalidation' ? 'selected' : '' ?>>For Revalidation</option>
            <option value="accepted" <?= $currentStatus === 'accepted' ? 'selected' : '' ?>>Accepted</option>
            <option value="rejected" <?= $currentStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            <option value="archived" <?= $currentStatus === 'archived' ? 'selected' : '' ?>>Archived</option>
        </select>
        <input type="hidden" name="sort"      value="<?= esc($currentSort) ?>">
        <input type="hidden" name="direction" value="<?= esc($currentDir) ?>">
        <button type="submit" class="app-btn-search">Search</button>
        <?php if ($currentSearch !== '' || $currentStatus !== 'active'): ?>
            <a href="<?= site_url('admin/applications') ?>" class="app-btn-clear">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Bulk action buttons — overlaid when rows are selected -->
    <div class="bulk-actions-bar" id="bulkActionsBar">
        <span class="bulk-count-label"><span id="bulkCount">0</span> selected</span>
        <div class="bulk-bar-sep"></div>
        <button type="button" class="bulk-act-btn bulk-act-pending"   data-action="pending">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Mark as For Review
        </button>
        <button type="button" class="bulk-act-btn bulk-act-accept"    data-action="accepted">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            Accept
        </button>
        <button type="button" class="bulk-act-btn bulk-act-reject"    data-action="rejected">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Reject
        </button>
        <button type="button" class="bulk-act-btn bulk-act-archive"   data-action="archive">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            Archive
        </button>
        <button type="button" class="bulk-act-btn bulk-act-unarchive" data-action="unarchive">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
            </svg>
            Unarchive
        </button>
    </div>
    </div>
</div>

<!-- Table -->
<?php if (empty($applications)): ?>
<div class="tbl-wrap">
    <div class="empty">No applications found.</div>
</div>
<?php else: ?>
<div class="tbl-wrap">
    <table class="tbl">
        <thead>
            <tr>
                <th class="col-check"></th>
                <th class="sortable <?= sortClass('applicantName', $currentSort, $currentDir) ?>">
                    <a href="<?= sortUrl('applicantName', $currentSort, $currentDir, $currentSearch, $currentStatus) ?>">
                        Applicant <span class="sort-icon"></span>
                    </a>
                </th>
                <th class="sortable <?= sortClass('startupName', $currentSort, $currentDir) ?>">
                    <a href="<?= sortUrl('startupName', $currentSort, $currentDir, $currentSearch, $currentStatus) ?>">
                        Startup <span class="sort-icon"></span>
                    </a>
                </th>
                <th class="sortable <?= sortClass('applicantEmail', $currentSort, $currentDir) ?>">
                    <a href="<?= sortUrl('applicantEmail', $currentSort, $currentDir, $currentSearch, $currentStatus) ?>">
                        Email <span class="sort-icon"></span>
                    </a>
                </th>
                <th class="sortable <?= sortClass('createdAt', $currentSort, $currentDir) ?>">
                    <a href="<?= sortUrl('createdAt', $currentSort, $currentDir, $currentSearch, $currentStatus) ?>">
                        Date <span class="sort-icon"></span>
                    </a>
                </th>
                <th class="sortable <?= sortClass('applicationStatus', $currentSort, $currentDir) ?>">
                    <a href="<?= sortUrl('applicationStatus', $currentSort, $currentDir, $currentSearch, $currentStatus) ?>">
                        Status <span class="sort-icon"></span>
                    </a>
                </th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $statusLabels = ['pending' => 'For Review', 'for_revalidation' => 'For Revalidation', 'accepted' => 'Accepted', 'rejected' => 'Rejected'];
            foreach ($applications as $app):
            ?>
            <tr data-id="<?= esc($app['id']) ?>" data-archived="<?= (int) $app['isArchived'] ?>">
                <td class="col-check">
                    <input type="checkbox" class="row-select"
                           data-id="<?= esc($app['id']) ?>"
                           data-status="<?= esc($app['applicationStatus']) ?>"
                           data-is-archived="<?= (int) $app['isArchived'] ?>">
                </td>
                <td><strong><?= esc($app['applicantName']) ?></strong></td>
                <td><strong><?= esc($app['startupName']) ?></strong></td>
                <td class="mono"><?= esc($app['applicantEmail']) ?></td>
                <td class="mono"><?= date('M j, Y', strtotime($app['createdAt'])) ?></td>
                <td>
                    <span class="tag tag-<?= esc($app['applicationStatus']) ?>">
                        <?= esc($statusLabels[$app['applicationStatus']] ?? ucfirst($app['applicationStatus'])) ?>
                    </span>
                    <?php if ($app['isArchived']): ?>
                        <span class="tag tag-archived">Archived</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="row-acts">
                        <button class="btn-review" data-id="<?= esc($app['id']) ?>">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Review
                        </button>
                        <button class="btn-arch-row" data-id="<?= esc($app['id']) ?>"
                                title="<?= $app['isArchived'] ? 'Restore' : 'Archive' ?>">
                            <?php if ($app['isArchived']): ?>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                            <?php else: ?>
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                            <?php endif; ?>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$baseUrl = site_url('admin/applications') . '?' . http_build_query([
    'search'    => $currentSearch,
    'status'    => $currentStatus,
    'sort'      => $currentSort,
    'direction' => $currentDir,
]) . '&page=';
?>
<?php if ($totalPages > 1): ?>
<div class="tbl-pagination">
    <span class="pag-info">Showing <?= ($currentPage - 1) * $perPage + 1 ?>&ndash;<?= min($currentPage * $perPage, $total) ?> of <?= $total ?></span>
    <div class="pag-controls">
        <?php $prevDisabled = $currentPage <= 1; ?>
        <a class="pag-btn<?= $prevDisabled ? ' pag-disabled' : '' ?>"
           href="<?= $prevDisabled ? '#' : $baseUrl . ($currentPage - 1) ?>" aria-label="Previous">&larr;</a>
        <?php
        $range = 2;
        $pages = [];
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i === 1 || $i === $totalPages || ($i >= $currentPage - $range && $i <= $currentPage + $range)) {
                $pages[] = $i;
            }
        }
        $prev = null;
        foreach ($pages as $p):
            if ($prev !== null && $p - $prev > 1): ?>
                <span class="pag-ellipsis">&hellip;</span>
        <?php endif; ?>
        <a class="pag-btn<?= $p === $currentPage ? ' pag-active' : '' ?>" href="<?= $baseUrl . $p ?>"><?= $p ?></a>
        <?php $prev = $p; endforeach; ?>
        <?php $nextDisabled = $currentPage >= $totalPages; ?>
        <a class="pag-btn<?= $nextDisabled ? ' pag-disabled' : '' ?>"
           href="<?= $nextDisabled ? '#' : $baseUrl . ($currentPage + 1) ?>" aria-label="Next">&rarr;</a>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Review Modal -->
<div class="modal-bg" id="reviewModal">
    <div class="modal">
        <div class="modal-head">
            <h2 id="modalTitle">Application Review</h2>
            <button class="modal-close" id="modalClose">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="modal-scroll">
            <div class="modal-body" id="modalBody">
                <!-- Populated by JS -->
            </div>
        </div>
        <div class="modal-foot" id="modalFoot">
            <div class="modal-status-meta" id="statusRemarkWrap">
                <label class="modal-status-label" for="statusRemarkInput">Remark For Applicant</label>
                <textarea id="statusRemarkInput" class="modal-status-input" rows="3" maxlength="2000"
                    placeholder="Optional note to include in the status notification email."></textarea>
                <p class="modal-status-help">Required for For Revalidation. This will be included in the notification email sent for the selected status.</p>
            </div>
            <button class="btn-arch-modal" id="btnArchModal" style="display:none">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                Archive
            </button>
            <button class="btn-restore-modal" id="btnRestoreModal" style="display:none">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
                Restore
            </button>
            <button class="btn-reject" id="btnReject" style="display:none">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Reject
            </button>
            <button class="btn-accept" id="btnAccept" style="display:none">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Accept
            </button>
            <button class="btn-change" id="btnChange" style="display:none">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Change Status
            </button>
            <div id="statusChangeWrap" style="display:none">
                <div class="status-picker" id="statusPicker">
                    <button type="button" class="status-picker-btn" id="statusPickerBtn">
                        <span id="statusPickerLabel">Select status</span>
                        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="status-picker-menu" id="statusPickerMenu">
                        <button type="button" class="spm-item" data-value="pending">For Review</button>
                        <button type="button" class="spm-item" data-value="for_revalidation">For Revalidation</button>
                        <button type="button" class="spm-item" data-value="accepted">Accepted</button>
                        <button type="button" class="spm-item" data-value="rejected">Rejected</button>
                    </div>
                </div>
                <button class="btn-accept" id="btnSaveStatus">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Universal Confirm Dialog -->
<div class="confirm-bg" id="confirmDialog">
    <div class="confirm-box">
        <div class="confirm-body">
            <div class="confirm-icon" id="confirmIcon">
                <svg id="confirmSvg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"></svg>
            </div>
            <h3 id="confirmTitle"></h3>
            <p id="confirmMsg"></p>
        </div>
        <div class="confirm-actions">
            <button class="c-cancel" id="confirmCancel">Cancel</button>
            <button class="c-ok" id="confirmOk">Confirm</button>
        </div>
    </div>
</div>

<?= jsBaseUrl() ?>
<div class="toast" id="appToast"></div>
<script src="<?= site_url('assets/js/admin/applications/index.js') ?>"></script>
