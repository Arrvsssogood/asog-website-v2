<link rel="stylesheet" href="<?= base_url('assets/css/adminAdmins.css') ?>">

<?php
$currentSort   = $sort      ?? 'fullName';
$currentDir    = $direction ?? 'ASC';
$currentSearch = $search    ?? '';
$currentStatus = $status    ?? 'all';
$currentRole   = $role      ?? 'all';

function sortUrl(string $col, string $currentSort, string $currentDir, string $search, string $status, string $role): string {
    $dir = ($currentSort === $col && strtolower($currentDir) === 'asc') ? 'desc' : 'asc';
    return site_url('admin/accounts') . '?' . http_build_query([
        'search'    => $search,
        'status'    => $status,
        'role'      => $role,
        'sort'      => $col,
        'direction' => $dir,
    ]);
}

function sortClass(string $col, string $currentSort, string $currentDir): string {
    if ($currentSort !== $col) return '';
    return 'sorted-' . strtolower($currentDir);
}
?>

<div class="accounts-admin-toolbar">
    <div>
        <span class="accounts-admin-count"><?= $total ?> account<?= $total !== 1 ? 's' : '' ?></span>
        <p>Manage admin access, roles, Google links, and account status.</p>
    </div>
    <div class="toolbar-actions">
        <a href="<?= site_url('admin/accounts/create') ?>" class="btn btn-p">New Account</a>
    </div>
</div>

<!-- Stats Widgets -->
<div class="grid-stats">
    <div class="stat">
        <div class="n" id="statActive" style="color:#10b981"><?= $counts['active'] ?></div>
        <div class="t">Active</div>
    </div>
    <div class="stat">
        <div class="n" id="statInactive" style="color:#ef4444"><?= $counts['inactive'] ?></div>
        <div class="t">Inactive</div>
    </div>
    <div class="stat">
        <div class="n" id="statSuperadmin" style="color:#03558C"><?= $counts['superadmin'] ?></div>
        <div class="t">Super Admins</div>
    </div>
    <div class="stat">
        <div class="n" id="statAdmin" style="color:#f59e0b"><?= $counts['admin'] ?></div>
        <div class="t">Admins</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="app-filter-bar">
    <div class="filter-slot">
        <form method="GET" action="<?= site_url('admin/accounts') ?>" class="app-filter-form" id="filterForm">
            <div class="app-search-wrap">
                <svg class="app-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" id="accountSearchInput" class="app-input-search"
                       placeholder="Search by name, email, google email…"
                       value="<?= esc($currentSearch) ?>">
            </div>
            <select name="status" class="app-select-filter" id="statusFilterSelect">
                <option value="all"      <?= $currentStatus === 'all'      ? 'selected' : '' ?>>All Statuses</option>
                <option value="active"   <?= $currentStatus === 'active'   ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $currentStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <select name="role" class="app-select-filter" id="roleFilterSelect">
                <option value="all"        <?= $currentRole === 'all'        ? 'selected' : '' ?>>All Roles</option>
                <option value="superadmin" <?= $currentRole === 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                <option value="admin"      <?= $currentRole === 'admin'      ? 'selected' : '' ?>>Admin</option>
            </select>
            <input type="hidden" name="sort"      value="<?= esc($currentSort) ?>">
            <input type="hidden" name="direction" value="<?= esc($currentDir) ?>">
            <button type="submit" class="app-btn-search">Search</button>
            <?php if ($currentSearch !== '' || $currentStatus !== 'all' || $currentRole !== 'all'): ?>
                <a href="<?= site_url('admin/accounts') ?>" class="app-btn-clear">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Table Wrap -->
<div class="tbl-wrap">
    <table class="tbl">
        <thead>
            <tr>
                <th class="sortable <?= sortClass('fullName', $currentSort, $currentDir) ?>">
                    <a href="<?= sortUrl('fullName', $currentSort, $currentDir, $currentSearch, $currentStatus, $currentRole) ?>">
                        Full Name <span class="sort-icon"></span>
                    </a>
                </th>
                <th class="sortable <?= sortClass('email', $currentSort, $currentDir) ?>">
                    <a href="<?= sortUrl('email', $currentSort, $currentDir, $currentSearch, $currentStatus, $currentRole) ?>">
                        Email <span class="sort-icon"></span>
                    </a>
                </th>
                <th>Google Account</th>
                <th class="sortable <?= sortClass('role', $currentSort, $currentDir) ?>">
                    <a href="<?= sortUrl('role', $currentSort, $currentDir, $currentSearch, $currentStatus, $currentRole) ?>">
                        Role <span class="sort-icon"></span>
                    </a>
                </th>
                <th class="sortable <?= sortClass('isActive', $currentSort, $currentDir) ?>">
                    <a href="<?= sortUrl('isActive', $currentSort, $currentDir, $currentSearch, $currentStatus, $currentRole) ?>">
                        Status <span class="sort-icon"></span>
                    </a>
                </th>
                <th class="sortable <?= sortClass('lastLoginAt', $currentSort, $currentDir) ?>">
                    <a href="<?= sortUrl('lastLoginAt', $currentSort, $currentDir, $currentSearch, $currentStatus, $currentRole) ?>">
                        Last Login <span class="sort-icon"></span>
                    </a>
                </th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($admins)): ?>
                <tr>
                    <td colspan="7" class="empty-row-msg" style="text-align:center;padding:2.5rem 1rem;color:#94a3b8">
                        No accounts found matching the search/filters.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($admins as $admin): ?>
                <tr>
                    <td><strong><?= esc($admin['fullName']) ?></strong></td>
                    <td><?= esc($admin['email']) ?></td>
                    <td>
                        <?php if (!empty($admin['googleEmail'])): ?>
                            <span class="tag tag-linked">
                                <?= esc($admin['googleEmail']) ?>
                            </span>
                        <?php else: ?>
                            <span class="tag tag-unlinked">Not linked</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="tag tag-role"><?= ucfirst(esc($admin['role'])) ?></span></td>
                    <td>
                        <?php if ($admin['isActive']): ?>
                            <span class="tag tag-active">Active</span>
                        <?php else: ?>
                            <span class="tag tag-inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.72rem;color:#94a3b8;white-space:nowrap">
                        <?php if ($admin['lastLoginAt']): ?>
                            <?= date('M d, Y h:i A', strtotime($admin['lastLoginAt'])) ?>
                        <?php else: ?>
                            <em>Never</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="acts">
                            <a href="<?= site_url('admin/accounts/' . $admin['id'] . '/edit') ?>" title="Edit">
                                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zM19.5 7.125L16.862 4.487"/><path stroke-linecap="round" stroke-linejoin="round" d="M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                            </a>
                            <form action="<?= site_url('admin/accounts/' . $admin['id']) ?>" method="POST" onsubmit="return confirm('Delete this account?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="del" title="Delete">
                                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="tbl-pagination">
        <span class="pag-info">
            Showing <?= (($currentPage - 1) * $perPage) + 1 ?>–<?= min($currentPage * $perPage, $total) ?> of <?= $total ?>
        </span>
        <div class="pag-controls">
            <?php
            $baseUrl = site_url('admin/accounts') . '?' . http_build_query([
                'search'    => $currentSearch,
                'status'    => $currentStatus,
                'role'      => $currentRole,
                'sort'      => $currentSort,
                'direction' => $currentDir,
            ]) . '&page=';
            $prevDisabled = $currentPage <= 1;
            ?>
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

<div id="adminAdminsConfig" data-base-url="<?= site_url('admin/accounts') ?>"></div>
<script src="<?= base_url('assets/js/admin/admins/index.js') ?>" defer></script>
