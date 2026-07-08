<?php
$admin = is_array($admin ?? null) ? $admin : [];
$googleEmail = trim((string) ($admin['googleEmail'] ?? ''));
$googleSub = trim((string) ($admin['googleSub'] ?? ''));
$isLinked = $googleEmail !== '' || $googleSub !== '';
?>

<div class="admin-profile-shell">
    <section class="admin-profile-card">
        <div class="admin-profile-head">
            <p>Account security</p>
            <h2>Google Account</h2>
            <span>Choose the Google account you want to use when signing in.</span>
        </div>

        <div class="admin-profile-status <?= $isLinked ? 'is-linked' : 'is-unlinked' ?>">
            <div class="admin-profile-status-icon" aria-hidden="true">
                <?php if ($isLinked): ?>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                <?php else: ?>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                <?php endif; ?>
            </div>
            <div>
                <strong><?= $isLinked ? 'Google sign-in is linked' : 'Google sign-in is not linked yet' ?></strong>
                <span>
                    <?= $isLinked
                        ? 'You can sign in with the Google account shown below.'
                        : 'Connect your Google account so you can use Continue with Google when signing in.' ?>
                </span>
            </div>
        </div>

        <div class="admin-profile-grid">
            <div class="admin-profile-field">
                <span>Account email</span>
                <strong><?= esc((string) ($admin['email'] ?? '')) ?></strong>
            </div>
            <div class="admin-profile-field">
                <span>Linked Google email</span>
                <strong><?= $googleEmail !== '' ? esc($googleEmail) : 'Not linked' ?></strong>
            </div>
        </div>

        <div class="admin-profile-actions">
            <a href="<?= site_url('admin/google-account/connect') ?>" class="btn btn-p">
                <?= $isLinked ? 'Change Google Account' : 'Connect Google Account' ?>
            </a>

            <?php if ($isLinked): ?>
                <form action="<?= site_url('admin/google-account/unlink') ?>" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-o">
                        Unlink Google Account
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</div>
