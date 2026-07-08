<link rel="stylesheet" href="<?= base_url('assets/css/adminSettings.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/adminProfile.css') ?>">

<?php
$canManageSiteSettings = ! empty($canManageSiteSettings);
$duplicateEmailSetting = old('allowDuplicateEmails');
$allowDuplicateEmails = $duplicateEmailSetting !== null
    ? $duplicateEmailSetting === '1'
    : ! empty($allowDuplicateEmails);
$deadlineSetting = old('showApplicationDeadline');
$showApplicationDeadline = $deadlineSetting !== null
    ? $deadlineSetting === '1'
    : ! empty($showApplicationDeadline);
$loaderSetting = old('landingLoaderEnabled');
$landingLoaderEnabled = $loaderSetting !== null
    ? $loaderSetting === '1'
    : ($landingLoaderEnabled ?? true);
$applicationStartDate = old('applicationStartDate', $applicationStartDate ?? '');
$applicationEndDate = old('applicationEndDate', $applicationEndDate ?? '');
$windowStatus = $applicationWindowStatus ?? [
    'label' => 'Always open',
    'description' => 'No application dates are set.',
    'state' => 'open',
];
$gmailStatus = $gmailStatus ?? [
    'label' => 'Needs setup',
    'description' => 'Email sending needs setup before messages can be sent.',
    'state' => 'off',
    'detail' => '',
];
$recaptchaStatus = $recaptchaStatus ?? [
    'label' => 'Off',
    'description' => 'Spam protection is off or missing a key.',
    'state' => 'off',
];
$loaderStatus = $loaderStatus ?? [
    'label' => 'On',
    'description' => 'Runs once per browser session on the homepage.',
    'state' => 'ready',
];
?>

