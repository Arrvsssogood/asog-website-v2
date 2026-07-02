<!--
     ╔══════════════════════════════════════════════════════════════════════╗
     ║  SECTION: FEATURED INCUBATEES — Scrolling Logo Carousel            ║
     ║  Off-white bg · auto-scroll logos · brand-partner style            ║
     ╚══════════════════════════════════════════════════════════════════════╝
-->
<?php
$all = $incubatees ?? [];
$hasIncubatees = ! empty($all);
$fallbackIncubateeImage = base_url('assets/img/incubatees.webp');

$selectedFilter = trim((string) ($landingIncubateesFilter ?? 'all'));
$headingMain = 'All Cohorts';
$headingHighlight = null;

if ($selectedFilter !== '' && strtolower($selectedFilter) !== 'all') {
    if (preg_match('/^Cohort\s+(.+)$/i', $selectedFilter, $m)) {
        $headingMain = 'Cohort';
        $headingHighlight = trim((string) ($m[1] ?? ''));
    } else {
        $headingMain = $selectedFilter;
    }
}
?>
<link rel="stylesheet" href="<?= base_url('assets/css/landingIncubatees.css') ?>">

<section id="incubatees" class="relative overflow-hidden py-14 md:py-20 px-6 md:px-10 lg:px-14 bg-off">
    <div class="max-w-[1200px] mx-auto">

        <!-- Section Header -->
        <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-4 mb-10 md:mb-12 reveal">
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="block w-[18px] h-[1.5px] bg-navy"></span>
                    <span class="text-[.58rem] font-semibold tracking-[.2em] uppercase text-navy">Incubatees</span>
                </div>
                <h2 class="font-display text-3xl md:text-[2.1rem] leading-[1.12] text-dark">
                    <?= esc($headingMain) ?><?php if (! empty($headingHighlight)): ?> <em
                        class=" text-gold"><?= esc($headingHighlight) ?></em><?php endif; ?>
                </h2>
            </div>
            <a href="<?= site_url('incubatees') ?>"
                class="text-[.6rem] font-semibold tracking-[.13em] uppercase text-dark no-underline border-b border-dark/40 pb-0.5 transition-colors duration-200 hover:text-gold hover:border-gold shrink-0">View
                All Incubatees →</a>
        </div>

        <div class="reveal reveal-d1">
            <?php if ($hasIncubatees): ?>
            <!-- Scrolling Logo Carousel -->
            <div class="inc-carousel">
                <div class="inc-track">
                    <?php for ($loop = 0; $loop < 2; $loop++): ?>
                    <?php foreach ($all as $inc): ?>
                    <div class="inc-logo-item"
                        title="<?= esc(html_entity_decode($inc['companyName'], ENT_QUOTES, 'UTF-8')) ?>">
                        <?php if (! empty($inc['logoPath'])): ?>
                        <?= responsiveUploadImg($inc['logoPath'], 'incubatees', html_entity_decode($inc['companyName'], ENT_QUOTES, 'UTF-8'), '', true) ?>
                        <?php else: ?>
                        <span
                            class="inc-initials"><?= strtoupper(substr(html_entity_decode($inc['companyName'], ENT_QUOTES, 'UTF-8'), 0, 2)) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endfor; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="rounded-xl border border-dark/10 bg-white/80 px-6 py-10 text-center">
                <p class="text-[.6rem] font-semibold tracking-[.14em] uppercase text-navy/70 mb-3">Coming Soon</p>
                <h3 class="font-display text-2xl md:text-3xl leading-tight text-dark mb-3">Will be announced soon</h3>
                <p class="text-sm md:text-base text-dark/70 max-w-[640px] mx-auto">
                    <?php if ($selectedFilter !== '' && strtolower($selectedFilter) !== 'all'): ?>
                    <?= esc($selectedFilter) ?> incubatees will be announced soon.
                    <?php else: ?>
                    New incubatees will be announced soon.
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
</section>