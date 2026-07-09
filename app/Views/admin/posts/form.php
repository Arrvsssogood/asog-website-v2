<?php
/**
 * Post form — shared for Create and Edit.
 *
 * Variables:
 *   $pageTitle  — "New Post" or "Edit Post"
 *   $post       — (edit only) associative array of current values
 *   $activePage — always "posts"
 */

$isEdit  = isset($post);
$formUrl = $isEdit
    ? site_url('admin/posts/' . $post['id'])
    : site_url('admin/posts');
?>

<link rel="stylesheet" href="<?= base_url('assets/css/adminPostForm.css') ?>">

<form action="<?= $formUrl ?>" method="POST" enctype="multipart/form-data" id="postForm" data-dirty-check data-dirty-btn=".form-actions button.btn-o[type=submit]">    <?= csrf_field() ?>
    <?php if ($isEdit): ?>
    <input type="hidden" name="_method" value="PUT" />
    <?php endif; ?>
<div class="edit-tabs" role="tablist">
    <button type="button" role="tab" aria-selected="true" aria-controls="editPanel" id="editTab" class="edit-tab active">Edit</button>
    <?php if ($isEdit): ?>
    <button type="button" role="tab" aria-selected="false" aria-controls="previewPanel" id="previewTab" class="edit-tab">Preview</button>
    <?php endif; ?>
</div>

<div class="tab-panel" id="editPanel" role="tabpanel" aria-labelledby="editTab">
    <div class="form-card">
        <div class="form-grid">

            <!-- Title -->
            <div class="field">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?= esc($isEdit ? $post['title'] : old('title')) ?>"
                    required placeholder="Post title">
            </div>

            <!-- Slug -->
            <div class="field">
                <label for="slug">URL slug</label>
                <input type="text" id="slug" name="slug"
                    value="<?= esc(old('slug') !== null ? old('slug') : ($isEdit ? $post['slug'] : '')) ?>"
                    placeholder="climbing-with-purpose-altitude-framework">
                <p class="field-help">This is the public URL part after /news/. Keep it short, lowercase, and SEO-friendly.</p>
            </div>

            <!-- Category + Author -->
            <div class="form-row">
                <div class="field">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <?php
                        $cat = $isEdit ? $post['category'] : old('category');
                        foreach (\Config\PostCategories::all() as $value => $label):
                        ?>
                        <option value="<?= $value ?>" <?= $cat === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="authorName">Author</label>
                    <input type="text" id="authorName" name="authorName"
                        value="<?= esc($isEdit ? $post['authorName'] : (old('authorName') ?: 'ASOG TBI')) ?>"
                        placeholder="Author name">
                </div>
            </div>

            <!-- Published date -->
            <div class="field">
                <label for="publishedAt">Publication date</label>
                <input type="date" id="publishedAt" name="publishedAt"
                    value="<?= esc($isEdit && !empty($post['publishedAt']) ? date('Y-m-d', strtotime($post['publishedAt'])) : old('publishedAt')) ?>"
                    placeholder="Leave blank for today">
            </div>

            <!-- Short description -->
            <div class="field">
                <label for="shortDescription">Short description</label>
                <textarea id="shortDescription" name="shortDescription" rows="2"
                    placeholder="A brief summary shown in previews"><?= esc($isEdit ? $post['shortDescription'] : old('shortDescription')) ?></textarea>
            </div>

            <!-- Content (Quill) -->
            <div class="field">
                <label>Content</label>
                <div class="editor-wrap">
                    <div class="quill-editor"><?= $isEdit ? $post['content'] : old('content') ?></div>
                    <input type="hidden" name="content" class="quill-content"
                        value="<?= esc($isEdit ? $post['content'] : old('content')) ?>">
                </div>
            </div>

            <!-- Image upload -->
            <div class="field">
                <label>Cover image</label>
                <div class="upload-zone" id="uploadZone">
                    <input type="file" name="image" id="imageInput" accept="image/*">
                    <div class="label" id="uploadLabel"><strong>Click to upload</strong> or drag an image here</div>
                    <div class="upload-preview" id="uploadPreview">
                        <?php if ($isEdit && ! empty($post['imagePath'])): ?>
                        <img src="<?= site_url($post['imagePath']) ?>" alt="">
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($isEdit && ! empty($post['imagePath'])): ?>
                <p style="font-size:.62rem;color:#94a3b8;margin-top:.35rem">Click the image to replace the current cover
                </p>
                <?php endif; ?>
            </div>

            <!-- Toggles -->
            <div class="switch-row">
                <div class="switch">
                    <label>
                        <input type="checkbox" name="isFeatured" value="1"
                            <?= ($isEdit && ! empty($post['isFeatured'])) ? 'checked' : '' ?>>
                        <span class="track"></span>
                    </label>
                    <span>Featured <span style="font-size:.65rem;color:#94a3b8;font-weight:400">(up to 5 show in hero)</span></span>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= site_url('admin/posts') ?>" class="btn-o">← Back to posts</a>

                <span style="flex:1"></span>

                <?php if ($isEdit && $post['isPublished']): ?>

                    <button type="submit" name="action" value="draft" class="btn-p">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                        </svg>
                        Unpublish
                    </button>

                    <button type="submit" name="action" value="publish" class="btn-o">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Changes
                    </button>

                <?php else: ?>

                    <button type="submit" name="action" value="draft" class="btn-o">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v5h5" />
                        </svg>
                        Save as Draft
                    </button>

                    <button type="submit" name="action" value="publish" class="btn-p">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <?= $isEdit ? 'Publish' : 'Publish Post' ?>
                    </button>

                <?php endif; ?>

            </div>
            <div id="previewError" class="preview-error-msg" style="display:none; color: #ef4444; font-size: 0.85rem; margin-top: 0.5rem; text-align: right;"></div>
        </div>
    </div>
</div>

<?php if ($isEdit): ?>
<div class="tab-panel" id="previewPanel" role="tabpanel" aria-labelledby="previewTab" hidden>
    <div class="preview-card">
        <div class="preview-header">
            <h3>Post Preview</h3>
            <a href="<?= site_url('admin/posts/' . $post['id'] . '/preview') ?>" target="_blank" class="btn-o" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                Open in New Tab
            </a>
        </div>
        <div class="preview-content">
            <iframe id="previewIframe" data-preview-url="<?= site_url('admin/posts/' . $post['id'] . '/preview') ?>"></iframe>
        </div>
    </div>
</div>
<?php endif; ?>
</form>

<div class="ql-image-dialog-overlay" id="qlImageDialogOverlay" hidden>
    <div class="ql-image-dialog" role="dialog" aria-modal="true" aria-labelledby="qlImageDialogTitle">
        <div class="ql-image-dialog__header">
            <div class="ql-image-dialog__title" id="qlImageDialogTitle">Image</div>
        </div>

        <div class="ql-image-dialog__preview-wrap">
            <img class="ql-image-dialog__preview" id="qlImageDialogPreview" alt="">
        </div>

        <label class="ql-image-dialog__field">
            <span class="ql-image-dialog__label">Caption</span>
            <textarea class="ql-image-dialog__caption" id="qlImageDialogCaption" rows="4" aria-label="Image caption"></textarea>
        </label>

        <div class="ql-image-dialog__actions">
            <button type="button" class="ql-image-dialog__delete" id="qlImageDialogDelete">Delete image</button>
            <div class="ql-image-dialog__action-group">
                <button type="button" class="ql-image-dialog__cancel" id="qlImageDialogCancel">Cancel</button>
                <button type="button" class="ql-image-dialog__save" id="qlImageDialogSave">Save</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/admin/posts/form.js') ?>"></script>
