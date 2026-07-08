<?php
/**
 * Incubatee form — shared for Create and Edit.
 *
 * Variables:
 *   $pageTitle  — "New Incubatee" or "Edit Incubatee"
 *   $incubatee  — (edit only) associative array of current values
 *   $activePage — always "incubatees"
 */

$isEdit  = isset($incubatee);
$formUrl = $isEdit
    ? site_url('admin/incubatees/' . $incubatee['id'] . '/update')
    : site_url('admin/incubatees');

$contactRows = [];

if ($isEdit && ! empty($incubatee['contactDetails'])) {
    $decodedContacts = json_decode((string) $incubatee['contactDetails'], true);
    if (is_array($decodedContacts)) {
        foreach ($decodedContacts as $contact) {
            if (! is_array($contact)) {
                continue;
            }

            $contactRows[] = [
                'person' => trim((string) ($contact['person'] ?? $contact['name'] ?? '')),
                'number' => trim((string) ($contact['number'] ?? $contact['phone'] ?? '')),
                'email'  => trim((string) ($contact['email'] ?? '')),
            ];
        }
    }
}

$oldPersons = old('contact_person');
$oldNumbers = old('contact_number');
$oldEmails  = old('contact_email');

if (is_array($oldPersons) || is_array($oldNumbers) || is_array($oldEmails)) {
    $oldPersons = is_array($oldPersons) ? $oldPersons : [];
    $oldNumbers = is_array($oldNumbers) ? $oldNumbers : [];
    $oldEmails  = is_array($oldEmails) ? $oldEmails : [];

    $contactRows = [];
    $maxOldRows = max(count($oldPersons), count($oldNumbers), count($oldEmails));
    for ($i = 0; $i < $maxOldRows; $i++) {
        $contactRows[] = [
            'person' => trim((string) ($oldPersons[$i] ?? '')),
            'number' => trim((string) ($oldNumbers[$i] ?? '')),
            'email'  => trim((string) ($oldEmails[$i] ?? '')),
        ];
    }
}

if (empty($contactRows)) {
    $fallbackPerson = old('contactName', $isEdit ? ($incubatee['contactName'] ?? '') : '');
    $fallbackNumber = old('contactNumber', $isEdit ? ($incubatee['contactNumber'] ?? '') : '');
    $fallbackEmail  = old('contactEmail', $isEdit ? ($incubatee['contactEmail'] ?? '') : '');

    if ($fallbackPerson !== '' || $fallbackNumber !== '' || $fallbackEmail !== '') {
        $contactRows[] = [
            'person' => (string) $fallbackPerson,
            'number' => (string) $fallbackNumber,
            'email'  => (string) $fallbackEmail,
        ];
    }
}

if (empty($contactRows)) {
    $contactRows[] = ['person' => '', 'number' => '', 'email' => ''];
}

$selectedSdgs = old('sdgNumbers');
if (! is_array($selectedSdgs)) {
    $rawSelectedSdgs = $isEdit ? ($incubatee['sdgNumbers'] ?? '') : '';
    $selectedSdgs = $rawSelectedSdgs !== '' ? explode(',', (string) $rawSelectedSdgs) : [];
}
$selectedSdgs = array_map('intval', $selectedSdgs);

$sdgTitles = [
    1  => 'No Poverty',
    2  => 'Zero Hunger',
    3  => 'Good Health and Well-Being',
    4  => 'Quality Education',
    5  => 'Gender Equality',
    6  => 'Clean Water and Sanitation',
    7  => 'Affordable and Clean Energy',
    8  => 'Decent Work and Economic Growth',
    9  => 'Industry, Innovation and Infrastructure',
    10 => 'Reduced Inequalities',
    11 => 'Sustainable Cities and Communities',
    12 => 'Responsible Consumption and Production',
    13 => 'Climate Action',
    14 => 'Life Below Water',
    15 => 'Life on Land',
    16 => 'Peace, Justice and Strong Institutions',
    17 => 'Partnerships for the Goals',
];
?>

<style>
.form-card {
    background: #fff;
    border: 1px solid #eceae6;
    border-radius: .4rem;
    padding: 1.4rem
}

.form-grid {
    display: grid;
    gap: 1rem
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem
}

.form-row-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1rem
}

.field label {
    display: block;
    font-size: .62rem;
    font-weight: 600;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: .3rem
}

