<?php
$isEdit = isset($modalMember) && is_array($modalMember);
$memberId = $isEdit ? (int) ($modalMember['id'] ?? 0) : 0;
$formUrl = $modalSubmitUrl ?? ($isEdit
    ? site_url('admin/organization/modal/' . $memberId)
    : site_url('admin/organization/modal'));

$selectedSection = old('section', $isEdit ? ($modalMember['section'] ?? '') : ($defaultSection ?? 'core_team'));
$selectedCategory = old('mentorCategory', $isEdit ? ($modalMember['mentorCategory'] ?? '') : ($defaultCategory ?? ''));
$selectedSection = trim((string) ($formData['section'] ?? $selectedSection));
$selectedCategory = trim((string) ($formData['mentorCategory'] ?? $selectedCategory));
$isMentor = $selectedSection === 'mentor';
$selectedSectionLabel = App\Models\OrganizationMemberModel::sectionLabel($selectedSection);
$editTitleSuffix = match ($selectedSection) {
    App\Models\OrganizationMemberModel::SECTION_CORE_TEAM => 'Core Team Member',
    App\Models\OrganizationMemberModel::SECTION_TBI_STAFF  => 'TBI Staff Member',
    App\Models\OrganizationMemberModel::SECTION_INTERN    => 'Intern',
    App\Models\OrganizationMemberModel::SECTION_MENTOR    => 'Mentor',
    default => 'Member',
};
$modalTitle = $isEdit ? ('Edit ' . $editTitleSuffix) : 'Add member';
$closeUrl = '#';
$errors = $formErrors ?? [];
$isCurrentlyFeatured = (string) ($formData['isFeatured'] ?? old('isFeatured', $isEdit ? (string) ($modalMember['isFeatured'] ?? '0') : '0')) === '1';
?>
<div class="org-admin-modal" data-org-modal<?= $isEdit ? ' data-member-id="' . $memberId . '" data-member-updated-at="' . esc((string) ($modalMember['updatedAt'] ?? '')) . '"' : '' ?>>
    <button type="button" class="org-admin-modal-backdrop" data-org-modal-close aria-label="Close modal"></button>
    <div class="org-admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="orgMemberModalTitle">
        <div class="org-admin-modal-head">
            <div>
                <h2 id="orgMemberModalTitle"><?= esc($modalTitle) ?></h2>
            </div>
            <button type="button" class="org-admin-modal-close" data-org-modal-close aria-label="Close modal">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <?php if (! empty($errors)): ?>
            <div class="org-admin-errors">
                <?php foreach ($errors as $error): ?>
                    <div><?= esc((string) $error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $formUrl ?>" enctype="multipart/form-data" data-modal-form data-dirty-check data-dirty-btn=".btn-p" class="org-admin-form org-admin-modal-form">
            <?= csrf_field() ?>

            <div class="org-admin-modal-body">
                <section class="org-admin-modal-section">
                    <div class="org-admin-modal-grid <?= $isEdit ? 'is-single' : '' ?>">
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="section" id="orgSection" value="<?= esc($selectedSection) ?>">
                        <?php else: ?>
                            <label class="org-admin-field">
                                <span>Section</span>
                                <select name="section" id="orgSection" class="lf-select" required>
                                    <?php foreach (App\Models\OrganizationMemberModel::SECTIONS as $sectionKey): ?>
                                        <option value="<?= esc($sectionKey) ?>" <?= $selectedSection === $sectionKey ? 'selected' : '' ?>>
                                            <?= esc(App\Models\OrganizationMemberModel::sectionLabel($sectionKey)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endif; ?>

                        <label class="org-admin-field">
                            <span>Full name</span>
                            <input type="text" name="fullName" value="<?= esc((string) ($formData['fullName'] ?? old('fullName', $isEdit ? ($modalMember['fullName'] ?? '') : ''))) ?>" maxlength="150" required>
                        </label>
                    </div>
                </section>

                <section class="org-admin-modal-section">
                    <div id="orgRoleFields" class="org-admin-modal-grid <?= $isMentor ? 'is-hidden' : '' ?>">
                        <label class="org-admin-field">
                            <span>Primary title / role</span>
                            <input type="text" name="rolePrimary" value="<?= esc((string) ($formData['rolePrimary'] ?? old('rolePrimary', $isEdit ? ($modalMember['rolePrimary'] ?? '') : ''))) ?>" maxlength="255">
                        </label>
                        <label class="org-admin-field">
                            <span>Secondary title / role</span>
                            <input type="text" name="roleSecondary" value="<?= esc((string) ($formData['roleSecondary'] ?? old('roleSecondary', $isEdit ? ($modalMember['roleSecondary'] ?? '') : ''))) ?>" maxlength="255">
                        </label>
                    </div>

                    <label class="org-admin-field <?= $isMentor ? '' : 'is-hidden' ?>" id="orgMentorCategoryField">
                        <span>Mentor category</span>
                        <select name="mentorCategory" id="orgMentorCategory" class="lf-select" <?= $isMentor ? 'required' : 'disabled' ?>>
                            <option value="">Select category</option>
                            <?php foreach (($mentorCategories ?? []) as $category): ?>
                                <option value="<?= esc($category) ?>" <?= $selectedCategory === $category ? 'selected' : '' ?>>
                                    <?= esc($category) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </section>

                <section class="org-admin-modal-section <?= $isMentor ? 'is-hidden' : '' ?>" id="orgPhotoField">
                    <div class="org-admin-field">
                        <span>Photo</span>
                        <label class="org-photo-upload-zone">
                            <input type="file" name="photo" accept="image/*" class="org-photo-input">
                            <div class="org-photo-upload-label"><strong>Click to upload</strong> or drag a photo here</div>
                            <div class="org-photo-upload-preview">
                            <?php if ($isEdit && ! empty($modalMember['photoPath'])): ?>
                                    <?= responsiveStaticImg((string) ($modalMember['photoPath'] ?? ''), 'team-org', 'Current photo', 'org-photo-preview') ?>
                            <?php endif; ?>
                            </div>
                        </label>
                        <small>Square image, up to 2 MB.</small>
                    </div>
                </section>

                <div class="org-admin-toggle-grid">
                    <label class="org-admin-switch-row <?= $isMentor ? 'is-hidden' : '' ?>" id="orgFeaturedField" data-original-featured="<?= $isCurrentlyFeatured ? '1' : '0' ?>">
                        <input type="hidden" name="isFeatured" value="0">
                        <span class="org-switch-copy">
                            <strong>Featured layout</strong>
                            <small>Centered leader/manager card in Core Team or TBI Staff.</small>
                            <small class="org-featured-replace-note <?= $isCurrentlyFeatured ? 'is-hidden' : '' ?>">Saving this as featured will replace the current featured member in this section.</small>
                        </span>
                        <span class="org-switch">
                            <input type="checkbox" name="isFeatured" value="1" <?= $isCurrentlyFeatured ? 'checked' : '' ?>>
                            <span class="track"></span>
                        </span>
                    </label>

                    <label class="org-admin-switch-row">
                        <input type="hidden" name="isPublished" value="0">
                        <span class="org-switch-copy">
                            <strong>Published</strong>
                            <small>Show this member on the public page.</small>
                        </span>
                        <span class="org-switch">
                            <input type="checkbox" name="isPublished" value="1" <?= (string) ($formData['isPublished'] ?? old('isPublished', $isEdit ? (string) ($modalMember['isPublished'] ?? '1') : '1')) !== '0' ? 'checked' : '' ?>>
                            <span class="track"></span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="org-admin-form-actions">
                <a href="<?= esc($closeUrl) ?>" data-org-modal-close class="btn btn-o">Cancel</a>
                <button type="submit" class="btn btn-p">
                    <?php if ($isEdit): ?>
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    <?php endif; ?>
                    <?= $isEdit ? 'Save changes' : 'Add member' ?>
                </button>
            </div>
        </form>
    </div>
</div>
