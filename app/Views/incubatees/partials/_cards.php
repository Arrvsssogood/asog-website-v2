<!-- Card Grid -->
<div id="ibStack" class="ib-stack flex flex-wrap gap-5 justify-center relative">
    <?php foreach ($incubatees as $i => $inc): ?>
    <div class="ib-card cursor-pointer relative" id="<?= esc(incubatee_anchor_id($inc)) ?>" data-ix="<?= $i ?>">
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
                        <?php if (!empty($inc['logoPath'])): ?>
                            <img src="<?= base_url(esc($inc['logoPath'])) ?>" alt="<?= esc($inc['companyName']) ?>">
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
                <span class="ib-back-cohort relative shrink-0"><?= esc(preg_replace('/\s*[·•|\-–—]\s*\d{4}/', '', $inc['cohort'] ?? '')) ?></span>
                <!-- Mobile: See More button on back face -->
                <button class="ib-see-more relative shrink-0" data-ix="<?= $i ?>">
                    See More
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

        </div>
    </div>
    <?php endforeach; ?>
</div>