<div class="settings-stack">
    <?php if ($canManageSiteSettings): ?>
    <section class="settings-group" aria-labelledby="public-application-settings-title">
        <div class="settings-group-head">
            <p class="settings-kicker">Applications</p>
            <h2 id="public-application-settings-title">Public Application Settings</h2>
            <p class="settings-copy">Manage when applications open and how applicant emails are handled.</p>
        </div>

        <div class="settings-card">
            <div class="settings-head">
                <p class="settings-kicker">Application dates</p>
                <h3>Submission window</h3>
                <p class="settings-copy">Set the dates when visitors can send a new application.</p>
            </div>

            <form method="POST" action="<?= site_url('admin/settings/applications') ?>" class="settings-form" data-toggle-form>
                <?= csrf_field() ?>

                <div class="settings-notice settings-notice-<?= esc((string) ($windowStatus['state'] ?? 'open')) ?>">
                    <span class="settings-notice-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h.01"></path>
                        </svg>
                    </span>
                    <div>
                        <strong><span>Form access status:</span> <?= esc((string) ($windowStatus['label'] ?? 'Always open')) ?></strong>
                        <p><?= esc((string) ($windowStatus['description'] ?? 'No application dates are set.')) ?></p>
                    </div>
                </div>

                <div class="settings-field-grid">
                    <label class="settings-field" for="applicationStartDate">
                        <span>Start date</span>
                        <input id="applicationStartDate" type="date" name="applicationStartDate" value="<?= esc((string) $applicationStartDate) ?>">
                    </label>
                    <label class="settings-field" for="applicationEndDate">
                        <span>End date</span>
                        <input id="applicationEndDate" type="date" name="applicationEndDate" value="<?= esc((string) $applicationEndDate) ?>">
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div class="settings-toggle-copy">
                        <strong>Duplicate applicant emails</strong>
                        <span><?= $allowDuplicateEmails
                            ? 'Applicants can submit more than once with the same email address.'
                            : 'Each email address can only send one active application.' ?></span>
                    </div>

                    <label class="settings-switch" for="allowDuplicateEmails">
                        <input type="hidden" name="allowDuplicateEmails" value="0">
                        <input
                            id="allowDuplicateEmails"
                            type="checkbox"
                            name="allowDuplicateEmails"
                            value="1"
                            <?= $allowDuplicateEmails ? 'checked' : '' ?>
                        >
                        <span class="settings-slider" aria-hidden="true"></span>
                        <span class="settings-switch-label"><?= $allowDuplicateEmails ? 'ON' : 'OFF' ?></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div class="settings-toggle-copy">
                        <strong>Show application dates</strong>
                        <span><?= $showApplicationDeadline
                            ? 'The Apply overview can show the start or end date.'
                            : 'The Apply overview hides the start and end dates.' ?></span>
                    </div>

                    <label class="settings-switch" for="showApplicationDeadline">
                        <input type="hidden" name="showApplicationDeadline" value="0">
                        <input
                            id="showApplicationDeadline"
                            type="checkbox"
                            name="showApplicationDeadline"
                            value="1"
                            <?= $showApplicationDeadline ? 'checked' : '' ?>
                        >
                        <span class="settings-slider" aria-hidden="true"></span>
                        <span class="settings-switch-label"><?= $showApplicationDeadline ? 'ON' : 'OFF' ?></span>
                    </label>
                </div>

                <div class="settings-actions">
                    <button type="submit" class="btn btn-p">Save application settings</button>
                </div>
            </form>
        </div>
    </section>

    <section class="settings-group" aria-labelledby="landing-display-title">
        <div class="settings-group-head">
            <p class="settings-kicker">Public Website</p>
            <h2 id="landing-display-title">Landing Page Display</h2>
            <p class="settings-copy">Choose what appears on the public homepage and Organization page.</p>
        </div>

        <div class="settings-grid settings-grid-two">
            <div class="settings-card">
                <div class="settings-head">
                    <p class="settings-kicker">Homepage incubatees</p>
                    <h3>Featured cohort</h3>
                    <p class="settings-copy">Choose which incubatees appear on the landing page.</p>
                </div>

                <form method="POST" action="<?= site_url('admin/settings/homepage-incubatees-filter') ?>" class="settings-form">
                    <?= csrf_field() ?>
                    <div class="settings-control-row">
                        <div class="settings-control-copy">
                            <strong>Cohort shown on homepage</strong>
                            <span>Select one cohort or show all published incubatees.</span>
                        </div>
                        <div class="settings-field settings-field-compact">
                            <select id="landingCohortFilter" name="landingCohortFilter" class="settings-select">
                                <option value="all" <?= ($selectedLandingFilter ?? 'all') === 'all' ? 'selected' : '' ?>>All Cohorts</option>
                                <?php foreach (($landingFilterOptions ?? []) as $cohortName): ?>
                                    <option value="<?= esc($cohortName) ?>" <?= ($selectedLandingFilter ?? 'all') === $cohortName ? 'selected' : '' ?>>
                                        <?= esc($cohortName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="settings-actions">
                        <button type="submit" class="btn btn-p">Save</button>
                    </div>
                </form>
            </div>

            <div class="settings-card">
                <div class="settings-head">
                    <p class="settings-kicker">Organization Page</p>
                    <h3>Interns section</h3>
                    <p class="settings-copy">Show or hide interns on the public Organization page.</p>
                </div>

                <form method="POST" action="<?= site_url('admin/settings/interns-visibility') ?>" class="settings-form" data-toggle-form>
                    <?= csrf_field() ?>

                    <div class="settings-toggle-row">
                        <div class="settings-toggle-copy">
                            <strong>Show interns section</strong>
                            <span><?= ! empty($showInternsSection) ? 'Visible on the Organization page.' : 'Hidden from the Organization page.' ?></span>
                        </div>

                        <label class="settings-switch" for="showInternsSection">
                            <input type="hidden" name="showInternsSection" value="0">
                            <input
                                id="showInternsSection"
                                type="checkbox"
                                name="showInternsSection"
                                value="1"
                                <?= ! empty($showInternsSection) ? 'checked' : '' ?>
                            >
                            <span class="settings-slider" aria-hidden="true"></span>
                            <span class="settings-switch-label"><?= ! empty($showInternsSection) ? 'ON' : 'OFF' ?></span>
                        </label>
                    </div>

                    <div class="settings-actions">
                        <button type="submit" class="btn btn-p">Save Visibility</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section class="settings-group" aria-labelledby="game-visibility-title">
        <div class="settings-group-head">
            <p class="settings-kicker">Interactive</p>
            <h2 id="game-visibility-title">Game Visibility</h2>
            <p class="settings-copy">Control whether visitors can see and play Guess the Startup.</p>
        </div>

        <div class="settings-card">
            <div class="settings-head">
                <p class="settings-kicker">Game</p>
                <h3>Guess The Startup</h3>
                <p class="settings-copy">Set game access and landing page visibility.</p>
            </div>

            <form method="POST" action="<?= site_url('admin/settings/guess-startup/availability') ?>" class="settings-form" data-toggle-form>
                <?= csrf_field() ?>

                <div class="settings-toggle-row">
                    <div class="settings-toggle-copy">
                        <strong>Allow gameplay</strong>
                        <span><?= ! empty($isGuessStartupEnabled) ? 'Visitors can start the game.' : 'Visitors cannot start the game.' ?></span>
                    </div>

                    <label class="settings-switch" for="guessStartupEnabled">
                        <input type="hidden" name="guessStartupEnabled" value="0">
                        <input
                            id="guessStartupEnabled"
                            type="checkbox"
                            name="guessStartupEnabled"
                            value="1"
                            <?= ! empty($isGuessStartupEnabled) ? 'checked' : '' ?>
                        >
                        <span class="settings-slider" aria-hidden="true"></span>
                        <span class="settings-switch-label"><?= ! empty($isGuessStartupEnabled) ? 'ON' : 'OFF' ?></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div class="settings-toggle-copy">
                        <strong>Show on landing page</strong>
                        <span><?= ! empty($isGuessStartupVisible)
                            ? 'The game card is visible on the homepage.'
                            : 'The game card is hidden and game routes are blocked.' ?></span>
                    </div>

                    <label class="settings-switch" for="guessStartupVisible">
                        <input type="hidden" name="guessStartupVisible" value="0">
                        <input
                            id="guessStartupVisible"
                            type="checkbox"
                            name="guessStartupVisible"
                            value="1"
                            <?= ! empty($isGuessStartupVisible) ? 'checked' : '' ?>
                        >
                        <span class="settings-slider" aria-hidden="true"></span>
                        <span class="settings-switch-label"><?= ! empty($isGuessStartupVisible) ? 'ON' : 'OFF' ?></span>
                    </label>
                </div>

                <div class="settings-actions">
                    <button type="submit" class="btn btn-p">Save game settings</button>
                </div>
            </form>
        </div>
    </section>

    <section class="settings-group" aria-labelledby="integrations-title">
        <div class="settings-group-head">
            <p class="settings-kicker">Integrations</p>
            <h2 id="integrations-title">Email &amp; Security Integrations</h2>
            <p class="settings-copy">Quick status checks for email sending and form spam protection.</p>
        </div>

        <div class="settings-card">
            <div class="settings-health-list">
                <div class="settings-health-row">
                    <div>
                        <strong>Email sending</strong>
                        <span><?= esc((string) ($gmailStatus['description'] ?? 'Email sending needs setup before messages can be sent.')) ?></span>
                        <?php if (! empty($gmailStatus['detail'])): ?>
                            <em><?= esc((string) $gmailStatus['detail']) ?></em>
                        <?php endif; ?>
                    </div>
                    <span class="settings-health-badge is-<?= esc((string) ($gmailStatus['state'] ?? 'off')) ?>">
                        <?= esc((string) ($gmailStatus['label'] ?? 'Needs setup')) ?>
                    </span>
                </div>

                <div class="settings-health-row">
                    <div>
                        <strong>Spam protection</strong>
                        <span><?= esc((string) ($recaptchaStatus['description'] ?? 'Spam protection is off or missing a key.')) ?></span>
                    </div>
                    <span class="settings-health-badge is-<?= esc((string) ($recaptchaStatus['state'] ?? 'off')) ?>">
                        <?= esc((string) ($recaptchaStatus['label'] ?? 'Off')) ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <section class="settings-group" aria-labelledby="site-experience-title">
        <div class="settings-group-head">
            <p class="settings-kicker">Experience</p>
            <h2 id="site-experience-title">Homepage Experience</h2>
            <p class="settings-copy">Control the intro animation visitors see on the homepage.</p>
        </div>

        <div class="settings-card">
            <div class="settings-head">
                <p class="settings-kicker">Landing loader</p>
                <h3>Intro animation</h3>
                <p class="settings-copy">Show or skip the ASOG loader before the landing page.</p>
            </div>

            <form method="POST" action="<?= site_url('admin/settings/site-experience') ?>" class="settings-form" data-toggle-form>
                <?= csrf_field() ?>

                <div class="settings-toggle-row">
                    <div class="settings-toggle-copy">
                        <strong>Landing loader</strong>
                        <span><?= esc((string) ($loaderStatus['description'] ?? 'Runs once per browser session on the homepage.')) ?></span>
                    </div>

                    <label class="settings-switch" for="landingLoaderEnabled">
                        <input type="hidden" name="landingLoaderEnabled" value="0">
                        <input
                            id="landingLoaderEnabled"
                            type="checkbox"
                            name="landingLoaderEnabled"
                            value="1"
                            <?= $landingLoaderEnabled ? 'checked' : '' ?>
                        >
                        <span class="settings-slider" aria-hidden="true"></span>
                        <span class="settings-switch-label"><?= $landingLoaderEnabled ? 'ON' : 'OFF' ?></span>
                    </label>
                </div>

                <div class="settings-actions">
                    <button type="submit" class="btn btn-p">Save experience</button>
                </div>
            </form>
        </div>
    </section>
    <?php endif; ?>

    <section class="settings-group" aria-labelledby="google-account-settings-title">
        <div class="settings-group-head">
            <p class="settings-kicker">Your Account</p>
            <h2 id="google-account-settings-title">Account Security</h2>
            <p class="settings-copy">Manage your password and Google sign-in.</p>
        </div>

        <div class="settings-grid settings-grid-two">
            <?= view('admin/profile/_password_card') ?>
            <?= view('admin/profile/_google_account_card', ['admin' => $currentAdmin ?? []]) ?>
        </div>
    </section>
</div>

<?php if ($canManageSiteSettings): ?>
<script>
(() => {
    document.querySelectorAll('[data-toggle-form]').forEach((form) => {
        form.querySelectorAll('.settings-switch').forEach((switchEl) => {
            const checkbox = switchEl.querySelector('input[type="checkbox"]');
            const stateLabel = switchEl.querySelector('.settings-switch-label');
            if (!checkbox || !stateLabel) {
                return;
            }

            const updateLabel = () => {
                stateLabel.textContent = checkbox.checked ? 'ON' : 'OFF';
            };

            checkbox.addEventListener('change', updateLabel);
            updateLabel();
        });
    });
})();
</script>
<?php endif; ?>
