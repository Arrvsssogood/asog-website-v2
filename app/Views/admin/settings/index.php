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
$leanCanvasTemplate = $leanCanvasTemplate ?? [
    'path'  => '',
    'url'   => '',
    'name'  => '',
    'mime'  => '',
    'isPdf' => false,
];
$leanCanvasTemplateName = trim((string) ($leanCanvasTemplate['name'] ?? ''));
$hasLeanCanvasTemplate = $leanCanvasTemplateName !== '';
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

        <div class="settings-card settings-template-card" id="leanCanvasTemplateCard"
             data-preview-url="<?= esc(site_url('admin/settings/lean-canvas-template/preview'), 'attr') ?>"
             data-delete-url="<?= esc(site_url('admin/settings/lean-canvas-template/delete'), 'attr') ?>"
             data-has-template="<?= $hasLeanCanvasTemplate ? '1' : '0' ?>">
            <div class="settings-head">
                <p class="settings-kicker">Lean Canvas Template</p>
                <h3>Application Template</h3>
                <p class="settings-copy">Manage the Lean Canvas template that applicants download from the public Apply form. Accepted formats: PDF or Word (.doc, .docx), up to 10&nbsp;MB.</p>
            </div>

                <div class="settings-template-row">
                    <div class="settings-template-info">
                        <strong>Current Template</strong>
                        <span class="settings-template-status">
                            <?php if ($hasLeanCanvasTemplate): ?>
                                <span class="settings-template-name" title="<?= esc($leanCanvasTemplateName, 'attr') ?>">
                                    <svg class="settings-template-file-icon" fill="currentColor" viewBox="0 0 20 20"><path d="M4 2a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V8l-6-6H4zm7 1.5L16.5 9H12a1 1 0 01-1-1V3.5z"/></svg>
                                    <?= esc($leanCanvasTemplateName) ?>
                                </span>
                            <?php else: ?>
                                <span class="settings-template-empty">No template uploaded &mdash; applicants will not see a downloadable template on the Apply form.</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="settings-template-actions">
                        <button type="button" class="btn btn-o" id="leanCanvasPreviewBtn" <?= $hasLeanCanvasTemplate ? '' : 'disabled' ?>>
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Preview
                        </button>
                        <button type="button" class="settings-template-delete-btn" id="leanCanvasDeleteBtn" <?= $hasLeanCanvasTemplate ? '' : 'disabled' ?>
                            data-confirm-title="Delete Lean Canvas template?"
                            data-confirm-message="Applicants will no longer be able to download the template from the Apply form until a new one is uploaded. This action cannot be undone.">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            Delete
                        </button>
                    </div>
                </div>

                <form method="POST" action="<?= site_url('admin/settings/lean-canvas-template') ?>" enctype="multipart/form-data" class="settings-form settings-template-form" id="leanCanvasTemplateForm">
                    <?= csrf_field() ?>
                    <div class="settings-template-upload">
                        <div class="settings-template-chooser">
                            <button type="button" class="file-upload-button" id="leanCanvasChooseBtn">Choose File</button>
                            <span class="settings-template-file-status" id="leanCanvasFileStatus"><?= $hasLeanCanvasTemplate ? esc($leanCanvasTemplateName) : 'No file chosen' ?></span>
                            <input id="leanCanvasTemplateFile" type="file" name="leanCanvasTemplate"
                                accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                class="hidden" style="display:none;position:absolute;opacity:0;width:0;height:0;pointer-events:none">
                        </div>
                        <div class="settings-actions">
                            <button type="submit" class="btn btn-p" id="leanCanvasTemplateSubmit" disabled>Upload Template</button>
                        </div>
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
<div id="leanCanvasPreviewModal" class="lc-modal" aria-hidden="true">
    <div class="lc-modal-backdrop" data-lean-canvas-preview-close></div>
    <div class="lc-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="leanCanvasPreviewTitle">
        <div class="lc-modal-header">
            <div>
                <span class="lc-modal-header-kicker">Lean Canvas</span>
                <h2 id="leanCanvasPreviewTitle" class="lc-modal-header-title">Template Preview</h2>
            </div>
            <button type="button" class="lc-modal-close" data-lean-canvas-preview-close aria-label="Close preview">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="lc-modal-body" id="leanCanvasPreviewBody">
            <div class="lc-modal-loading">Loading template&hellip;</div>
        </div>
        <div class="lc-modal-footer" id="leanCanvasPreviewFoot" hidden>
            <a class="lc-modal-download" id="leanCanvasPreviewDownload" href="#" download>Download</a>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js" defer></script>
<script src="<?= base_url('assets/js/admin/settings/leanCanvas.js') ?>" defer></script>
<?php endif; ?>
