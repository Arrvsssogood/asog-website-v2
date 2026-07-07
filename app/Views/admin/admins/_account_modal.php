<?php
$isEdit = ($modalMode ?? '') === 'edit' && is_array($modalAdmin ?? null);
$adminId = $isEdit ? (int) ($modalAdmin['id'] ?? 0) : 0;
$formUrl = $modalSubmitUrl ?? ($isEdit
    ? site_url('admin/accounts/modal/' . $adminId)
    : site_url('admin/accounts/modal'));
$errors = $modalErrors ?? [];
$roleValue = (string) ($formData['role'] ?? old('role', $isEdit ? ($modalAdmin['role'] ?? 'superadmin') : 'superadmin'));
$fullNameValue = (string) ($formData['fullName'] ?? old('fullName', $isEdit ? ($modalAdmin['fullName'] ?? '') : ''));
$emailValue = (string) ($formData['email'] ?? old('email', $isEdit ? ($modalAdmin['email'] ?? '') : ''));
$googleEmailValue = (string) ($formData['googleEmail'] ?? old('googleEmail', $isEdit ? ($modalAdmin['googleEmail'] ?? '') : ''));
$isActiveValue = (string) ($formData['isActive'] ?? old('isActive', $isEdit ? (string) ($modalAdmin['isActive'] ?? '1') : '1')) !== '0';
?>
<div class="account-admin-modal" data-account-modal>
    <button type="button" class="account-admin-modal-backdrop" data-account-modal-close aria-label="Close modal"></button>
    <div class="account-admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="accountModalTitle">
        <div class="account-admin-modal-head">
            <div>
                <span>Admin access</span>
                <h2 id="accountModalTitle"><?= $isEdit ? 'Edit Account' : 'New Account' ?></h2>
                <p><?= $isEdit ? 'Update account access, Google login email, and status.' : 'Create an admin account. Google will link automatically on first sign-in.' ?></p>
            </div>
            <button type="button" class="account-admin-modal-close" data-account-modal-close aria-label="Close modal">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <?php if (! empty($errors)): ?>
            <div class="account-admin-errors">
                <?php foreach ($errors as $error): ?>
                    <div><?= esc((string) $error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= esc($formUrl) ?>" data-account-modal-form class="account-admin-modal-form">
            <?= csrf_field() ?>

            <div class="account-admin-modal-body">
                <section class="account-admin-modal-section">
                    <div class="account-admin-modal-grid">
                        <label class="account-admin-field">
                            <span>Full name</span>
                            <input type="text" name="fullName" value="<?= esc($fullNameValue) ?>" maxlength="150" required placeholder="Juan Dela Cruz">
                        </label>

                        <label class="account-admin-field">
                            <span>Email</span>
                            <input type="email" name="email" value="<?= esc($emailValue) ?>" required placeholder="admin@example.com">
                        </label>

                        <label class="account-admin-field">
                            <span>Role</span>
                            <select name="role" class="lf-select" required>
                                <option value="admin" <?= $roleValue === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="superadmin" <?= $roleValue === 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                                <option value="editor" <?= $roleValue === 'editor' ? 'selected' : '' ?>>Editor</option>
                            </select>
                        </label>
                    </div>
                </section>

                <?php if ($isEdit): ?>
                    <section class="account-admin-modal-section">
                        <div class="account-admin-modal-grid account-admin-modal-grid-single">
                            <label class="account-admin-field">
                                <span>Google login email</span>
                                <input type="email" name="googleEmail" value="<?= esc($googleEmailValue) ?>" placeholder="user@gmail.com">
                            </label>
                        </div>
                    </section>

                    <label class="account-admin-switch-row">
                        <input type="hidden" name="isActive" value="0">
                        <span class="account-switch-copy">
                            <strong>Active account</strong>
                            <small>Allow this admin to sign in and access the dashboard.</small>
                        </span>
                        <span class="account-switch">
                            <input type="checkbox" name="isActive" value="1" <?= $isActiveValue ? 'checked' : '' ?>>
                            <span class="track"></span>
                        </span>
                    </label>
                <?php endif; ?>
            </div>

            <div class="account-admin-form-actions">
                <button type="button" data-account-modal-close class="btn btn-o">Cancel</button>
                <button type="submit" class="btn btn-p">
                    <?php if ($isEdit): ?>
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    <?php endif; ?>
                    <?= $isEdit ? 'Save changes' : 'Add Account' ?>
                </button>
            </div>
        </form>
    </div>
</div>
