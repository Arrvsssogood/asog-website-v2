<!-- ╔══════════════════════════════════════════════════════════════════════╗
     ║  INCUBATEE APPLICATION FORM — Horizontal grid layout                 ║
     ╚══════════════════════════════════════════════════════════════════════╝ -->
<section class="relative bg-off py-16 md:py-24 px-6 md:px-10 lg:px-14">
    <div class="ai-grid"></div>
    <div class="ai-grid-fade"></div>

    <div class="max-w-[960px] mx-auto relative z-[2]">

        <?php
            // Server-side errors keyed by field name
            $errs = $formErrors ?? (session('errors') ?? []);
            $formInput = $formInput ?? [];
            $formError = $formError ?? session('error');
            $inputValue = static fn (string $key, string $default = ''): string => (string) old($key, $formInput[$key] ?? $default);
            $privacyAccepted = $inputValue('privacyAgreement') === '1';
            $serverPostMaxSize = trim((string) ($serverPostMaxSize ?? ''));
            $serverUploadMaxFilesize = trim((string) ($serverUploadMaxFilesize ?? ''));
            $isRevalidation = ! empty($isRevalidation);
            $formAction = $isRevalidation ? ($revalidationAction ?? current_url()) : site_url('apply/form');
            $existingTeamCvPath = trim((string) ($existingTeamCvPath ?? ''));
            $existingLeanCanvasPath = trim((string) ($existingLeanCanvasPath ?? ''));
            $existingTeamCvPaths = $existingTeamCvPath !== '' ? array_values(array_filter(array_map('trim', explode(',', $existingTeamCvPath)))) : [];
            $existingTeamCvCount = count($existingTeamCvPaths);
            $existingLeanCanvasName = $existingLeanCanvasPath !== '' ? basename($existingLeanCanvasPath) : '';
            $fileUrl = static fn (string $path): string => base_url(ltrim($path, '/'));
            $storageKey = $isRevalidation
                ? 'asog_apply_form_revalidation_' . (string) ($formInput['id'] ?? 'unknown')
                : 'asog_apply_form_public_v2';
            $recaptcha = config('Recaptcha');
            $recaptchaEnabled = $recaptcha->enabled && $recaptcha->siteKey !== '';
            $recaptchaAction = $isRevalidation ? 'application_revalidate' : 'application_submit';
        ?>

        <?php if ($formError): ?>
            <div class="mb-6 rounded-md border border-navy/10 bg-white p-4">
                <p class="text-[.58rem] font-bold tracking-[.18em] uppercase text-red-500 mb-1">Submission issue</p>
                <p class="text-[.82rem] font-normal leading-[1.6] text-dark"><?= esc((string) $formError) ?></p>
            </div>
        <?php endif; ?>

        <form id="applyForm" action="<?= esc($formAction) ?>" method="post" enctype="multipart/form-data"
            data-check-url="<?= site_url('apply/form/check-email') ?>"
            data-skip-duplicate-email="<?= $isRevalidation ? '1' : '0' ?>"
            data-form-mode="<?= $isRevalidation ? 'revalidation' : 'public' ?>"
            data-storage-key="<?= esc($storageKey) ?>"
            data-has-existing-lean-canvas="<?= $isRevalidation && $existingLeanCanvasPath !== '' ? '1' : '0' ?>"
            data-existing-team-cv-count="<?= esc((string) $existingTeamCvCount) ?>"
            data-recaptcha-enabled="<?= $recaptchaEnabled ? '1' : '0' ?>"
            data-recaptcha-site-key="<?= esc($recaptcha->siteKey) ?>"
            data-recaptcha-action="<?= esc($recaptchaAction) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="recaptchaToken" data-recaptcha-token value="">
            <input type="hidden" name="recaptchaAction" value="<?= esc($recaptchaAction) ?>">

            <!-- ═══════════════════════════════════════════════════════
                 WELCOME INTRO
                 ═══════════════════════════════════════════════════════ -->
            <div class="mb-10">
                <div class="flex items-center gap-2 mb-4">
                    <span class="block w-[18px] h-[2px] bg-gold"></span>
                    <span class="text-[.52rem] font-bold tracking-[.2em] uppercase text-gold">ASOG TBI</span>
                </div>
                <h2 class="font-display text-[1.4rem] md:text-[1.7rem] text-dark leading-snug mb-2"><?= $isRevalidation ? 'Update Your Startup Application' : 'Startup Application Form' ?></h2>
                <p class="text-[.92rem] font-medium text-dark/85 mb-3"><?= $isRevalidation ? 'Welcome back, Future Innovator!' : 'Welcome, Future Innovator!' ?></p>
                <p class="text-[.84rem] font-normal leading-[1.65] text-black max-w-[640px] mb-2 text-justify">
                    Thank you for your interest in joining the ASOG Technology Business Incubator (TBI). We're excited
                    to support passionate startups like yours in turning bold ideas into real-world solutions —
                    especially those rooted in AI, engineering, and food value chain innovation.
                </p>
                <p class="text-[.84rem] font-normal leading-[1.65] text-black max-w-[640px] text-justify">
                    Please fill out this form carefully so we can get to know your team, your idea, and how we can best
                    support your startup journey. We're looking forward to discovering the next big thing — and it might
                    just be you!
                </p>
            </div>

            <!-- ═══════════════════════════════════════════════════════
                 PRIVACY & DATA AGREEMENT
                 ═══════════════════════════════════════════════════════ -->
            <div class="mb-10 bg-white border border-navy/10 rounded-md p-5 md:p-7">
                <div class="flex items-center gap-2 mb-4">
                    <span class="block w-[18px] h-[2px] bg-gold"></span>
                    <h3 class="text-[.62rem] font-bold tracking-[.2em] uppercase text-navy m-0">Privacy &amp; Data
                        Agreement</h3>
                </div>
                <p class="text-[.82rem] font-normal leading-[1.65] text-dark mb-3">
                    By submitting this application, you agree that ASOG Technology Business Incubator (ASOG TBI)
                    may collect, process, and store the personal information you provide for the purpose of evaluating
                    your application to the incubation program. Your data will be handled in accordance with the
                    Data Privacy Act of 2012 (RA 10173) and will not be shared with third parties without your consent.
                </p>
                <label class="flex items-start gap-3 cursor-pointer select-none group">
                    <input type="checkbox" id="privacyAgreement" name="privacyAgreement" value="1" required
                        class="v-field mt-0.5 w-4 h-4 shrink-0 accent-gold cursor-pointer" data-v="required"
                        data-required-message="Please confirm your privacy consent before continuing."<?= $privacyAccepted ? ' checked' : '' ?>>
                    <span class="text-[.82rem] text-dark leading-[1.6] group-hover:text-dark transition-colors">
                        I have read and agree to the <strong class="text-[#102033] font-semibold">Privacy
                            Policy</strong> and
                        <strong class="text-[#102033] font-semibold">Data Collection Agreement</strong>. I consent to
                        ASOG TBI
                        collecting and processing my personal data for application evaluation purposes.
                        <span class="text-red-400">*</span>
                    </span>
                </label>
                <span class="v-msg text-[.62rem] text-red-500 block mt-2 hidden" data-for="privacyAgreement"></span>
            </div>

            <!-- ═══════════════════════════════════════════════════════
                 SECTION: YOUR INFORMATION
                 ═══════════════════════════════════════════════════════ -->
            <div class="mb-10">
                <div class="flex items-center gap-2 mb-5 pb-3 border-b-2 border-navy/15">
                    <span class="block w-[18px] h-[2px] bg-gold"></span>
                    <h3 class="text-[.62rem] font-bold tracking-[.2em] uppercase text-navy m-0">Applicant Information
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 border border-navy/10 rounded-md overflow-hidden bg-white">
                    <!-- Full Name -->
                    <div class="border-b md:border-b md:border-r border-navy/10 p-4 md:p-5">
                        <label for="applicantName"
                            class="text-[.52rem] font-bold tracking-[.18em] uppercase text-[#102033]/85 block mb-1.5">
                            Full Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" id="applicantName" name="applicantName" maxlength="255"
                            data-v="required|min:2|name"
                            class="v-field w-full bg-transparent border-none p-0 text-[.88rem] text-dark font-normal outline-none placeholder:text-dark/25"
                            placeholder="Dela Cruz, Juan A." value="<?= esc($inputValue('applicantName')) ?>" required>
                        <span class="text-[.58rem] text-dark/60 block mt-2">Last Name, First Name MI</span>
                        <span class="v-msg text-[.62rem] text-red-500 block mt-1 hidden"
                            data-for="applicantName"><?= $errs['applicantName'] ?? '' ?></span>
                    </div>
                    <!-- Email -->
                    <div class="border-b md:border-b md:border-r border-navy/10 p-4 md:p-5">
                        <label for="applicantEmail"
                            class="text-[.52rem] font-bold tracking-[.18em] uppercase text-[#102033]/85 block mb-1.5">
                            Email Address <span class="text-red-400">*</span>
                        </label>
                        <input type="email" id="applicantEmail" name="applicantEmail" maxlength="255"
                            data-v="required|email"
                            class="v-field w-full bg-transparent border-none p-0 text-[.88rem] text-dark font-normal outline-none placeholder:text-dark/25"
                            placeholder="your.email@example.com" value="<?= esc($inputValue('applicantEmail')) ?>" required>
                        <span class="v-msg text-[.62rem] text-red-500 block mt-1 hidden"
                            data-for="applicantEmail"><?= $errs['applicantEmail'] ?? '' ?></span>
                    </div>
                    <!-- Contact -->
                    <div class="border-b border-navy/10 p-4 md:p-5">
                        <label for="contactNumber"
                            class="text-[.52rem] font-bold tracking-[.18em] uppercase text-[#102033]/85 block mb-1.5">
                            Contact Number <span class="text-red-400">*</span>
                        </label>
                        <input type="tel" id="contactNumber" name="contactNumber" maxlength="11" inputmode="numeric" pattern="[0-9]*" data-v="required|phone"
                            class="v-field w-full bg-transparent border-none p-0 text-[.88rem] text-dark font-normal outline-none placeholder:text-dark/25"
                            placeholder="09XX XXX XXXX" value="<?= esc($inputValue('contactNumber')) ?>" required>
                        <!-- <span class="text-[.58rem] text-dark/60 block mt-2">11 digits only, start with 09</span> -->
                        <span class="v-msg text-[.62rem] text-red-500 block mt-1 hidden"
                            data-for="contactNumber"><?= $errs['contactNumber'] ?? '' ?></span>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════
                 SECTION: STARTUP PRESENTATION
                 ═══════════════════════════════════════════════════════ -->
            <div class="mb-10">
                <div class="flex items-center gap-2 mb-5 pb-3 border-b-2 border-navy/15">
                    <span class="block w-[18px] h-[2px] bg-gold"></span>
                    <h3 class="text-[.62rem] font-bold tracking-[.2em] uppercase text-navy m-0">Presentation of Your
                        Startup</h3>
                </div>

                <div class="border border-navy/10 rounded-md overflow-hidden bg-white">
                    <!-- Row 1: Startup Name (full width) -->
                    <div class="border-b border-navy/10 p-4 md:p-5">
                        <label for="startupName"
                            class="text-[.52rem] font-bold tracking-[.18em] uppercase text-[#102033]/85 block mb-1.5">
                            Startup Name <span class="text-red-400">*</span>
                        </label>
                        <input type="text" id="startupName" name="startupName" maxlength="255" data-v="required|min:2"
                            class="v-field w-full bg-transparent border-none p-0 text-[.88rem] text-dark font-normal outline-none placeholder:text-dark/25"
                            placeholder="e.g. GreenTech Innovations" value="<?= esc($inputValue('startupName')) ?>" required>
                        <span class="v-msg text-[.62rem] text-red-500 block mt-1 hidden"
                            data-for="startupName"><?= $errs['startupName'] ?? '' ?></span>
                    </div>
                    <!-- Row 2: Description (full width) -->
                    <div class="border-b border-navy/10 p-4 md:p-5">
                        <label for="startupDescription"
                            class="text-[.52rem] font-bold tracking-[.18em] uppercase text-[#102033]/85 block mb-1.5">
                            Describe your startup. How is it innovative? <span class="text-red-400">*</span>
                        </label>
                        <textarea id="startupDescription" name="startupDescription" rows="4" maxlength="2000"
                            data-v="required|min:10"
                            class="v-field guidelines-scroll w-full bg-transparent border-none p-0 text-[.88rem] text-dark font-normal outline-none resize-none placeholder:text-dark/25"
                            placeholder="Describe your solution, target market, and what makes it unique..."
                            required><?= esc($inputValue('startupDescription')) ?></textarea>
                        <span class="v-msg text-[.62rem] text-red-500 block mt-1 hidden"
                            data-for="startupDescription"><?= $errs['startupDescription'] ?? '' ?></span>
                    </div>
                    <!-- Row 3: Two columns — Risk + Goals -->
                    <div class="grid grid-cols-1 md:grid-cols-2">
                        <div class="border-b md:border-b-0 md:border-r border-navy/10 p-4 md:p-5">
                            <label for="mainRisk"
                                class="text-[.52rem] font-bold tracking-[.18em] uppercase text-[#102033]/85 block mb-1.5">
                                Main Risk for Your Startup
                            </label>
                            <textarea id="mainRisk" name="mainRisk" rows="3" maxlength="1000"
                                class="guidelines-scroll w-full bg-transparent border-none p-0 text-[.88rem] text-dark font-normal outline-none resize-none placeholder:text-dark/25"
                                placeholder="Describe potential challenges..."><?= esc($inputValue('mainRisk')) ?></textarea>
                        </div>
                        <div class="p-4 md:p-5">
                            <label for="shortTermGoals"
                                class="text-[.52rem] font-bold tracking-[.18em] uppercase text-[#102033]/85 block mb-1.5">
                                Short-term Goals (3–5 months)
                            </label>
                            <textarea id="shortTermGoals" name="shortTermGoals" rows="3" maxlength="1000"
                                class="guidelines-scroll w-full bg-transparent border-none p-0 text-[.88rem] text-dark font-normal outline-none resize-none placeholder:text-dark/25"
                                placeholder="What do you plan to achieve?..."><?= esc($inputValue('shortTermGoals')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════
                 SECTION: TEAM PRESENTATION
                 ═══════════════════════════════════════════════════════ -->
            <div class="mb-10">
                <div class="flex items-center gap-2 mb-5 pb-3 border-b-2 border-navy/15">
                    <span class="block w-[18px] h-[2px] bg-gold"></span>
                    <h3 class="text-[.62rem] font-bold tracking-[.2em] uppercase text-navy m-0">Presentation of Your
                        Team</h3>
                </div>

                <div class="border border-navy/10 rounded-md overflow-hidden bg-white">
                    <div class="grid grid-cols-1 md:grid-cols-2">
                        <!-- CV Upload -->
                        <div class="border-b md:border-b-0 md:border-r border-navy/10 p-4 md:p-5">
                            <label for="teamCv"
                                class="text-[.52rem] font-bold tracking-[.18em] uppercase text-[#102033]/85 block mb-1.5">
                                Team Members' CV
                            </label>
                                <span class="text-[.58rem] text-navy/30 block mb-3">Upload PDFs · Max 10 files · 100 MB
                                    each</span>
                            <div id="teamCvChooser" class="inline-flex items-center gap-3">
                                <button type="button" id="teamCvButton" class="file-upload-button">
                                    Choose File
                                </button>
                                <span id="teamCvStatus" class="text-[.78rem] text-dark/60">No file chosen</span>
                                <input type="file" id="teamCv" name="teamCv[]" multiple accept=".pdf" class="hidden">
                            </div>
                            <?php if ($isRevalidation && $existingTeamCvPath !== ''): ?>
                                <div class="mt-3 space-y-2">
                                    <p class="text-[.58rem] font-bold tracking-[.16em] uppercase text-navy/45 m-0">Current CV file<?= $existingTeamCvCount === 1 ? '' : 's' ?></p>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($existingTeamCvPaths as $path): ?>
                                            <a href="<?= esc($fileUrl($path)) ?>" target="_blank" rel="noopener"
                                                class="inline-flex items-center gap-2 text-[.72rem] text-dark/70 bg-off/60 border border-navy/10 rounded-sm px-3 py-1.5 no-underline hover:border-navy/30 hover:text-navy transition-colors">
                                                <svg class="w-3.5 h-3.5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M4 2a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V8l-6-6H4zm7 1.5L16.5 9H12a1 1 0 01-1-1V3.5z"/>
                                                </svg>
                                                <span class="max-w-[220px] truncate"><?= esc(basename($path)) ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="text-[.62rem] text-navy/50 m-0">Leave blank to keep <?= $existingTeamCvCount === 1 ? 'this file' : 'these files' ?>.</p>
                                </div>
                            <?php endif; ?>
                            <!-- File preview list -->
                            <ul id="teamCvList" class="mt-3 space-y-2.5 list-none p-0 m-0 hidden"></ul>
                            <span id="teamCvNotice" class="text-[.62rem] text-red-500 block mt-1.5 hidden"></span>
                        </div>
                        <!-- Video Link -->
                        <div class="p-4 md:p-5">
                            <label for="videoPresentationLink"
                                class="text-[.52rem] font-bold tracking-[.18em] uppercase text-[#102033]/85 block mb-1.5">
                                Video Presentation Link <span class="text-red-400">*</span>
                            </label>
                            <span class="text-[.58rem] text-navy/30 block mb-3">1-minute pitch · YouTube or Google
                                Drive</span>
                            <input type="url" id="videoPresentationLink" name="videoPresentationLink" maxlength="500"
                                data-v="required|url"
                                class="v-field w-full bg-off/50 border border-navy/10 rounded-sm px-3 py-2 text-[.85rem] text-dark font-normal outline-none transition-colors duration-200 focus:border-gold placeholder:text-dark/25"
                                placeholder="https://youtu.be/..." value="<?= esc($inputValue('videoPresentationLink')) ?>" required>
                            <span class="v-msg text-[.62rem] text-red-500 block mt-1 hidden"
                                data-for="videoPresentationLink"><?= $errs['videoPresentationLink'] ?? '' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════
                 SECTION: LEAN CANVAS
                 ═══════════════════════════════════════════════════════ -->
            <div class="mb-10">
                <div class="flex items-center gap-2 mb-5 pb-3 border-b-2 border-navy/15">
                    <span class="block w-[18px] h-[2px] bg-gold"></span>
                    <h3 class="text-[.62rem] font-bold tracking-[.2em] uppercase text-navy m-0">Lean Canvas</h3>
                </div>

                <div class="border border-navy/10 rounded-md overflow-hidden bg-white">
                    <!-- Description row -->
                    <div class="border-b border-navy/10 p-4 md:p-5 bg-navy/[.02]">
                        <p class="text-[.78rem] text-black leading-[1.75] m-0">
                            A <strong class="font-semibold text-black">Lean Canvas</strong> is a one-page diagram
                            designed for entrepreneurs to use when developing, evaluating, and validating a scalable
                            startup business idea.
                        </p>
                        <p class="text-[.75rem] text-black leading-[1.7] mt-2 mb-0">
                            Download the Lean Canvas template, complete it, then upload the finished PDF or DOCX file below.
                        </p>
                        <a href="<?= base_url('assets/file/ASOG%20TBI%20Startup%20-%20Lean%20Canvas.docx') ?>"
                            download="ASOG TBI Startup - Lean Canvas.docx"
                            class="inline-flex items-center gap-1.5 mt-3 text-[.6rem] font-bold tracking-[.12em] uppercase text-navy no-underline border border-navy/20 px-4 py-2 rounded-sm transition-all duration-200 hover:bg-navy hover:text-white hover:border-navy">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download Lean Canvas Template
                        </a>
                    </div>

                    <!-- Upload row -->
                    <div class="p-4 md:p-5">
                        <label for="leanCanvas"
                            class="text-[.52rem] font-bold tracking-[.18em] uppercase text-[#102033]/85 block mb-1">
                            Your Startup's Lean Canvas <span class="text-red-400">*</span>
                        </label>
                        <span class="text-[.58rem] text-navy/30 block mb-3">Must be in .docx or PDF &middot; 1 file
                            &middot; Max 10 MB</span>
                        <div id="leanCanvasChooser" class="inline-flex items-center gap-3">
                            <button type="button" id="leanCanvasButton" class="file-upload-button">
                                Choose File
                            </button>
                            <span id="leanCanvasStatus" class="text-[.78rem] text-dark/60">No file chosen</span>
                            <input type="file" id="leanCanvas" name="leanCanvas"
                                accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                class="hidden">
                        </div>
                        <?php if ($isRevalidation && $existingLeanCanvasPath !== ''): ?>
                            <div class="mt-3 space-y-2">
                                <p class="text-[.58rem] font-bold tracking-[.16em] uppercase text-navy/45 m-0">Current Lean Canvas</p>
                                <a href="<?= esc($fileUrl($existingLeanCanvasPath)) ?>" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-2 text-[.72rem] text-dark/70 bg-off/60 border border-navy/10 rounded-sm px-3 py-1.5 no-underline hover:border-navy/30 hover:text-navy transition-colors">
                                    <svg class="w-3.5 h-3.5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 2a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V8l-6-6H4zm7 1.5L16.5 9H12a1 1 0 01-1-1V3.5z"/>
                                    </svg>
                                    <span class="max-w-[260px] truncate"><?= esc($existingLeanCanvasName) ?></span>
                                </a>
                                <p class="text-[.62rem] text-navy/50 m-0">Leave blank to keep this file.</p>
                            </div>
                        <?php endif; ?>
                        <!-- File preview -->
                        <div id="leanCanvasPreview" class="hidden"></div>
                        <span class="v-msg text-[.62rem] text-red-500 block mt-1.5 hidden"
                            id="leanCanvasErr"><?= $errs['leanCanvas'] ?? '' ?></span>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════
                 SUBMIT
                 ═══════════════════════════════════════════════════════ -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
                <div class="flex items-center gap-3">
                    <button type="button" id="btnPreview"
                        class="font-body text-[.62rem] font-bold tracking-[.14em] uppercase text-white bg-navy px-8 py-3.5 rounded-sm border-none cursor-pointer transition-all duration-200 hover:bg-dark">
                        <?= $isRevalidation ? 'Review &amp; Update' : 'Review &amp; Submit →' ?>
                    </button>
                    <button type="button" data-open-guidelines
                        class="font-body text-[.6rem] font-bold tracking-[.13em] uppercase text-navy/50 bg-transparent px-4 py-3.5 rounded-sm border border-navy/15 cursor-pointer transition-all duration-200 hover:text-navy hover:border-navy/30">
                        <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1" fill="none" stroke="currentColor"
                            stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        Guidelines
                    </button>
                    
                </div>
            </div>
        </form>
    </div>
</section>

<!-- ═══ PREVIEW MODAL ═══ -->
<div id="previewModal"
    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 opacity-0 pointer-events-none transition-opacity duration-300">
    <!-- Backdrop -->
    <div id="previewBackdrop" class="absolute inset-0 bg-dark/60 backdrop-blur-sm"></div>

    <!-- Modal body -->
    <div class="relative w-full max-w-[680px] max-h-[85vh] overflow-y-auto guidelines-scroll bg-white rounded-lg shadow-2xl border border-navy/10 transform scale-95 transition-transform duration-300"
        id="previewBody">
        <!-- Header -->
        <div class="sticky top-0 bg-white z-10 px-7 py-5 border-b border-navy/10 flex items-center justify-between">
            <div>
                <h3 class="font-display text-[1.25rem] text-dark m-0"><?= $isRevalidation ? 'Review Your Updates' : 'Review Your Application' ?></h3>
                <p class="text-[.68rem] text-dark/40 mt-0.5"><?= $isRevalidation ? 'Please confirm the updates below before resubmitting.' : 'Please confirm the details below before submitting.' ?></p>
            </div>
            <button id="btnClosePreview"
                class="w-8 h-8 rounded-full bg-off flex items-center justify-center text-dark/40 hover:text-dark hover:bg-navy/10 transition-colors cursor-pointer border-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Content -->
        <div class="px-7 py-6 space-y-6">
            <!-- Applicant -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="block w-3 h-[2px] bg-gold"></span>
                    <span class="text-[.52rem] font-bold tracking-[.2em] uppercase text-[#102033]/85">Applicant
                        Information</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <span class="text-[.5rem] font-bold tracking-[.16em] uppercase text-dark/30 block mb-0.5">Full
                            Name</span>
                        <span class="text-[.85rem] text-dark" id="pv_applicantName">—</span>
                    </div>
                    <div>
                        <span
                            class="text-[.5rem] font-bold tracking-[.16em] uppercase text-dark/30 block mb-0.5">Email</span>
                        <span class="text-[.85rem] text-dark" id="pv_applicantEmail">—</span>
                    </div>
                    <div>
                        <span
                            class="text-[.5rem] font-bold tracking-[.16em] uppercase text-dark/30 block mb-0.5">Contact</span>
                        <span class="text-[.85rem] text-dark" id="pv_contactNumber">—</span>
                    </div>
                </div>
            </div>

            <div class="h-px bg-navy/[.06]"></div>

            <!-- Startup -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="block w-3 h-[2px] bg-gold"></span>
                    <span class="text-[.52rem] font-bold tracking-[.2em] uppercase text-[#102033]/85">Startup
                        Details</span>
                </div>
                <div class="space-y-3">
                    <div>
                        <span
                            class="text-[.5rem] font-bold tracking-[.16em] uppercase text-dark/30 block mb-0.5">Startup
                            Name</span>
                        <span class="text-[.85rem] text-dark" id="pv_startupName">—</span>
                    </div>
                    <div>
                        <span
                            class="text-[.5rem] font-bold tracking-[.16em] uppercase text-dark/30 block mb-0.5">Description</span>
                        <p class="text-[.82rem] text-dark/70 leading-[1.7] whitespace-pre-line m-0"
                            id="pv_startupDescription">—</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <span
                                class="text-[.5rem] font-bold tracking-[.16em] uppercase text-dark/30 block mb-0.5">Main
                                Risk</span>
                            <p class="text-[.82rem] text-dark/70 leading-[1.7] whitespace-pre-line m-0"
                                id="pv_mainRisk">—</p>
                        </div>
                        <div>
                            <span
                                class="text-[.5rem] font-bold tracking-[.16em] uppercase text-dark/30 block mb-0.5">Short-term
                                Goals</span>
                            <p class="text-[.82rem] text-dark/70 leading-[1.7] whitespace-pre-line m-0"
                                id="pv_shortTermGoals">—</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-px bg-navy/[.06]"></div>

            <!-- Team -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="block w-3 h-[2px] bg-gold"></span>
                    <span class="text-[.52rem] font-bold tracking-[.2em] uppercase text-[#102033]/85">Team
                        Presentation</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="text-[.5rem] font-bold tracking-[.16em] uppercase text-dark/30 block mb-0.5">CV
                            Files</span>
                        <span class="text-[.82rem] text-dark/70" id="pv_teamCv">None uploaded</span>
                    </div>
                    <div>
                        <span class="text-[.5rem] font-bold tracking-[.16em] uppercase text-dark/30 block mb-0.5">Video
                            Link</span>
                        <a href="#" target="_blank"
                            class="text-[.82rem] text-gold hover:text-gold-dk no-underline break-all"
                            id="pv_videoPresentationLink">—</a>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-[.5rem] font-bold tracking-[.16em] uppercase text-dark/30 block mb-0.5">Lean
                            Canvas</span>
                        <span class="text-[.82rem] text-dark/70" id="pv_leanCanvas">No file uploaded</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div
            class="sticky bottom-0 bg-white z-10 px-7 py-5 border-t border-navy/10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <button id="btnBackEdit"
                class="font-body text-[.58rem] font-bold tracking-[.12em] uppercase text-dark/50 bg-transparent px-5 py-2.5 rounded-sm border border-dark/15 cursor-pointer transition-all duration-200 hover:border-dark/30 hover:text-dark">
                ← Edit Application
            </button>
            <button id="btnConfirmSubmit"
                class="font-body text-[.62rem] font-bold tracking-[.14em] uppercase text-white bg-navy px-8 py-3.5 rounded-sm border-none cursor-pointer transition-all duration-200 hover:bg-dark">
                <?= $isRevalidation ? 'Confirm &amp; Update' : 'Confirm &amp; Submit' ?>
            </button>
        </div>
    </div>
</div>

<?php if ($recaptchaEnabled): ?>
    <script src="https://www.google.com/recaptcha/enterprise.js?render=<?= rawurlencode($recaptcha->siteKey) ?>"></script>
<?php endif; ?>
<script src="<?= base_url('assets/js/features/forms/applyForm.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/features/forms/applyForm.js') ?>"></script>

<!-- ═══ GUIDELINES MODAL (reusable) ═══ -->
<?= view('incubatees/partials/_guidelines_modal') ?>
