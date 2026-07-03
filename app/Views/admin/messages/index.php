<link rel="stylesheet" href="<?= base_url('assets/css/adminMessages.css') ?>">

<?php
$currentSearch = $currentSearch ?? '';
$currentDate   = $currentDate   ?? 'all';
$currentPage   = $currentPage   ?? 1;
$totalPages    = $totalPages    ?? 1;
$total         = $total         ?? 0;
$perPage       = $perPage       ?? 10;
$activeView    = $activeView    ?? 'inbox';

$filterValue = $activeView === 'archived' ? 'archived' : 'inbox-' . $currentDate;

$baseUrl = site_url('admin/messages') . '?' . http_build_query([
    'view'   => $activeView,
    'search' => $currentSearch,
    'date'   => $currentDate,
]) . '&page=';
?>

<div class="grid-stats">
    <div class="stat">
        <div class="n"><?= $counts['total'] ?></div>
        <div class="t">Inbox</div>
    </div>
    <div class="stat">
        <div class="n" style="color:#03558C"><?= $counts['unread'] ?></div>
        <div class="t">Unread</div>
    </div>
    <div class="stat">
        <div class="n" style="color:#94a3b8"><?= $counts['archived'] ?></div>
        <div class="t">Archived</div>
    </div>
</div>


<div class="msg-filter-bar">
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
                <button type="button" class="scm-item" data-smart="read">Read</button>
                <button type="button" class="scm-item" data-smart="unread">Unread</button>
            </div>
        </div>
    </div>
    <div class="filter-divider"></div>
    <div class="filter-slot">
        <form method="GET" action="<?= site_url('admin/messages') ?>" class="app-filter-form" id="filterForm">
            <input type="hidden" id="viewInput" name="view" value="<?= esc($activeView) ?>">
            <input type="hidden" id="dateInput" name="date" value="<?= esc($currentDate) ?>">
            <div class="app-search-wrap">
                <svg class="app-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" id="msgSearchInput" class="app-input-search"
                    placeholder="Search by name, email, message…"
                    value="<?= esc($currentSearch) ?>">
            </div>
            <select id="filterSelect" class="app-select-filter">
                <optgroup label="Date">
                    <option value="inbox-all"   <?= $filterValue === 'inbox-all'   ? 'selected' : '' ?>>All</option>
                    <option value="inbox-today" <?= $filterValue === 'inbox-today'  ? 'selected' : '' ?>>Today</option>
                    <option value="inbox-week"  <?= $filterValue === 'inbox-week'   ? 'selected' : '' ?>>This Week</option>
                    <option value="inbox-month" <?= $filterValue === 'inbox-month'  ? 'selected' : '' ?>>This Month</option>
                </optgroup>
                <optgroup label="View">
                    <option value="archived"    <?= $filterValue === 'archived'     ? 'selected' : '' ?>>Archived</option>
                </optgroup>
            </select>
            <button type="submit" class="app-btn-search">Search</button>
            <?php if ($currentSearch !== '' || $currentDate !== 'all' || $activeView === 'archived'): ?>
                <a href="<?= site_url('admin/messages') ?>" class="app-btn-clear">Clear</a>
            <?php endif; ?>
        </form>
        <div class="bulk-actions-bar" id="bulkActionsBar">
            <span class="bulk-count-label"><span id="bulkCount">0</span> selected</span>
            <div class="bulk-bar-sep"></div>
            <?php if ($activeView === 'inbox'): ?>
            <button type="button" class="bulk-act-btn bulk-act-read" onclick="bulkDo('mark_read')">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Mark Read
            </button>
            <button type="button" class="bulk-act-btn bulk-act-unread" onclick="bulkDo('mark_unread')">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                Mark Unread
            </button>
            <button type="button" class="bulk-act-btn bulk-act-archive" onclick="bulkDo('archive')">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                Archive
            </button>
            <?php else: ?>
            <button type="button" class="bulk-act-btn bulk-act-unarchive" onclick="bulkDo('unarchive')">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                Move to Inbox
            </button>
            <?php endif; ?>
            <button type="button" class="bulk-act-btn bulk-act-delete" onclick="bulkDo('delete')">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Delete
            </button>
        </div>
    </div>
</div>

