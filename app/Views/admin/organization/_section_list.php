<?php
/**
 * @var array<int,array<string,mixed>> $members
 * @var string $activeSection
 */
$members = $members ?? [];
$activeSection = $activeSection ?? '';
$lastIndex = count($members) - 1;
?>
<?php if (empty($members)): ?>
    <div class="org-admin-empty">
        <strong>No members in this section</strong>
        <span>Add the first member using the button above.</span>
    </div>
<?php else: ?>
    <div class="org-admin-list org-admin-card-grid" data-org-reorder-list data-section="<?= esc($activeSection) ?>" data-category="">
        <?php foreach ($members as $index => $member): ?>
            <?= view('admin/organization/_member_row', [
                'member' => $member,
                'activeSection' => $activeSection,
                'isFirst' => $index === 0,
                'isLast' => $index === $lastIndex,
            ]) ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
