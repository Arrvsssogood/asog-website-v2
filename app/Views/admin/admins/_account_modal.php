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
$isActiveValue = (string) ($formData['isActive'] ?? old('isActive', $isEdit ? (string) ($modalAdmin['isActive'] ?? '1') : '1')) !== '0';
$currentAdminId = (int) session()->get('admin_id');
$originalRoleValue = $isEdit ? (string) ($modalAdmin['role'] ?? '') : '';
?>
<div class="account-admin-modal" data-account-modal data-account-id="<?= $adminId ?>" data-current-admin-id="<?= $currentAdminId ?>" data-original-role="<?= esc($originalRoleValue) ?>">
    <button type="button" class="account-admin-modal-backdrop" data-account-modal-close aria-label="Close modal"></button>
    <div class="account-admin-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="accountModalTitle">
        <div class="account-admin-modal-head">
            <div>
                <h2 id="accountModalTitle"><?= $isEdit ? 'Edit Account' : 'New Account' ?></h2>
                <p><?= $isEdit ? 'Update account details, role, and access status.' : 'Create an account. Google sign-in can be linked later from Settings.' ?></p>
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
                            <select name="role" class="lf-select" required data-account-role-select data-original-role="<?= esc($originalRoleValue) ?>">
                                <option value="admin" <?= $roleValue === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="superadmin" <?= $roleValue === 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                                <option value="editor" <?= $roleValue === 'editor' ? 'selected' : '' ?>>Editor</option>
                            </select>
                        </label>
                    </div>
                </section>

                <?php if ($isEdit): ?>
                    <section class="account-admin-modal-section">
                        <div class="account-admin-status-action" data-account-status-action>
                            <input type="hidden" name="isActive" value="<?= $isActiveValue ? '1' : '0' ?>" data-account-status-input>
                            <div>
                                <span>Account access</span>
                                <strong data-account-status-title><?= $isActiveValue ? 'Active' : 'Inactive' ?></strong>
                                <small data-account-status-copy><?= $isActiveValue
                                    ? 'This account can sign in. Save changes after deactivating to apply the update.'
                                    : 'This account cannot sign in. Save changes after activating to apply the update.' ?></small>
                            </div>
                            <button type="button" class="btn <?= $isActiveValue ? 'btn-danger-soft' : 'btn-p' ?>" data-account-status-toggle>
                                <?= $isActiveValue ? 'Deactivate Account' : 'Activate Account' ?>
                            </button>
                        </div>
                    </section>
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
