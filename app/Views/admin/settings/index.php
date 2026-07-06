<link rel="stylesheet" href="<?= base_url('assets/css/adminSettings.css') ?>">

<?php
$duplicateEmailSetting = old('allowDuplicateEmails');
$allowDuplicateEmails = $duplicateEmailSetting !== null
    ? $duplicateEmailSetting === '1'
    : ! empty($allowDuplicateEmails);
$applicationStartDate = old('applicationStartDate', $applicationStartDate ?? '');
$applicationEndDate = old('applicationEndDate', $applicationEndDate ?? '');
$windowStatus = $applicationWindowStatus ?? [
    'label' => 'Always open',
    'description' => 'No application timeline is currently configured.',
    'state' => 'open',
];
?>

<div class="settings-stack">
    <div class="settings-card">
        <div class="settings-head">
            <p class="settings-kicker">Application Control</p>
            <h2>Application Settings</h2>
            <p class="settings-copy">Set when new public applications can be submitted and control whether applicants can reuse an email address.</p>
        </div>

        <form method="POST" action="<?= site_url('admin/settings/applications') ?>" class="settings-form">
            <?= csrf_field() ?>

            <div class="settings-status-row settings-status-<?= esc((string) ($windowStatus['state'] ?? 'open')) ?>">
                <div>
                    <strong><?= esc((string) ($windowStatus['label'] ?? 'Always open')) ?></strong>
                    <span><?= esc((string) ($windowStatus['description'] ?? 'No application timeline is currently configured.')) ?></span>
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

            <div class="settings-actions">
                <button type="submit" class="btn btn-p">Save Application Settings</button>
            </div>
        </form>
    </div>

    <div class="settings-card">
        <div class="settings-head">
            <p class="settings-kicker">Landing & Gameplay Control</p>
            <h2>Guess The Startup Availability</h2>
            <p class="settings-copy">Control whether the game is visible on the landing page and whether visitors can start a new round.</p>
        </div>

        <form method="POST" action="<?= site_url('admin/settings/guess-startup/availability') ?>" class="settings-form" data-toggle-form>
            <?= csrf_field() ?>

            <div class="settings-toggle-row">
                <div class="settings-toggle-copy">
                    <strong>Guess The Startup</strong>
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

    <div class="settings-card">
        <div class="settings-head">
            <p class="settings-kicker">Organization Page Control</p>
            <h2>Interns Section Visibility</h2>
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

    <div class="settings-card">
        <div class="settings-head">
            <p class="settings-kicker">Homepage Incubatees</p>
            <h2>Display Filter</h2>
            <p class="settings-copy">Choose one cohort or all cohorts for the landing section. If the selected cohort has no published startups yet, the site shows "Will be announced soon".</p>
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
</div>

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
