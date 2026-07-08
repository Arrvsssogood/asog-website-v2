<link rel="stylesheet" href="<?= base_url('assets/css/adminOrganization.css') ?>">

<div class="org-admin-toolbar">
    <div>
        <span class="org-admin-count"><?= array_sum(array_map('count', $membersBySection ?? [])) ?> members</span>
        <p>Manage team members shown on the public Organization page.</p>
    </div>
    <div class="org-admin-toolbar-actions">
        <a href="<?= site_url('organization') ?>" target="_blank" rel="noopener" class="btn btn-o">
            View Organization page
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h8v8"/>
            </svg>
        </a>
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
    <div class="org-reorder-control-group">
        <button type="button" class="btn btn-o org-reorder-mode-btn" id="orgReorderBtn">Re-order</button>
    </div>
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
