<!-- ╔══════════════════════════════════════════════════════════════╗
     ║  COHORT PAGE — Dedicated incubatee showcase per cohort        ║
     ╚══════════════════════════════════════════════════════════════╝ -->
<?php
$incubatees    = $incubatees ?? [];
$hasIncubatees = ! empty($incubatees);
$count         = count($incubatees);
$cohortLabel   = $cohortLabel ?? 'Cohort';
$sealUrl       = base_url('assets/img/ASOG TBI/WebP/ASOG-TBI-stacked-v2.webp');
?>

<link rel="stylesheet" href="<?= base_url('assets/css/incubatees.css') ?>">

<!-- Section -->
<section class="ib-s relative min-h-screen py-20 pb-16" 
         id="ibCohortSection"
         style="opacity: 0; transition: opacity 0.4s ease;">
    <div class="ib-w mx-auto px-6 md:px-10 lg:px-14">

        <!-- Header -->
        <div class="flex items-center gap-2 mb-3">
            <span class="ib-rule block"></span>
            <span class="ib-tag"><?= esc($cohortLabel) ?></span>
        </div>
        <div class="mb-12">
            <h2 class="ib-title m-0 mb-2"><?= esc($cohortLabel) ?> <em>Startups</em></h2>
            <p class="ib-sub m-0">The startups and MSMEs accepted into <?= esc($cohortLabel) ?> of the ASOG TBI
                incubation program.</p>
        </div>

        <?php if ($hasIncubatees): ?>
        <?= view('incubatees/partials/_cards', ['incubatees' => $incubatees, 'sealUrl' => $sealUrl]) ?>
        <?php else: ?>
        <!-- Empty state -->
        <div class="text-center py-16">
            <!-- 3D illustration — journey / take-off symbolism -->
            <div class="mb-8">
                <img src="<?= base_url('assets/img/illustrations/rocket-takeoff.svg') ?>" alt="Ready for takeoff"
                    class="w-52 h-52 mx-auto opacity-80" />
            </div>
            <h3 class="font-display text-2xl text-dark mb-3"><?= esc($cohortLabel) ?> Coming Soon</h3>
            <p class="text-dark/55 text-[.88rem] max-w-lg mx-auto leading-relaxed mb-2">
                Incubatees for <?= esc($cohortLabel) ?> will be announced soon.
                Interested in joining? Apply to the ASOG-TBI incubation program.
            </p>
            <p class="text-dark/35 text-[.75rem] max-w-md mx-auto leading-relaxed">
                The journey of a thousand startups begins with a single application.
            </p>
            <a href="<?= site_url('apply') ?>"
                class="inline-block mt-8 text-[.7rem] font-bold tracking-[.14em] uppercase text-navy bg-gold px-8 py-3.5 rounded-sm no-underline transition-colors hover:bg-gold-dk">
                Apply Now
            </a>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="flex flex-col gap-2.5 items-start mt-10 sm:flex-row sm:items-center sm:justify-between">
            <p class="ib-count">
                <?php if ($hasIncubatees): ?>
                <?= $count ?> incubatee<?= $count !== 1 ? 's' : '' ?> in <?= esc($cohortLabel) ?>
                <?php else: ?>
                Interested in joining ASOG-TBI?
                <?php endif; ?>
            </p>
            <div class="flex gap-4">
                <a href="<?= site_url('incubatees') ?>"
                    class="text-[.56rem] font-bold tracking-[.14em] uppercase text-navy/50 no-underline border-b border-navy/15 pb-0.5 transition-colors hover:text-gold hover:border-gold">All
                    Cohorts →</a>
                <a href="<?= site_url('apply') ?>" class="ib-apply">Become an Incubatee</a>
            </div>
        </div>

    </div>
</section>

<?php if ($hasIncubatees): ?>
<?= view('incubatees/partials/_overlay', ['sealUrl' => $sealUrl]) ?>
<?= view('incubatees/partials/_panel') ?>

<!-- Mobile Preview Modal -->
<div id="ibMobilePreview" class="ib-mob-preview">
    <div class="ib-mob-preview-backdrop" id="ibMobPreviewBackdrop"></div>
    <div class="ib-mob-preview-wrap" id="ibMobPreviewWrap">
        <!-- Close button -->
        <button class="ib-mob-preview-close" id="ibMobPreviewClose">
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Mini flipping card (exact same structure as desktop big card) -->
        <div class="ib-mob-preview-card">
            <div class="ib-mob-preview-inner" id="mpInner">

                <!-- Front — navy (same as ib-big-front) -->
                <div
                    class="ib-big-front absolute inset-0 rounded-2xl overflow-hidden flex flex-col items-center justify-center">
                    <div class="ib-frame absolute pointer-events-none"></div>
                    <div class="ib-frame-inner absolute pointer-events-none"></div>
                    <div class="ib-diamond absolute pointer-events-none tl"></div>
                    <div class="ib-diamond absolute pointer-events-none tr"></div>
                    <div class="ib-diamond absolute pointer-events-none bl"></div>
                    <div class="ib-diamond absolute pointer-events-none br"></div>
                    <div class="ib-dots absolute inset-0 pointer-events-none"></div>
                    <span id="mpNum" class="ib-bf-num absolute"></span>
                    <div class="ib-bf-portrait flex items-center justify-center relative">
                        <div id="mpLogo" class="ib-bf-logo flex items-center justify-center"></div>
                    </div>
                    <div class="ib-bf-divider shrink-0 relative"></div>
                    <div class="ib-bf-nameplate text-center relative">
                        <h3 id="mpName" class="ib-bf-name"></h3>
                        <p id="mpFounder" class="ib-bf-founder"></p>
                        <span id="mpCohort" class="ib-bf-cohort block"></span>
                    </div>
                    <span class="ib-mob-flip-cue">Tap to flip ↻</span>
                </div>

                <!-- Back — white / team (same as ib-big-back) -->
                <div
                    class="ib-big-back absolute inset-0 rounded-2xl overflow-hidden flex flex-col items-center justify-center text-center">
                    <div class="ib-frame absolute pointer-events-none"></div>
                    <div class="ib-frame-inner absolute pointer-events-none"></div>
                    <div class="ib-diamond absolute pointer-events-none tl"></div>
                    <div class="ib-diamond absolute pointer-events-none tr"></div>
                    <div class="ib-diamond absolute pointer-events-none bl"></div>
                    <div class="ib-diamond absolute pointer-events-none br"></div>
                    <div class="text-center relative z-10">
                        <span class="ib-bb-label block">The Team</span>
                        <p id="mpBackName" class="ib-bb-name"></p>
                    </div>
                    <div class="ib-bb-divider shrink-0 relative"></div>
                    <div id="mpBackTeam" class="ib-bb-team w-full flex flex-col items-center overflow-y-auto relative">
                    </div>
                </div>

            </div>
        </div>

        <!-- Tap to flip hint -->
        <span class="ib-mob-preview-hint" id="mpHint">Tap card to flip</span>

        <!-- Read More button -->
        <button class="ib-mob-preview-read" id="mpReadMore">
            Read More
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>
</div>

<script src="<?= base_url('assets/js/features/incubatees/incubateesLoader.js') ?>" defer
    data-api-url="<?= site_url('api/incubatees') ?>" data-cohort="<?= esc($cohortLabel, 'attr') ?>"
    data-app-script="<?= base_url('assets/js/features/incubatees/incubatees.js') ?>"></script>
<?php endif; ?>