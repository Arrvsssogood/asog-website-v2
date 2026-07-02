<link rel="stylesheet" href="<?= base_url('assets/css/adminPosts.css') ?>">

<div class="posts-admin-toolbar">
    <div>
        <span class="posts-admin-count"><?= $total ?? count($posts ?? []) ?> posts</span>
        <p>Manage news and updates shown across the public website.</p>
    </div>
    <div class="toolbar-actions">
        <?php if (!empty($supportsSortOrder)): ?>
            <button type="button" class="btn btn-o" id="featuredOrderBtn">Order featured</button>
        <?php endif; ?>
        <a href="<?= site_url('admin/posts/create') ?>" class="btn btn-p">New post</a>
    </div>
</div>

<?php
$featuredStories = $featuredStories ?? array_values(array_filter($posts ?? [], static function ($post) {
    return ! empty($post['isFeatured']);
}));
?>

<?php if (!empty($supportsSortOrder)): ?>
<div class="feature-order-modal" id="featureOrderModal" aria-hidden="true">
    <div class="feature-order-backdrop" data-close-modal="true"></div>
    <div class="feature-order-dialog" role="dialog" aria-modal="true" aria-labelledby="featureOrderTitle">
        <div class="feature-order-head">
            <h3 id="featureOrderTitle">Featured Stories Order</h3>
            <button type="button" class="feature-order-close" data-close-modal="true" aria-label="Close">&times;</button>
        </div>
        <p class="feature-order-help">Drag stories to reorder. Top item appears first on the landing featured slider.</p>

        <form action="<?= site_url('admin/posts/featured-order') ?>" method="POST" id="featureOrderForm">
            <?= csrf_field() ?>
            <ul class="feature-order-list" id="featureOrderList">
                <?php if (empty($featuredStories)): ?>
                    <li class="feature-order-empty">No featured stories yet.</li>
                <?php else: ?>
                    <?php foreach ($featuredStories as $story): ?>
                        <li class="feature-order-item" draggable="true" data-id="<?= (int) $story['id'] ?>">
                            <span class="feature-order-grip" aria-hidden="true">::</span>
                            <span class="feature-order-title"><?= esc($story['title']) ?></span>
                            <input type="hidden" name="featuredOrder[]" value="<?= (int) $story['id'] ?>">
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>

            <div class="feature-order-actions">
                <button type="button" class="btn btn-o" data-close-modal="true">Cancel</button>
                <button type="submit" class="btn btn-p" <?= empty($featuredStories) ? 'disabled' : '' ?>>Save order</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (empty($posts)): ?>
    <div class="empty-row">No posts yet. <a href="<?= site_url('admin/posts/create') ?>">Create one.</a></div>
<?php else: ?>
    <table class="posts-tbl">
        <thead>
            <tr>
                <th></th>
                <th>Title</th>
                <th>Category</th>
                <th>Status</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $p): ?>
                <tr>
                    <td>
                        <?php if (! empty($p['imagePath'])): ?>
                            <img src="<?= site_url($p['imagePath']) ?>" alt="" class="tbl-thumb"/>
                        <?php else: ?>
                            <span class="tbl-thumb-empty">
                                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="tbl-title"><?= esc($p['title']) ?></span>
                        <?php if (! empty($p['shortDescription'])): ?>
                            <span class="tbl-desc"><?= esc($p['shortDescription']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="tag tag-cat"><?= esc(ucfirst($p['category'])) ?></span></td>
                    <td>
                        <span class="tag <?= $p['isPublished'] ? 'tag-live' : 'tag-draft' ?>"><?= $p['isPublished'] ? 'Published' : 'Draft' ?></span>
                        <?php if (! empty($p['isFeatured'])): ?>
                            <span class="tag tag-feat">Featured</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.72rem;color:#94a3b8;white-space:nowrap"><?= $p['publishedAt'] ? date('M j, Y', strtotime($p['publishedAt'])) : '—' ?></td>
                    <td>
                        <div class="acts">
                            <a href="<?= site_url('news/' . $p['slug']) ?>" target="_blank" title="Preview">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </a>
                            <a href="<?= site_url('admin/posts/' . $p['id'] . '/edit') ?>" title="Edit">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zM19.5 7.125L16.862 4.487"/><path stroke-linecap="round" stroke-linejoin="round" d="M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                            </a>
                            <form action="<?= site_url('admin/posts/' . $p['id']) ?>" method="POST" onsubmit="return confirm('Delete this post?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="del" title="Delete">
                                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (!empty($totalPages) && $totalPages > 1): ?>
        <div class="tbl-pagination">
            <span class="pag-info">Showing <?= ($currentPage - 1) * $perPage + 1 ?>&ndash;<?= min($currentPage * $perPage, $total) ?> of <?= $total ?></span>
            <div class="pag-controls">
                <?php $prevDisabled = $currentPage <= 1; ?>
                <a class="pag-btn<?= $prevDisabled ? ' pag-disabled' : '' ?>"
                   href="<?= $prevDisabled ? '#' : site_url('admin/posts?page=' . ($currentPage - 1)) ?>" aria-label="Previous">&larr;</a>
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
                    
                    <?php if ($p === $currentPage): ?>
                        <span class="pag-btn pag-active"><?= $p ?></span>
                    <?php else: ?>
                        <a class="pag-btn" href="<?= site_url('admin/posts?page=' . $p) ?>"><?= $p ?></a>
                    <?php endif; ?>
                <?php $prev = $p; endforeach; ?>
                
                <?php $nextDisabled = $currentPage >= $totalPages; ?>
                <a class="pag-btn<?= $nextDisabled ? ' pag-disabled' : '' ?>"
                   href="<?= $nextDisabled ? '#' : site_url('admin/posts?page=' . ($currentPage + 1)) ?>" aria-label="Next">&rarr;</a>
            </div>
        </div>
    <?php endif; ?>


<?php endif; ?>

<?php if (!empty($supportsSortOrder)): ?>
<script>
(function () {
    var modal = document.getElementById('featureOrderModal');
    var openBtn = document.getElementById('featuredOrderBtn');
    var list = document.getElementById('featureOrderList');
    if (!modal || !openBtn || !list) return;

    function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    openBtn.addEventListener('click', openModal);
    modal.addEventListener('click', function (event) {
        if (event.target.matches('[data-close-modal="true"]')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    var dragging = null;

    list.addEventListener('dragstart', function (event) {
        var item = event.target.closest('.feature-order-item');
        if (!item) return;
        dragging = item;
        item.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
    });

    list.addEventListener('dragend', function () {
        if (dragging) {
            dragging.classList.remove('is-dragging');
        }
        dragging = null;
    });

    list.addEventListener('dragover', function (event) {
        if (!dragging) return;
        event.preventDefault();
        var over = event.target.closest('.feature-order-item');
        if (!over || over === dragging) return;

        var rect = over.getBoundingClientRect();
        var before = event.clientY < rect.top + rect.height / 2;
        if (before) {
            list.insertBefore(dragging, over);
        } else {
            list.insertBefore(dragging, over.nextSibling);
        }
    });
})();
</script>
<?php endif; ?>
