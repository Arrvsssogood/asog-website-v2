<!-- ╔══════════════════════════════════════════════════════════════╗
     ║  INCUBATEES — All Cohorts, One Page                           ║
     ║  Horizontal cohort tabs · Card grid · Panel detail            ║
     ╚══════════════════════════════════════════════════════════════╝ -->
<?php
$cohorts        = $cohorts ?? [];
$allIncubatees  = $allIncubatees ?? [];
$hasIncubatees  = ! empty($allIncubatees);
$hasCohorts     = ! empty($cohorts);
$sealPath       = 'assets/img/ASOG TBI/WebP/ASOG-TBI-stacked-v2';
$firstCohort    = $hasCohorts ? $cohorts[0]['name'] : '';
?>

<link rel="stylesheet" href="<?= base_url('assets/css/incubatees.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    crossorigin="anonymous" referrerpolicy="no-referrer">

<section class="ib-s relative min-h-screen py-20 pb-16">
    <div class="ib-w mx-auto px-6 md:px-10 lg:px-14">

        <?php if ($hasCohorts): ?>
        <!-- ═══════ COHORT TABS ═══════ -->
        <div class="ib-tabs reveal" id="ibTabs">
            <?php foreach ($cohorts as $i => $ch): ?>
            <button class="ib-tab<?= $i === 0 ? ' is-active' : '' ?>" data-cohort="<?= esc($ch['name']) ?>">
                <?= esc($ch['name']) ?>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($hasIncubatees): ?>
        <!-- ═══════ CARD GRID (all cohorts, filtered by tab) ═══════ -->
        <div id="ibStack" class="ib-stack flex flex-wrap gap-5 justify-center relative">
            <?php foreach ($allIncubatees as $i => $inc): ?>
            <div class="ib-card cursor-pointer relative" data-ix="<?= $i ?>"
                data-cohort="<?= esc($inc['cohort'] ?? '') ?>"
                <?php if (($inc['cohort'] ?? '') !== $firstCohort): ?>style="display:none" <?php endif; ?>>
                <div class="ib-inner relative w-full h-full rounded-xl">

                    <!-- Front -->
                    <div class="ib-front absolute inset-0 overflow-hidden rounded-xl flex flex-col items-center">
                        <div class="ib-frame absolute pointer-events-none"></div>
                        <div class="ib-diamond absolute pointer-events-none tl"></div>
                        <div class="ib-diamond absolute pointer-events-none tr"></div>
                        <div class="ib-diamond absolute pointer-events-none bl"></div>
                        <div class="ib-diamond absolute pointer-events-none br"></div>
                        <div class="ib-portrait w-full flex-1 flex items-center justify-center relative">
                            <div class="ib-logo-box">
                                <?php if (! empty($inc['logoPath'])): ?>
                                <?= responsiveUploadImg($inc['logoPath'], 'incubatees', $inc['companyName'], '', true) ?>
                                <?php else: ?>
                                <span class="ib-init"><?= strtoupper(substr($inc['companyName'], 0, 1)) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Back -->
                    <div class="ib-back absolute inset-0 overflow-hidden rounded-xl flex flex-col items-center">
                        <div class="ib-frame absolute pointer-events-none"></div>
                        <div class="ib-frame-inner absolute pointer-events-none"></div>
                        <div class="ib-diamond absolute pointer-events-none tl"></div>
                        <div class="ib-diamond absolute pointer-events-none tr"></div>
                        <div class="ib-diamond absolute pointer-events-none bl"></div>
                        <div class="ib-diamond absolute pointer-events-none br"></div>
                        <div class="ib-dots absolute inset-0 pointer-events-none"></div>
                        <img class="ib-seal relative" src="<?= $sealUrl ?>" alt="ASOG TBI">
                        <div class="ib-back-divider relative shrink-0"></div>
                        <p class="ib-back-name relative shrink-0"><?= esc($inc['companyName']) ?></p>
                        <span
                            class="ib-back-cohort relative shrink-0"><?= esc(preg_replace('/\s*[·•|\-–—]\s*\d{4}/', '', $inc['cohort'] ?? '')) ?></span>
                        <button class="ib-see-more relative shrink-0" data-ix="<?= $i ?>">
                            See More
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Coming Soon — visible when selected cohort has zero incubatees -->
        <div id="ibComingSoon" class="text-center py-16"
            style="display:<?= ($hasCohorts && $cohorts[0]['_count'] === 0) ? 'block' : 'none' ?>">
            <div class="mb-6">
                <?= responsiveStaticImg('assets/img/icons8-rocket-launch-94', 'default', 'Coming Soon', 'w-128 h-128 mx-auto opacity-50', true) ?>
            </div>
            <h3 class="font-display text-2xl text-dark mb-3">
                <span id="ibCSLabel"><?= esc($firstCohort) ?></span> — Coming Soon
            </h3>
            <p class="text-dark/55 text-[.88rem] max-w-lg mx-auto leading-relaxed mb-2">
                Incubatees for this cohort will be announced soon.
            </p>
            <a href="<?= site_url('apply') ?>"
                class="inline-block mt-6 text-[.7rem] font-bold tracking-[.14em] uppercase text-navy bg-gold px-8 py-3.5 rounded-sm no-underline transition-colors hover:bg-gold-dk">
                Apply Now
            </a>
        </div>

        <?php if (! $hasCohorts): ?>
        <div class="text-center py-12 reveal">
            <p class="text-dark/35 text-[.88rem] mb-6">No cohorts have been announced yet.</p>
            <a href="<?= site_url('apply') ?>"
                class="inline-block text-[.7rem] font-bold tracking-[.14em] uppercase text-navy bg-gold px-8 py-3.5 rounded-sm no-underline transition-colors hover:bg-gold-dk">
                Apply Now
            </a>
        </div>
        <?php endif; ?>

    </div>
</section>

<?php if ($hasIncubatees): ?>
<?= view('incubatees/partials/_overlay', ['sealUrl' => base_url($sealPath . '.webp')]) ?>
<?= view('incubatees/partials/_panel') ?>

<!-- Mobile Preview Modal -->
<div id="ibMobilePreview" class="ib-mob-preview">
    <div class="ib-mob-preview-backdrop" id="ibMobPreviewBackdrop"></div>
    <div class="ib-mob-preview-wrap" id="ibMobPreviewWrap">
        <div class="ib-mob-preview-card">
            <div class="ib-mob-preview-inner" id="mpInner">
                <!-- Front — navy -->
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
                        <span id="mpCohort" class="ib-bf-cohort block"></span>
                    </div>
                    <span class="ib-mob-flip-cue">Tap to flip ↻</span>
                </div>
                <!-- Back — white / team -->
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
        <span class="ib-mob-preview-hint" id="mpHint">Tap card to flip</span>
        <button class="ib-mob-preview-read" id="mpReadMore">
            Read More
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>
</div>

<script src="<?= base_url('assets/js/features/incubatees/incubateesLoader.js') ?>" defer
    data-api-url="<?= site_url('api/incubatees') ?>"
    data-app-script="<?= base_url('assets/js/features/incubatees/incubatees.js') ?>"></script>
    <script src="<?= base_url('assets/js/features/incubatees/incubateesCohortTabs.js') ?>" defer></script>
<?php endif; ?>