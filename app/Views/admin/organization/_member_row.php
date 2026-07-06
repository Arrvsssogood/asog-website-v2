<?php
/**
 * @var array $member
 * @var string $activeSection
 */
$id = (int) $member['id'];
$isMentor = ($activeSection ?? '') === 'mentor';
$isFeatured = ! $isMentor && ! empty($member['isFeatured']);
$isReorderable = ! $isFeatured;
$roleText = trim((string) ($member['rolePrimary'] ?? ''));
if (! empty($member['roleSecondary'])) {
    $roleText = trim($roleText . ' - ' . (string) $member['roleSecondary']);
}
$initials = '';
foreach (preg_split('/\s+/', trim((string) ($member['fullName'] ?? ''))) ?: [] as $part) {
    if ($part === '') {
        continue;
    }
    $initials .= strtoupper(mb_substr($part, 0, 1));
    if (mb_strlen($initials) >= 2) {
        break;
    }
}
$initials = $initials !== '' ? $initials : '-';
$editUrl = site_url('admin/organization/modal/' . $id);
?>
<article class="org-admin-item <?= ! $isMentor ? 'org-admin-member-card ' : '' ?>org-drag-row" id="org-member-row-<?= $id ?>" data-id="<?= $id ?>" data-reorderable="<?= $isReorderable ? '1' : '0' ?>">
    <?php if (! $isMentor): ?>
        <div class="org-admin-card-top">
            <div class="org-admin-card-media">
                <?php if ($isReorderable): ?>
                    <span class="org-drag-handle" title="Drag to reorder" aria-label="Drag to reorder">&#8942;&#8942;</span>
                <?php endif; ?>

                <?php if (! empty($member['photoPath'])): ?>
                    <?= responsiveStaticImg((string) ($member['photoPath'] ?? ''), 'team-org', '', 'org-admin-thumb') ?>
                <?php else: ?>
                    <span class="org-admin-thumb org-admin-thumb-empty"><?= esc($initials) ?></span>
                <?php endif; ?>
            </div>

            <div class="org-admin-item-actions">
                <div class="acts">
                    <a href="<?= $editUrl ?>" class="act-btn edit js-org-modal-trigger" data-modal-url="<?= $editUrl ?>" data-member-updated-at="<?= esc((string) ($member['updatedAt'] ?? '')) ?>" title="Edit" aria-label="Edit member">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                        </svg>
                    </a>
                    <form method="POST" action="<?= site_url('admin/organization/members/' . $id . '/delete') ?>" data-admin-delete-confirm data-confirm-title="Delete organization member?" data-confirm-message="This removes <?= esc((string) ($member['fullName'] ?? 'this member'), 'attr') ?> from the organization records and public organization page. This action cannot be undone.">
                        <?= csrf_field() ?>
                        <button type="submit" class="act-btn delete" title="Delete" aria-label="Delete member">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="org-admin-card-body">
            <div class="org-admin-card-name-row">
                <strong><?= esc($member['fullName']) ?></strong>
                <?php if (! empty($member['isFeatured'])): ?>
                    <span class="org-admin-badge">Featured</span>
                <?php endif; ?>
            </div>

            <?php if ($roleText !== ''): ?>
                <span class="org-admin-meta"><?= esc($roleText) ?></span>
            <?php endif; ?>

            <span class="org-admin-status <?= ! empty($member['isPublished']) ? 'is-live' : 'is-hidden' ?>">
                <?= ! empty($member['isPublished']) ? 'Published' : 'Hidden' ?>
            </span>
        </div>
    <?php else: ?>
        <div class="org-admin-item-head">
            <div class="org-admin-item-meta">
                <?php if ($isReorderable): ?>
                    <span class="org-drag-handle" title="Drag to reorder" aria-label="Drag to reorder">&#8942;&#8942;</span>
                <?php endif; ?>
                <div>
                    <strong><?= esc($member['fullName']) ?></strong>
                    <span class="org-admin-status <?= ! empty($member['isPublished']) ? 'is-live' : 'is-hidden' ?>">
                        <?= ! empty($member['isPublished']) ? 'Published' : 'Hidden' ?>
                    </span>
                </div>
            </div>
            <div class="org-admin-item-actions">
                <div class="acts">
                    <a href="<?= $editUrl ?>" class="act-btn edit js-org-modal-trigger" data-modal-url="<?= $editUrl ?>" data-member-updated-at="<?= esc((string) ($member['updatedAt'] ?? '')) ?>" title="Edit" aria-label="Edit member">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                        </svg>
                    </a>
                    <form method="POST" action="<?= site_url('admin/organization/members/' . $id . '/delete') ?>" data-admin-delete-confirm data-confirm-title="Delete organization member?" data-confirm-message="This removes <?= esc((string) ($member['fullName'] ?? 'this member'), 'attr') ?> from the organization records and public organization page. This action cannot be undone.">
                        <?= csrf_field() ?>
                        <button type="submit" class="act-btn delete" title="Delete" aria-label="Delete member">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</article>