.field input[type=text],
.field input[type=url],
.field input[type=number],
.field textarea,
.field select {
    width: 100%;
    font-family: 'DM Sans', sans-serif;
    font-size: .82rem;
    color: #1e293b;
    padding: .5rem .65rem;
    border: 1px solid #e4e2dd;
    border-radius: .3rem;
    background: #fff;
    outline: none;
    transition: border .15s
}

.field select {
    padding-right: 2rem;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%2394a3b8' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M4 6l4 4 4-4'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right .65rem center;
    background-size: 14px 14px;
    cursor: pointer
}

.field input:focus,
.field textarea:focus,
.field select:focus {
    border-color: #03558C
}

.field textarea {
    resize: vertical;
    min-height: 70px
}

.sdg-select-grid {
    display: grid;
    grid-template-columns: repeat(10, 1fr);
    gap: .5rem;
    max-width: 1000px;
}

.sdg-check {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid transparent;
    border-radius: .35rem;
    padding: .25rem;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s;
}

.sdg-check img {
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: .25rem;
    display: block;
    opacity: .3;
    transition: opacity .15s;
}

.sdg-check:hover img {
    opacity: .8;
}

.sdg-check:has(input:checked) img {
    opacity: 1;
}

@media (max-width: 800px) {
    .sdg-select-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}

.sdg-check:hover {
    border-color: #03558C
}

.sdg-check:has(input:checked) {
    border-color: #03558C;
    box-shadow: 0 0 0 2px rgba(3,85,140,.15);
}

