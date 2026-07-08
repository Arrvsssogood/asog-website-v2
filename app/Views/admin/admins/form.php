<?php
$isEdit = $admin !== null;
$pageTitle = $isEdit ? 'Edit Account' : 'New Account';
?>

<link rel="stylesheet" href="<?= base_url('assets/css/adminAdmins.css') ?>">

<div style="margin-bottom: 1.5rem;">
    <a href="<?= site_url('admin/accounts') ?>" style="display:inline-flex;align-items:center;gap:0.35rem;color:#475569;text-decoration:none;font-weight:500;transition:color 0.15s;">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Go Back
    </a>
</div>

<form action="<?= site_url($isEdit ? 'admin/accounts/' . $admin['id'] : 'admin/accounts') ?>" method="POST" class="form-container" data-dirty-check data-dirty-btn=".btn-p">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>

    <h2><?= $pageTitle ?></h2>

    <div class="form-grid">
        <div class="form-group">
            <label for="fullName">Full name *</label>
            <input type="text" id="fullName" name="fullName"
                value="<?= esc($isEdit ? ($admin['fullName'] ?? '') : old('fullName')) ?>"
                maxlength="150"
                required
                placeholder="Juan Dela Cruz">
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" 
                value="<?= esc($isEdit ? $admin['email'] : old('email')) ?>" 
                required 
                placeholder="admin@example.com">
        </div>

        <div class="form-group">
            <label for="role">Role *</label>
            <select id="role" name="role" required>
                <option value="admin" <?= ($isEdit && $admin['role'] === 'admin') || old('role') === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="superadmin" <?= ($isEdit && $admin['role'] === 'superadmin') || (!$isEdit || old('role') === 'superadmin') ? 'selected' : '' ?>>Super Admin</option>
                <option value="editor" <?= ($isEdit && $admin['role'] === 'editor') || old('role') === 'editor' ? 'selected' : '' ?>>Editor</option>
            </select>
        </div>
    </div>

    <?php if ($isEdit): ?>
        <input type="hidden" name="isActive" value="<?= ! empty($admin['isActive']) ? '1' : '0' ?>">
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-p">
            <?= $isEdit ? 'Update' : 'Add Account' ?>
        </button>
        <a href="<?= site_url('admin/accounts') ?>" class="btn btn-o">Cancel</a>
    </div>
</form>
