<link rel="stylesheet" href="<?= base_url('assets/css/adminFaqs.css') ?>">

<div class="faq-admin-toolbar">
    <div>
        <span class="faq-admin-count"><?= count($faqs ?? []) ?> FAQ<?= count($faqs ?? []) === 1 ? '' : 's' ?></span>
        <p>Manage the questions shown near the bottom of the public application page.</p>
    </div>
    <a href="<?= site_url('apply#apply-faqs') ?>" target="_blank" rel="noopener" class="btn btn-o">
        View FAQs
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h8v8"/>
        </svg>
    </a>
</div>

<div class="faq-admin-settings">
    <section class="faq-admin-panel">
        <div class="faq-admin-panel-head">
            <span>Apply Page Settings</span>
            <h2>FAQ copy</h2>
            <p>Manage the public FAQ heading and introduction shown above the question list.</p>
        </div>

        <form method="POST" action="<?= site_url('admin/faqs/section') ?>" class="faq-admin-form">
            <?= csrf_field() ?>

            <label class="faq-admin-field">
                <span>Heading</span>
                <input
                    type="text"
                    name="faqTitle"
                    value="<?= esc(old('faqTitle') ?: ($faqTitle ?? '')) ?>"
                    maxlength="120"
                    required
                >
                <small>Maximum 120 characters.</small>
            </label>

            <label class="faq-admin-field">
                <span>Introduction</span>
                <textarea name="faqIntro" rows="4" maxlength="500" required><?= esc(old('faqIntro') ?: ($faqIntro ?? '')) ?></textarea>
                <small>Maximum 500 characters.</small>
            </label>

            <div class="faq-admin-form-actions">
                <button type="submit" class="btn btn-p">Save apply page settings</button>
            </div>
        </form>
    </section>

    <section class="faq-admin-panel">
        <div class="faq-admin-panel-head">
            <span>New Item</span>
            <h2>Add an FAQ</h2>
            <p>New questions are added to the end of the current order.</p>
        </div>

        <form method="POST" action="<?= site_url('admin/faqs') ?>" class="faq-admin-form">
            <?= csrf_field() ?>

            <label class="faq-admin-field">
                <span>Question</span>
                <input
                    type="text"
                    name="question"
                    value="<?= esc(old('question') ?? '') ?>"
                    maxlength="255"
                    required
                >
            </label>

            <label class="faq-admin-field">
                <span>Answer</span>
                <textarea name="answer" rows="4" maxlength="5000" required><?= esc(old('answer') ?? '') ?></textarea>
            </label>

            <label class="faq-admin-publish">
                <input type="hidden" name="isPublished" value="0">
                <input type="checkbox" name="isPublished" value="1" <?= old('isPublished') === '0' ? '' : 'checked' ?>>
                <span>
                    <strong>Publish immediately</strong>
                    <small>Visible on the public apply page.</small>
                </span>
            </label>

            <div class="faq-admin-form-actions">
                <button type="submit" class="btn btn-p">Add FAQ</button>
            </div>
        </form>
    </section>
</div>

<div class="faq-admin-list-head">
    <div>
        <span>Managed Content</span>
        <h2>Questions and answers</h2>
    </div>
    <p>Use the arrow buttons to control the public display order.</p>
</div>

<?php if (empty($faqs)): ?>
    <div class="faq-admin-empty">
        <strong>No FAQs yet</strong>
        <span>Add the first question using the form above.</span>
    </div>
<?php else: ?>
    <div class="faq-admin-list">
        <?php $lastIndex = count($faqs) - 1; ?>
        <?php foreach ($faqs as $index => $faq): ?>
            <?php $id = (int) $faq['id']; ?>
            <article class="faq-admin-item">
                <div class="faq-admin-item-head">
                    <div class="faq-admin-item-meta">
                        <span class="faq-admin-position"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="faq-admin-status <?= ! empty($faq['isPublished']) ? 'is-live' : 'is-hidden' ?>">
                            <?= ! empty($faq['isPublished']) ? 'Published' : 'Hidden' ?>
                        </span>
                    </div>

                    <div class="faq-admin-item-actions">
                        <form method="POST" action="<?= site_url('admin/faqs/' . $id . '/move/up') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="faq-admin-icon-btn" title="Move up" aria-label="Move FAQ up" <?= $index === 0 ? 'disabled' : '' ?>>↑</button>
                        </form>
                        <form method="POST" action="<?= site_url('admin/faqs/' . $id . '/move/down') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="faq-admin-icon-btn" title="Move down" aria-label="Move FAQ down" <?= $index === $lastIndex ? 'disabled' : '' ?>>↓</button>
                        </form>
                        <form method="POST" action="<?= site_url('admin/faqs/' . $id . '/delete') ?>" data-admin-delete-confirm data-confirm-title="Delete FAQ?" data-confirm-message="This removes the question and answer from the FAQ list on the public application page. This action cannot be undone.">
                            <?= csrf_field() ?>
                            <button type="submit" class="act-btn delete" title="Delete" aria-label="Delete FAQ">
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

                <form method="POST" action="<?= site_url('admin/faqs/' . $id . '/update') ?>" class="faq-admin-form faq-admin-edit-form">
                    <?= csrf_field() ?>

                    <label class="faq-admin-field">
                        <span>Question</span>
                        <input type="text" name="question" value="<?= esc($faq['question']) ?>" maxlength="255" required>
                    </label>

                    <label class="faq-admin-field">
                        <span>Answer</span>
                        <textarea name="answer" rows="4" maxlength="5000" required><?= esc($faq['answer']) ?></textarea>
                    </label>

                    <div class="faq-admin-edit-foot">
                        <label class="faq-admin-publish">
                            <input type="hidden" name="isPublished" value="0">
                            <input type="checkbox" name="isPublished" value="1" <?= ! empty($faq['isPublished']) ? 'checked' : '' ?>>
                            <span>
                                <strong>Published</strong>
                                <small>Show this item publicly.</small>
                            </span>
                        </label>
                        <button type="submit" class="btn btn-p btn-s">Save changes</button>
                    </div>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