.sdg-check input {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.sdg-tooltip {
    position: absolute;
    bottom: calc(100% + 6px);
    left: 50%;
    transform: translateX(-50%);
    background: #4a5a73;
    color: #f1f5f9;
    font-size: .6rem;
    font-weight: 600;
    white-space: nowrap;
    padding: .3rem .55rem;
    border-radius: .25rem;
    opacity: 0;
    pointer-events: none;
    transition: opacity .15s;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
}

.sdg-check:focus-within {
    outline: 2px solid #03558C;
    outline-offset: 2px;
}

.sdg-check:hover .sdg-tooltip,
 .sdg-check:has(input:focus-visible) .sdg-tooltip {
     opacity: 1;
 }

.sdg-help {
    margin-top: .45rem;
    font-size: .62rem;
    color: #94a3b8
}

.editor-wrap {
    border: 1px solid #ddd;
    border-radius: .25rem;
    overflow: hidden
}

.editor-wrap .ql-toolbar {
    border: none;
    border-bottom: 1px solid #eee;
    background: #fafaf9
}

.editor-wrap .ql-container {
    border: none;
    font-family: 'DM Sans', sans-serif;
    font-size: .82rem;
    min-height: 200px
}

.upload-zone {
    border: 1.5px dashed #d4d0ca;
    border-radius: .35rem;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: border-color .15s, background .15s;
    position: relative
}

.upload-zone:hover {
    border-color: #03558C;
    background: #fafcff
}

.upload-zone input[type=file] {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0
}

.upload-zone .label {
    font-size: .78rem;
    color: #94a3b8
}

.upload-zone .label strong {
    color: #03558C
}

.upload-zone.upload-zone-white {
    background: #03355a;
    border: 1.5px solid transparent;
    background-image:
        linear-gradient(#03355a, #03355a),
        repeating-linear-gradient(
            135deg,
            rgba(255,255,255,.42) 0 8px,
            rgba(127,179,209,.48) 8px 16px
        );
    background-origin: border-box;
    background-clip: padding-box, border-box
}

.upload-zone.upload-zone-white:hover {
    background-image:
        linear-gradient(#044066, #044066),
        repeating-linear-gradient(
            135deg,
            rgba(255,255,255,.6) 0 8px,
            rgba(147,197,253,.7) 8px 16px
        )
}

.upload-zone.upload-zone-white .label {
    color: rgba(255,255,255,.72)
}

.upload-zone.upload-zone-white .label strong {
    color: #fff
}

.upload-preview {
    margin-top: .6rem
}

.upload-preview img {
    max-height: 140px;
    max-width: 100%;
    border-radius: .3rem;
    border: 1px solid #eceae6
}

.upload-preview.upload-preview-white {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #03355a;
    padding: .5rem;
    border-radius: .3rem;
    border: 1px solid rgba(255,255,255,.16)
}

.upload-preview.upload-preview-white img {
    border: 0;
    background: transparent;
}

.upload-preview-white-logo {
    filter: brightness(0) invert(1)
}

.upload-help {
    margin-top: .35rem;
    font-size: .62rem;
    line-height: 1.4;
    color: #94a3b8
}

.upload-zone.upload-zone-white + .upload-help {
    margin-top: .55rem
}

.upload-help.is-error {
    color: #b91c1c
}

.switch-row {
    display: flex;
    gap: 1.5rem;
    align-items: center;
    margin-top: .25rem
}

/* Founders repeater */
.tm-section {
    margin-top: .25rem
}

.tm-help-stack {
    display: flex;
    flex-direction: column;
    gap: .3rem;
    margin-bottom: .85rem
}

.tm-help-stack .upload-help {
    margin-top: 0
}

.tm-section .section-label {
    font-size: .62rem;
    font-weight: 600;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: .5rem;
    display: block
}

.tm-rows {
    display: flex;
    flex-direction: column;
    gap: .45rem
}

.tm-row {
    display: grid;
    grid-template-columns: 92px 1fr 1fr 32px;
    gap: .5rem;
    align-items: start
}

.tm-row input {
    font-family: 'DM Sans', sans-serif;
    font-size: .82rem;
    color: #1e293b;
    padding: .45rem .6rem;
    border: 1px solid #ddd;
    border-radius: .25rem;
    background: #fff;
    outline: none;
    transition: border .15s
}

.tm-row input:focus {
    border-color: #03558C
}

.tm-photo-zone {
    border: 1px dashed #d4d0ca;
    border-radius: .35rem;
    background: #fff;
    width: 92px;
    min-height: 92px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    cursor: pointer;
    overflow: hidden;
}

.tm-photo-zone.is-dragover {
    border-color: #03558C;
    background: #f0f9ff;
}

.tm-photo-zone input[type=file] {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}

.tm-photo-preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.tm-photo-placeholder {
    font-size: .6rem;
    font-weight: 600;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #94a3b8;
    text-align: center;
    line-height: 1.35;
    padding: .35rem;
}

.tm-row .tm-remove {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 1px solid #e4e2dd;
    background: #fff;
    color: #94a3b8;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .15s;
    flex-shrink: 0
}

.tm-row .tm-remove:hover {
    border-color: #ef4444;
    color: #ef4444;
    background: #fef2f2
}

.tm-add {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .68rem;
    font-weight: 600;
    color: #03558C;
    background: none;
    border: 1px dashed #ccc;
    border-radius: .25rem;
    padding: .4rem .8rem;
    cursor: pointer;
    margin-top: .4rem;
    transition: all .15s
}

.tm-add:hover {
    border-color: #03558C;
    background: #f0f9ff
}

/* Contacts repeater */
.contact-section {
    margin-top: .25rem
}

.contact-section .section-label {
    font-size: .62rem;
    font-weight: 600;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: .5rem;
    display: block
}

.contact-rows {
    display: flex;
    flex-direction: column;
    gap: .45rem
}

.contact-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 32px;
    gap: .5rem;
    align-items: center
}

.contact-row input {
    font-family: 'DM Sans', sans-serif;
    font-size: .82rem;
    color: #1e293b;
    padding: .45rem .6rem;
    border: 1px solid #ddd;
    border-radius: .25rem;
    background: #fff;
    outline: none;
    transition: border .15s
}

.contact-row input:focus {
    border-color: #03558C
}

.contact-remove {
    width: 32px;
    height: 32px;
    border: 1px solid #e8e5df;
    border-radius: .25rem;
    background: #fff;
    color: #94a3b8;
    font-size: 1rem;
    line-height: 1;
    cursor: pointer;
    transition: all .15s
}

.contact-remove:hover {
    border-color: #ef4444;
    color: #ef4444
}

.contact-add {
    margin-top: .45rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .3rem;
    padding: .38rem .68rem;
    border-radius: .25rem;
    border: 1px dashed #cfd8e3;
    background: #fff;
    color: #64748b;
    font-size: .68rem;
    font-weight: 600;
    letter-spacing: .03em;
    cursor: pointer;
    transition: all .15s
}

.contact-add:hover {
    border-color: #03558C;
    color: #03558C
}

@media (max-width: 900px) {
    .tm-row {
        grid-template-columns: 1fr;
    }

    .contact-row {
        grid-template-columns: 1fr;
    }

    .contact-remove {
        margin-left: auto;
    }

    .tm-photo-zone {
        width: min(180px, 100%);
        min-height: auto;
        aspect-ratio: 1 / 1;
    }

    .tm-row .tm-remove {
        margin-left: auto;
    }
}

.switch {
    display: flex;
    align-items: center;
    gap: .45rem;
    cursor: pointer;
    font-size: .78rem;
    color: #334155
}

.switch input {
    display: none
}

.switch .track {
    width: 32px;
    height: 18px;
    border-radius: 9px;
    background: #d4d0ca;
    position: relative;
    transition: background .2s
}

.switch input:checked+.track {
    background: #03558C
}

.switch .track::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #fff;
    transition: transform .2s
}

.switch input:checked+.track::after {
    transform: translateX(14px)
}

.form-actions {
    display: flex;
    gap: .55rem;
    justify-content: flex-end;
    align-items: center;
    margin-top: 1rem;
    padding-top: .8rem;
    border-top: 1px solid #eceae6
}

.form-actions .btn-p,
.form-actions .btn-o {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .68rem;
    font-weight: 600;
    padding: .55rem 1.1rem;
    border-radius: .3rem;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: all .15s
}

.form-actions .btn-p {
    background: #03558C;
    color: #fff
}

.form-actions .btn-p:hover {
    background: #024a7a
}

.form-actions .btn-o {
    background: #fff;
    color: #64748b;
    border: 1px solid #e4e2dd
}

.form-actions .btn-o:hover {
    border-color: #03558C;
    color: #03558C
}
</style>

<form action="<?= $formUrl ?>" method="POST" enctype="multipart/form-data" id="incubateeForm" data-dirty-check data-dirty-btn=".btn-p">
    <?= csrf_field() ?>

    <div class="form-card">
        <div class="form-grid">

            <!-- Company Name -->
            <div class="field">
                <label for="companyName">Company Name</label>
                <input type="text" id="companyName" name="companyName"
                    value="<?= esc(old('companyName', $isEdit ? $incubatee['companyName'] : '')) ?>" required
                    placeholder="Startup or company name">
            </div>

            <!-- Cohort -->
            <div class="form-row">
                <div class="field">
                    <label for="cohortSelect">Cohort</label>
                    <?php
                        $cohorts = $existingCohorts ?? [];
                        $currentCohort = old('cohort', $isEdit ? $incubatee['cohort'] : '');
                    ?>
                    <div style="display:flex;gap:.4rem;align-items:start">
                        <div style="flex:1">
                            <select id="cohortSelect" onchange="onCohortChange(this)">
                                <option value="">— Select Cohort —</option>
                                <?php foreach ($cohorts as $c): ?>
                                    <option value="<?= esc($c) ?>" <?= $currentCohort === $c ? 'selected' : '' ?>><?= esc($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" onclick="openCohortModal()" style="margin-top:2px;background:#03558C;color:#fff;border:none;border-radius:.3rem;padding:.48rem .7rem;cursor:pointer;font-size:.68rem;font-weight:600;display:inline-flex;align-items:center;gap:.3rem;white-space:nowrap;transition:background .15s" onmouseover="this.style.background='#024a7a'" onmouseout="this.style.background='#03558C'">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                            Add
                        </button>
                    </div>
                    <input type="hidden" id="cohortHidden" name="cohort" value="<?= esc($currentCohort) ?>">
                </div>
                <div class="field"></div>
            </div>

            <!-- Website + Facebook -->
            <div class="form-row">
                <div class="field">
                    <label for="websiteUrl">Website URL</label>
                    <input type="url" id="websiteUrl" name="websiteUrl"
                        value="<?= esc(old('websiteUrl', $isEdit ? $incubatee['websiteUrl'] : '')) ?>"
                        placeholder="https://example.com">
                </div>
                <div class="field">
                    <label for="facebookUrl">Facebook Page URL</label>
                    <input type="url" id="facebookUrl" name="facebookUrl"
                        value="<?= esc(old('facebookUrl', $isEdit ? ($incubatee['facebookUrl'] ?? '') : '')) ?>"
                        placeholder="https://facebook.com/yourpage">
                </div>
            </div>

            <!-- Startup contacts -->
            <div class="contact-section">
                <span class="section-label">Contacts</span>
                <div class="contact-rows" id="contactRows">
                    <?php foreach ($contactRows as $contact): ?>
                    <div class="contact-row">
                        <input type="text" name="contact_person[]" value="<?= esc($contact['person'] ?? '') ?>" placeholder="Contact person">
                        <input type="text" name="contact_number[]" value="<?= esc($contact['number'] ?? '') ?>" placeholder="Number">
                        <input type="text" name="contact_email[]" value="<?= esc($contact['email'] ?? '') ?>" placeholder="Email">
                        <button type="button" class="contact-remove" title="Remove">×</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="contact-add" id="contactAdd">+ Add contact</button>
            </div>

            <!-- SDGs -->
            <div class="field">
                <label>SDGs</label>
                <div class="sdg-select-grid">
                    <?php for ($sdgId = 1; $sdgId <= 17; $sdgId++): ?>
                    <?php $sdgNum = str_pad((string) $sdgId, 2, '0', STR_PAD_LEFT); ?>
                    <label class="sdg-check">
                        <input
                            type="checkbox"
                            name="sdgNumbers[]"
                            value="<?= $sdgId ?>"
                            <?= in_array($sdgId, $selectedSdgs, true) ? 'checked' : '' ?>
                        >
                        <img src="<?= base_url('assets/img/sdg/sdg-' . $sdgNum . '.webp') ?>" alt="SDG <?= $sdgId ?> — <?= esc($sdgTitles[$sdgId] ?? '') ?>">
                        <span class="sdg-tooltip">SDG <?= $sdgId ?> — <?= esc($sdgTitles[$sdgId] ?? '') ?></span>
                    </label>
                    <?php endfor; ?>
                </div>
                <p class="sdg-help">Choose SDG goals for this incubatee. They will appear as clickable SDG squares in the incubatee detail panel.</p>
            </div>

            <!-- Sort order -->
            <div class="form-row">
                <div class="field">
                    <label for="sortOrder">Sort Order</label>
                    <input type="number" id="sortOrder" name="sortOrder"
                        value="<?= esc(old('sortOrder', $isEdit ? $incubatee['sortOrder'] : 0)) ?>" min="0"
                        placeholder="0">
                </div>
                <div class="field"></div>
            </div>

            <!-- Content (Quill) -->
            <div class="field">
                <label>Full Description</label>
                <div class="editor-wrap">
                    <div class="quill-editor"><?= old('content', $isEdit ? $incubatee['content'] : '') ?></div>
                    <input type="hidden" name="content" class="quill-content"
                        value="<?= esc(old('content', $isEdit ? $incubatee['content'] : '')) ?>">
                </div>
            </div>

            <!-- Logo upload -->
            <div class="field">
                <label>Company Logo</label>
                <div class="upload-zone" id="uploadZone">
                    <input type="file" name="logo" id="logoInput" accept="image/*" data-max-bytes="<?= esc((string) ($logoUploadMaxBytes ?? 1048576)) ?>" data-max-label="<?= esc($logoUploadMaxLabel ?? '1 MB') ?>">
                    <div class="label" id="uploadLabel"><strong>Click to upload</strong> or drag a logo here</div>
                    <div class="upload-preview" id="uploadPreview">
                        <?php if ($isEdit && ! empty($incubatee['logoPath'])): ?>
                        <img src="<?= site_url($incubatee['logoPath']) ?>" alt="">
                        <?php endif; ?>
                    </div>
                </div>
                <p class="upload-help" id="logoUploadHelp">
                    Allowed: PNG, JPG, GIF, WEBP.<br>
                    Best results: a clean, centered logo with enough padding around the mark so it does not feel cramped in the card.<br>
                    Transparent PNG or WEBP is preferred for logos with cutouts or irregular shapes. Keep the file under <?= esc($logoUploadMaxLabel ?? '1 MB') ?>.
                </p>
            </div>

            <!-- White Logo upload (for big card) -->
            <div class="field">
                <label>White Logo <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#b0aaa0">(used on the navy card)</span></label>
                <div class="upload-zone upload-zone-white" id="uploadZoneWhite">
                    <input type="file" name="logoWhite" id="logoWhiteInput" accept="image/*" data-max-bytes="<?= esc((string) ($logoUploadMaxBytes ?? 1048576)) ?>" data-max-label="<?= esc($logoUploadMaxLabel ?? '1 MB') ?>">
                    <div class="label" id="uploadLabelWhite"><strong>Click to upload</strong> white version of the logo</div>
                    <div class="upload-preview upload-preview-white" id="uploadPreviewWhite">
                        <?php if ($isEdit && ! empty($incubatee['logoWhitePath'])): ?>
                        <img class="upload-preview-white-logo" src="<?= site_url($incubatee['logoWhitePath']) ?>" alt="">
                        <?php endif; ?>
                    </div>
                </div>
                <p class="upload-help" id="logoWhiteUploadHelp">
                    This is the version used on the navy card.<br>
                    Upload a white or light-colored logo if you have one. If you leave this blank, the site will fall back to the main logo and auto-invert it for the navy background.<br>
                    Transparent PNG or WEBP is ideal. Keep the file under <?= esc($logoUploadMaxLabel ?? '1 MB') ?>.
                </p>
            </div>

            <!-- Founders -->
            <?php
                $existingMembers = [];
                if ($isEdit && ! empty($incubatee['teamMembers'])) {
                    $existingMembers = json_decode($incubatee['teamMembers'], true) ?: [];
                }
            ?>
            <div class="tm-section">
                <span class="section-label">Founders</span>
                <div class="tm-help-stack">
                    <p class="upload-help">
                        Founder photos must be square (1:1). Please upload tightly cropped portraits that sit well inside the frame. Max <?= esc($teamPhotoUploadMaxLabel ?? '10 MB') ?> per photo.
                    </p>
                    <p class="upload-help" id="tmUploadHelp">Square founder photos under <?= esc($teamPhotoUploadMaxLabel ?? '10 MB') ?> work best for the team layout.</p>
                </div>
                <div class="tm-rows" id="tmRows">
                    <?php if (! empty($existingMembers)): ?>
                    <?php foreach ($existingMembers as $member): ?>
                    <div class="tm-row">
                        <label class="tm-photo-zone">
                            <input type="hidden" name="tm_photo_existing[]" value="<?= esc($member['photo'] ?? '') ?>">
                            <input type="file" name="tm_photo[]" class="tm-photo-input" accept="image/*" data-max-bytes="<?= esc((string) ($teamPhotoUploadMaxBytes ?? 10485760)) ?>" data-aspect="square">
                            <?php if (! empty($member['photo'])): ?>
                                <img class="tm-photo-preview" src="<?= site_url($member['photo']) ?>" alt="<?= esc($member['name'] ?? '') ?>">
                            <?php else: ?>
                                <span class="tm-photo-placeholder">Founder<br>Photo</span>
                            <?php endif; ?>
                        </label>
                        <input type="text" name="tm_name[]" value="<?= esc($member['name'] ?? '') ?>"
                            placeholder="Name">
                        <input type="text" name="tm_role[]" value="<?= esc($member['role'] ?? '') ?>"
                            placeholder="Founder title (e.g. CEO, CTO)">
                        <button type="button" class="tm-remove" title="Remove">×</button>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="tm-row">
                        <label class="tm-photo-zone">
                            <input type="hidden" name="tm_photo_existing[]" value="">
                            <input type="file" name="tm_photo[]" class="tm-photo-input" accept="image/*" data-max-bytes="<?= esc((string) ($teamPhotoUploadMaxBytes ?? 10485760)) ?>" data-aspect="square">
                            <span class="tm-photo-placeholder">Founder<br>Photo</span>
                        </label>
                        <input type="text" name="tm_name[]" placeholder="Name">
                        <input type="text" name="tm_role[]" placeholder="Founder title (e.g. CEO, CTO)">
                        <button type="button" class="tm-remove" title="Remove">×</button>
                    </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="tm-add" id="tmAdd">+ Add founder</button>
            </div>

            <!-- Toggle -->
            <div class="switch-row">
                <label class="switch">
                    <input type="checkbox" name="isPublished" value="1"
                        <?= old('isPublished', $isEdit ? $incubatee['isPublished'] : 0) ? 'checked' : '' ?>>
                    <span class="track"></span>
                    Publish
                </label>
            </div>

            <div class="form-actions">
                <a href="<?= site_url('admin/incubatees') ?>" class="btn-o">← Back to incubatees</a>
                <span style="flex:1"></span>
                <button type="submit" class="btn-p">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <?= $isEdit ? 'Save changes' : 'Add incubatee' ?>
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Cohort Manager Modal -->
<style>
.cfm-overlay{position:fixed;inset:0;z-index:900;background:rgba(2,13,24,.45);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;opacity:0;transition:opacity .2s}
.cfm-overlay.open{display:flex;opacity:1}
.cfm-modal{background:#fff;border-radius:.55rem;box-shadow:0 20px 60px rgba(0,0,0,.18);width:90%;max-width:480px;max-height:80vh;display:flex;flex-direction:column;transform:translateY(12px);transition:transform .25s ease}
.cfm-overlay.open .cfm-modal{transform:translateY(0)}
.cfm-head{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.3rem;border-bottom:1px solid #eceae6}
.cfm-head h3{font-size:.85rem;font-weight:700;color:#1e293b;margin:0}
.cfm-close{background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1.3rem;line-height:1;padding:.2rem;transition:color .15s}
.cfm-close:hover{color:#1e293b}
.cfm-body{padding:.8rem 1.3rem;overflow-y:auto;flex:1}
.cfm-foot{padding:.75rem 1.3rem;border-top:1px solid #eceae6;display:flex;justify-content:space-between;align-items:center}
.cfm-list{list-style:none;margin:0;padding:0}
.cfm-item{display:flex;align-items:center;justify-content:space-between;padding:.55rem .5rem;border-bottom:1px solid #f4f3f0;font-size:.78rem;color:#1e293b;transition:background .1s}
.cfm-item:last-child{border-bottom:none}
.cfm-item:hover{background:#fafaf9}
.cfm-item-name{font-weight:600;color:#03558C}
.cfm-item-info{font-size:.62rem;color:#94a3b8;margin-left:.5rem}
.cfm-item-tag{font-size:.5rem;font-weight:600;letter-spacing:.04em;text-transform:uppercase;padding:.12rem .35rem;border-radius:.15rem;margin-left:.5rem}
.cfm-tag-active{background:#ecfdf5;color:#065f46}
.cfm-tag-soon{background:#fef3c7;color:#92400e}
.cfm-item-del{background:none;border:none;cursor:pointer;color:#be123c70;padding:.15rem .25rem;transition:color .15s}
.cfm-item-del:hover{color:#be123c}
.cfm-item-del:disabled{opacity:.25;cursor:not-allowed}
.cfm-add-btn{display:inline-flex;align-items:center;gap:.3rem;background:#03558C;color:#fff;font-size:.66rem;font-weight:600;padding:.42rem .8rem;border-radius:.25rem;border:none;cursor:pointer;transition:background .15s}
.cfm-add-btn:hover{background:#024a7a}
.cfm-add-btn:disabled{background:#94a3b8;cursor:wait}
.cfm-empty{text-align:center;padding:1.2rem 0;color:#94a3b8;font-size:.78rem}
</style>

<div class="cfm-overlay" id="cfmOverlay" onclick="if(event.target===this)closeCohortModal()">
    <div class="cfm-modal">
        <div class="cfm-head">
            <h3>Manage Cohorts</h3>
            <button type="button" class="cfm-close" onclick="closeCohortModal()">×</button>
        </div>
        <div class="cfm-body">
            <ul class="cfm-list" id="cfmList">
                <?php
                    $allCohorts = $allCohorts ?? [];
                    $cohortStartupCounts = $cohortStartupCounts ?? [];
                ?>
                <?php foreach ($allCohorts as $ch): ?>
                <?php $cnt = (int) (($cohortStartupCounts[$ch['name']] ?? 0)); ?>
                <li class="cfm-item" data-id="<?= $ch['id'] ?>" data-name="<?= esc($ch['name']) ?>">
                    <div style="display:flex;align-items:center">
                        <span class="cfm-item-name"><?= esc($ch['name']) ?></span>
                        <span class="cfm-item-info"><?= $cnt ?> startup<?= $cnt !== 1 ? 's' : '' ?></span>
                        <?php if ($cnt > 0): ?>
                            <span class="cfm-item-tag cfm-tag-active">Active</span>
                        <?php else: ?>
                            <span class="cfm-item-tag cfm-tag-soon">Coming Soon</span>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="cfm-item-del" onclick="cfmDelete(<?= $ch['id'] ?>,'<?= esc($ch['name'], 'js') ?>')" <?= $cnt > 0 ? 'disabled title="Has incubatees — cannot delete"' : 'title="Delete"' ?>>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if (empty($allCohorts)): ?>
            <div class="cfm-empty" id="cfmEmpty">No cohorts yet. Add one below.</div>
            <?php endif; ?>
        </div>
        <div class="cfm-foot">
            <span style="font-size:.65rem;color:#94a3b8" id="cfmTotal"><?= count($allCohorts) ?> cohort<?= count($allCohorts) !== 1 ? 's' : '' ?></span>
            <button type="button" class="cfm-add-btn" id="cfmAddBtn" onclick="cfmAdd()">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Add Cohort
            </button>
        </div>
    </div>
</div>

<script>
/* ── Cohort select → hidden field sync ── */
function onCohortChange(sel) {
    document.getElementById('cohortHidden').value = sel.value;
}
// Init: sync hidden field with current select value
(function(){
    var sel = document.getElementById('cohortSelect');
    var hid = document.getElementById('cohortHidden');
    if (sel.value) hid.value = sel.value;
})();

/* ── Modal open / close ── */
function openCohortModal() {
    document.getElementById('cfmOverlay').classList.add('open');
}
function closeCohortModal() {
    document.getElementById('cfmOverlay').classList.remove('open');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeCohortModal();
});

var cfmCount = <?= count($allCohorts) ?>;

/* ── Add cohort via AJAX ── */
function cfmAdd() {
    var btn = document.getElementById('cfmAddBtn');
    btn.disabled = true;
    btn.textContent = 'Adding…';
    fetch('<?= site_url('admin/cohorts/add') ?>', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json'},
        body: JSON.stringify({})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok) {
            var c = data.cohort;
            // Remove empty state
            var empty = document.getElementById('cfmEmpty');
            if (empty) empty.remove();
            // Add to modal list
            var li = document.createElement('li');
            li.className = 'cfm-item';
            li.dataset.id = c.id;
            li.dataset.name = c.name;
            li.innerHTML =
                '<div style="display:flex;align-items:center">' +
                    '<span class="cfm-item-name">' + c.name + '</span>' +
                    '<span class="cfm-item-info">0 startups</span>' +
                    '<span class="cfm-item-tag cfm-tag-soon">Coming Soon</span>' +
                '</div>' +
                '<button type="button" class="cfm-item-del" title="Delete" onclick="cfmDelete(' + c.id + ',\'' + c.name + '\')">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>' +
                '</button>';
            document.getElementById('cfmList').appendChild(li);
            // Add to dropdown & select it
            var sel = document.getElementById('cohortSelect');
            var opt = document.createElement('option');
            opt.value = c.name;
            opt.textContent = c.name;
            opt.selected = true;
            sel.appendChild(opt);
            document.getElementById('cohortHidden').value = c.name;
            // Update count
            cfmCount++;
            document.getElementById('cfmTotal').textContent = cfmCount + ' cohort' + (cfmCount !== 1 ? 's' : '');
        } else {
            alert(data.error || 'Failed to add cohort');
        }
    })
    .catch(function() { alert('Network error'); })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg> Add Cohort';
    });
}

/* ── Delete cohort via AJAX ── */
function cfmDelete(id, name) {
    // Change note: this inline manager shares the same admin delete modal as full-page tables.
    var confirmDelete = window.AdminDeleteConfirm
        ? window.AdminDeleteConfirm.ask({
            title: 'Delete cohort?',
            message: 'This removes the "' + name + '" cohort from the cohort options for incubatees. This action cannot be undone.',
        })
        : Promise.resolve(confirm('Delete ' + name + '?'));

    confirmDelete.then(function(confirmed) {
        if (!confirmed) return;
        fetch('<?= site_url('admin/cohorts/') ?>' + id + '/delete', {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json'},
        body: JSON.stringify({})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok) {
            // Remove from modal list
            var li = document.querySelector('#cfmList .cfm-item[data-id="' + id + '"]');
            if (li) li.remove();
            // Remove from dropdown
            var sel = document.getElementById('cohortSelect');
            for (var i = 0; i < sel.options.length; i++) {
                if (sel.options[i].value === name) { sel.remove(i); break; }
            }
            // If deleted cohort was selected, reset
            if (document.getElementById('cohortHidden').value === name) {
                sel.value = '';
                document.getElementById('cohortHidden').value = '';
            }
            cfmCount--;
            document.getElementById('cfmTotal').textContent = cfmCount + ' cohort' + (cfmCount !== 1 ? 's' : '');
            if (cfmCount === 0) {
                var emptyDiv = document.createElement('div');
                emptyDiv.className = 'cfm-empty';
                emptyDiv.id = 'cfmEmpty';
                emptyDiv.textContent = 'No cohorts yet. Add one below.';
                document.querySelector('.cfm-body').appendChild(emptyDiv);
            }
        } else {
            alert(data.error || 'Failed to delete');
        }
    })
        .catch(function() { alert('Network error'); });
    });
}
</script>
<script src="<?= base_url('assets/js/admin/incubatees/form.js') ?>"></script>
