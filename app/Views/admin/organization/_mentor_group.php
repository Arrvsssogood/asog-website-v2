<?php
/**
 * @var array{category:string,members:array<int,array<string,mixed>>} $group
 * @var string $activeMentorCategory
 */
$category = (string) ($group['category'] ?? '');
$members = $group['members'] ?? [];
$lastIndex = count($members) - 1;
$categorySlug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $category));
$categoryId = 'mentor-' . $categorySlug;
?>
<section id="mentor-group-<?= esc($categorySlug) ?>" class="org-admin-mentor-group<?= ($activeMentorCategory ?? '') === $category ? ' is-focused' : '' ?>" data-mentor-group data-anchor-id="<?= esc($categoryId) ?>">
    <div class="org-admin-mentor-head">
        <div>
            <span class="org-admin-mentor-kicker">Mentor area</span>
            <h3><?= esc($category) ?></h3>
        </div>
        <a href="<?= site_url('admin/organization/modal?section=mentor&category=' . rawurlencode($category)) ?>" class="btn btn-p btn-s js-org-modal-trigger" data-modal-url="<?= site_url('admin/organization/modal?section=mentor&category=' . rawurlencode($category)) ?>">Add mentor</a>
    </div>

    <?php if (empty($members)): ?>
        <div class="org-admin-empty org-admin-empty-compact">
            <span>No mentors in this area yet.</span>
        </div>
    <?php else: ?>
        <div class="org-admin-list" data-org-reorder-list data-section="mentor" data-category="<?= esc($category) ?>">
            <?php foreach ($members as $index => $member): ?>
                <?= view('admin/organization/_member_row', [
                    'member' => $member,
                    'activeSection' => 'mentor',
                    'isFirst' => $index === 0,
                    'isLast' => $index === $lastIndex,
                ]) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
