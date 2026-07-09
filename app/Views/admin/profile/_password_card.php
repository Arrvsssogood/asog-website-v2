<div class="admin-profile-shell">
    <section class="admin-profile-card">
        <div class="admin-profile-head">
            <h2>Password</h2>
            <span>Update the password used for email and password sign-in.</span>
        </div>

        <form method="POST" action="<?= site_url('admin/settings/password') ?>" class="admin-profile-password-form" data-dirty-check data-dirty-btn=".btn-p">            <?= csrf_field() ?>

            <label class="admin-profile-field">
                <span>Current password</span>
                <div class="admin-profile-password-input">
                    <input id="settingsCurrentPassword" type="password" name="currentPassword" autocomplete="current-password" required>
                    <button type="button" class="admin-profile-password-toggle" data-settings-password-toggle aria-controls="settingsCurrentPassword" aria-label="Show password" aria-pressed="false">
                        <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke-width="2" aria-hidden="true">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke-width="2" aria-hidden="true">
                            <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.77 21.77 0 0 1 5.06-6.94"></path>
                            <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 7 11 7a21.77 21.77 0 0 1-2.16 3.19"></path>
                            <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                            <path d="M1 1l22 22"></path>
                        </svg>
                    </button>
                </div>
            </label>

            <label class="admin-profile-field">
                <span>New password</span>
                <div class="admin-profile-password-input">
                    <input id="settingsNewPassword" type="password" name="newPassword" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="admin-profile-password-toggle" data-settings-password-toggle aria-controls="settingsNewPassword" aria-label="Show password" aria-pressed="false">
                        <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke-width="2" aria-hidden="true">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke-width="2" aria-hidden="true">
                            <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.77 21.77 0 0 1 5.06-6.94"></path>
                            <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 7 11 7a21.77 21.77 0 0 1-2.16 3.19"></path>
                            <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                            <path d="M1 1l22 22"></path>
                        </svg>
                    </button>
                </div>
            </label>

            <label class="admin-profile-field">
                <span>Confirm new password</span>
                <div class="admin-profile-password-input">
                    <input id="settingsConfirmPassword" type="password" name="confirmPassword" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="admin-profile-password-toggle" data-settings-password-toggle aria-controls="settingsConfirmPassword" aria-label="Show password" aria-pressed="false">
                        <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke-width="2" aria-hidden="true">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke-width="2" aria-hidden="true">
                            <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.77 21.77 0 0 1 5.06-6.94"></path>
                            <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 7 11 7a21.77 21.77 0 0 1-2.16 3.19"></path>
                            <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                            <path d="M1 1l22 22"></path>
                        </svg>
                    </button>
                </div>
            </label>

            <div class="admin-profile-actions">
                <button type="submit" class="btn btn-p">Update Password</button>
            </div>
        </form>
    </section>
</div>
