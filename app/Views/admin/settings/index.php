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
    'description' => 'No application timeline is currently configured.',
    'state' => 'open',
];
$gmailStatus = $gmailStatus ?? [
    'label' => 'Not configured',
    'description' => 'Site email delivery is not fully configured.',
    'state' => 'off',
    'detail' => '',
];
$recaptchaStatus = $recaptchaStatus ?? [
    'label' => 'Disabled',
    'description' => 'Public form spam protection is disabled or incomplete.',
    'state' => 'off',
];
$loaderStatus = $loaderStatus ?? [
    'label' => 'Enabled',
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
            <p class="settings-copy">Control the public submission window and applicant email reuse behavior.</p>
        </div>

        <div class="settings-card">
            <div class="settings-head">
                <p class="settings-kicker">Application Window</p>
                <h3>Submission Access</h3>
                <p class="settings-copy">Set when new applications can be submitted from the public form.</p>
            </div>

            <form method="POST" action="<?= site_url('admin/settings/applications') ?>" class="settings-form" data-toggle-form>
                <?= csrf_field() ?>

                <div class="settings-notice settings-notice-<?= esc((string) ($windowStatus['state'] ?? 'open')) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h.01"></path>
                    </svg>
                    <div>
                        <strong><span>Form access status:</span> <?= esc((string) ($windowStatus['label'] ?? 'Always open')) ?></strong>
                        <p><?= esc((string) ($windowStatus['description'] ?? 'No application timeline is currently configured.')) ?></p>
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
                        <strong>Allow Duplicate Applicant Emails</strong>
                        <span><?= $allowDuplicateEmails
                            ? 'Applicants can submit more than once with the same email address.'
                            : 'Applicants will see an email-specific error if that address was already used before.' ?></span>
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
                        <strong>Show Application Dates</strong>
                        <span><?= $showApplicationDeadline
                            ? 'The public Apply overview can show the configured start date or end date.'
                            : 'The public Apply overview hides configured application dates.' ?></span>
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
                    <button type="submit" class="btn btn-p">Save Application Settings</button>
                </div>
            </form>
        </div>
    </section>

    <section class="settings-group" aria-labelledby="landing-display-title">
        <div class="settings-group-head">
            <p class="settings-kicker">Public Website</p>
            <h2 id="landing-display-title">Landing Page Display</h2>
            <p class="settings-copy">Tune which public sections and incubatee cohorts are visible.</p>
        </div>

        <div class="settings-grid settings-grid-two">
            <div class="settings-card">
                <div class="settings-head">
                    <p class="settings-kicker">Homepage Incubatees</p>
                    <h3>Display Filter</h3>
                    <p class="settings-copy">Choose one cohort or all cohorts for the landing section.</p>
                </div>

                <form method="POST" action="<?= site_url('admin/settings/homepage-incubatees-filter') ?>" class="settings-form">
                    <?= csrf_field() ?>
                    <div class="settings-control-row">
                        <div class="settings-control-copy">
                            <strong>Landing Cohort</strong>
                            <span>Controls which published incubatees appear on the public homepage.</span>
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
                    <h3>Interns Section Visibility</h3>
                    <p class="settings-copy">Use this switch to show or hide the interns section on the public Organization page.</p>
                </div>

                <form method="POST" action="<?= site_url('admin/settings/interns-visibility') ?>" class="settings-form" data-toggle-form>
                    <?= csrf_field() ?>

                    <div class="settings-toggle-row">
                        <div class="settings-toggle-copy">
                            <strong>Show Interns Section</strong>
                            <span><?= ! empty($showInternsSection) ? 'Currently visible on the Organization page' : 'Currently hidden on the Organization page' ?></span>
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
            <p class="settings-copy">Manage the Guess the Startup experience without touching the game content.</p>
        </div>

        <div class="settings-card">
            <div class="settings-head">
                <p class="settings-kicker">Landing & Route Control</p>
                <h3>Guess The Startup</h3>
                <p class="settings-copy">Control whether the game is visible on the landing page and whether visitors can start a new round.</p>
            </div>

            <form method="POST" action="<?= site_url('admin/settings/guess-startup/availability') ?>" class="settings-form" data-toggle-form>
                <?= csrf_field() ?>

                <div class="settings-toggle-row">
                    <div class="settings-toggle-copy">
                        <strong>Game Availability</strong>
                        <span><?= ! empty($isGuessStartupEnabled) ? 'Currently available to visitors' : 'Currently unavailable to visitors' ?></span>
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
                        <strong>Show On Landing Page</strong>
                        <span><?= ! empty($isGuessStartupVisible)
                            ? 'Game card and public routes are currently accessible.'
                            : 'Game card is hidden and public game routes are blocked.' ?></span>
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
                    <button type="submit" class="btn btn-p">Save Game Settings</button>
                </div>
            </form>
        </div>
    </section>

    <section class="settings-group" aria-labelledby="integrations-title">
        <div class="settings-group-head">
            <p class="settings-kicker">Integrations</p>
            <h2 id="integrations-title">Email &amp; Security Integrations</h2>
            <p class="settings-copy">A quick, non-sensitive health check for public form and email services.</p>
        </div>

        <div class="settings-card">
            <div class="settings-health-list">
                <div class="settings-health-row">
                    <div>
                        <strong>Gmail API</strong>
                        <span><?= esc((string) ($gmailStatus['description'] ?? 'Site email delivery is not fully configured.')) ?></span>
                        <?php if (! empty($gmailStatus['detail'])): ?>
                            <em><?= esc((string) $gmailStatus['detail']) ?></em>
                        <?php endif; ?>
                    </div>
                    <span class="settings-health-badge is-<?= esc((string) ($gmailStatus['state'] ?? 'off')) ?>">
                        <?= esc((string) ($gmailStatus['label'] ?? 'Not configured')) ?>
                    </span>
                </div>

                <div class="settings-health-row">
                    <div>
                        <strong>reCAPTCHA</strong>
                        <span><?= esc((string) ($recaptchaStatus['description'] ?? 'Public form spam protection is disabled or incomplete.')) ?></span>
                    </div>
                    <span class="settings-health-badge is-<?= esc((string) ($recaptchaStatus['state'] ?? 'off')) ?>">
                        <?= esc((string) ($recaptchaStatus['label'] ?? 'Disabled')) ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <section class="settings-group" aria-labelledby="site-experience-title">
        <div class="settings-group-head">
            <p class="settings-kicker">Experience</p>
            <h2 id="site-experience-title">Site Experience</h2>
            <p class="settings-copy">Read-only status for visitor-facing presentation features.</p>
        </div>

        <div class="settings-card">
            <div class="settings-head">
                <p class="settings-kicker">Homepage Intro</p>
                <h3>Landing Loader Animation</h3>
                <p class="settings-copy">Control whether visitors see the ASOG loader animation before the landing page.</p>
            </div>

            <form method="POST" action="<?= site_url('admin/settings/site-experience') ?>" class="settings-form" data-toggle-form>
                <?= csrf_field() ?>

                <div class="settings-toggle-row">
                    <div class="settings-toggle-copy">
                        <strong>Landing Loader</strong>
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
                    <button type="submit" class="btn btn-p">Save Experience</button>
                </div>
            </form>
        </div>
    </section>
    <?php endif; ?>

    <section class="settings-group" aria-labelledby="google-account-settings-title">
        <div class="settings-group-head">
            <p class="settings-kicker">Your Account</p>
            <h2 id="google-account-settings-title">Google Sign-In</h2>
            <p class="settings-copy">Manage the Google account connected to your own login.</p>
        </div>

        <?= view('admin/profile/_google_account_card', ['admin' => $currentAdmin ?? []]) ?>
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
