<div class="admin-profile-shell">
    <section class="admin-profile-card">
        <div class="admin-profile-head">
            <p>Account security</p>
            <h2>Password</h2>
            <span>Update the password used for email and password sign-in.</span>
        </div>

        <form method="POST" action="<?= site_url('admin/settings/password') ?>" class="admin-profile-password-form">
            <?= csrf_field() ?>

            <label class="admin-profile-field">
                <span>Current password</span>
                <input type="password" name="currentPassword" autocomplete="current-password" required>
            </label>

            <label class="admin-profile-field">
                <span>New password</span>
                <input type="password" name="newPassword" autocomplete="new-password" minlength="8" required>
            </label>

            <label class="admin-profile-field">
                <span>Confirm new password</span>
                <input type="password" name="confirmPassword" autocomplete="new-password" minlength="8" required>
            </label>

            <div class="admin-profile-actions">
                <button type="submit" class="btn btn-p">Update Password</button>
            </div>
        </form>
    </section>
</div>