<div class="inbox-wrap" id="inbox">
    <div class="inbox-bar">
        <span class="bar-label"><?= $activeView === 'archived' ? 'Archived' : 'Inbox' ?></span>
        <span class="bar-count" id="barCount">
            <?php if (empty($messages)): ?>
                0 messages
            <?php elseif ($total > $perPage): ?>
                <?= (($currentPage - 1) * $perPage) + 1 ?>–<?= min($currentPage * $perPage, $total) ?> of <?= $total ?>
            <?php else: ?>
                <?= $total ?> message<?= $total !== 1 ? 's' : '' ?>
            <?php endif; ?>
        </span>
    </div>
    <?php if (empty($messages)): ?>
        <div class="empty">
            <?php if ($activeView === 'archived'): ?>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" style="width:36px;height:36px;color:#d4d2ce;margin-bottom:.6rem"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                <div><?= $currentSearch !== '' ? 'No archived messages match your search.' : 'No archived messages.' ?></div>
            <?php else: ?>
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" style="width:36px;height:36px;color:#d4d2ce;margin-bottom:.6rem"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <div><?= ($currentSearch !== '' || $currentDate !== 'all') ? 'No messages match your search.' : 'No messages yet. When visitors submit the contact form, their messages will appear here.' ?></div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($messages as $m): ?>
        <div class="msg-row <?= $m['isRead'] ? '' : 'unread' ?>"
            data-id="<?= $m['id'] ?>"
            data-date="<?= date('Y-m-d', strtotime($m['createdAt'])) ?>"
            data-read="<?= (int) $m['isRead'] ?>"
            onclick="openMsg(<?= $m['id'] ?>)">
            <label class="msg-chk-cell" onclick="event.stopPropagation()">
                <input type="checkbox" class="row-select" data-id="<?= $m['id'] ?>" onchange="onRowCheck(this, <?= $m['id'] ?>)">
            </label>
            <span class="dot <?= $m['isRead'] ? 'dot-read' : 'dot-unread' ?>"></span>
            <span class="row-name"><?= esc($m['name']) ?></span>
            <span class="row-preview"><?= esc(mb_substr($m['message'], 0, 120)) ?></span>
            <span class="row-date">
                <?php
                $msgTime = strtotime($m['createdAt']);
                if (date('Y-m-d', $msgTime) === date('Y-m-d')) {
                    echo date('g:i A', $msgTime);
                } else {
                    echo date('M j', $msgTime);
                }
                ?>
            </span>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
    <div class="tbl-pagination">
        <span class="pag-info">
            Showing <?= (($currentPage - 1) * $perPage) + 1 ?>–<?= min($currentPage * $perPage, $total) ?> of <?= $total ?>
        </span>
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
            <a class="pag-btn<?= $p === $currentPage ? ' pag-active' : '' ?>"
                href="<?= $baseUrl . $p ?>"><?= $p ?></a>
            <?php $prev = $p; endforeach; ?>
            <?php $nextDisabled = $currentPage >= $totalPages; ?>
            <a class="pag-btn<?= $nextDisabled ? ' pag-disabled' : '' ?>"
                href="<?= $nextDisabled ? '#' : $baseUrl . ($currentPage + 1) ?>" aria-label="Next">&rarr;</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="reader" id="reader">
    <div class="reader-bar">
        <button class="r-btn" onclick="backToInbox()">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back
        </button>
        <div class="bar-sep"></div>
        <button class="r-btn" id="btnToggleRead" onclick="toggleRead()">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span id="toggleLabel">Mark unread</span>
        </button>
        <button class="r-btn" id="btnArchive" onclick="archiveSingle()">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            <span id="archiveLabel">Archive</span>
        </button>
        <button class="r-btn danger" onclick="confirmDelete()">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Delete
        </button>
    </div>
    <div class="reader-head">
        <h1 class="rh-subject" id="rSubject">Message</h1>
    </div>
    <div class="sender-row">
        <div class="sender-avatar" id="rAvatar">?</div>
        <div class="sender-info">
            <div class="sender-name" id="rName">—</div>
            <div class="sender-email">to me &lt;<span id="rEmail">—</span>&gt;</div>
        </div>
        <div class="sender-date" id="rDate">—</div>
    </div>
    <div class="reader-body" id="rBody">—</div>
    <div class="reply-strip">
        <a class="reply-btn" id="rReply" href="#">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a5 5 0 015 5v4M3 10l6 6M3 10l6-6"/></svg>
            Reply
        </a>
    </div>
</div>

<div class="toast" id="toast"></div>
<div id="adminMessagesConfig" data-base-url="<?= site_url('admin/messages') ?>"></div>
<script src="<?= base_url('assets/js/admin/messages/index.js') ?>" defer></script>
