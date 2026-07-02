<!-- ╔══════════════════════════════════════════════════════════════════════╗
     ║  SECTION: FACILITIES                                               ║
     ║  Light bg · 2-column photo cards                                   ║
     ╚══════════════════════════════════════════════════════════════════════╝ -->
<section id="facilities" class="relative overflow-hidden bg-off py-16 md:py-24 px-6 md:px-10 lg:px-14">
    <div class="ai-grid"></div>
    <div class="ai-grid-fade"></div>
    <div class="ai-cross hidden lg:block" style="top:14%;right:28%"></div>
    <div class="ai-cross hidden lg:block" style="bottom:20%;left:10%"></div>

    <div class="max-w-[1200px] mx-auto relative z-[2]">
        <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-4 mb-10 md:mb-14 reveal">
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="block w-[18px] h-[1.5px] bg-navy"></span>
                    <span class="text-[.58rem] font-semibold tracking-[.2em] uppercase text-navy">Infrastructure</span>
                </div>
                <h2 class="font-display text-3xl md:text-[2.1rem] leading-[1.12] text-dark">Our <em
                        class="italic text-gold">Facilities</em></h2>
            </div>
            <a href="<?= site_url('programs#co-lab') ?>"
                class="text-[.6rem] font-semibold tracking-[.13em] uppercase text-dark/[.28] no-underline border-b border-dark/[.12] pb-0.5 transition-colors duration-200 hover:text-gold hover:border-gold shrink-0">View
                All Facilities →</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 reveal-group">
            <?php if (! empty($facilities)): ?>
            <?php foreach ($facilities as $fac): ?>
            <a href="<?= site_url('facilities/' . $fac['slug']) ?>"
                class="rc rounded-lg border border-dark/[.06] overflow-hidden bg-white shadow-sm shadow-dark/[.04] transition-all duration-300 hover:shadow-md hover:shadow-dark/[.08] no-underline block">
                <?php if (! empty($fac['imagePath'])): ?>
                <div class="h-[180px] md:h-[200px] bg-[#e9e6e1]">
                    <img src="<?= base_url($fac['imagePath']) ?>" alt="<?= esc($fac['name']) ?>" width="1200" height="800"
                        class="w-full h-full object-cover">
                </div>
                <?php else: ?>
                <div class="h-[180px] md:h-[200px] bg-[#e9e6e1] flex items-center justify-center">
                    <span class="text-[.6rem] font-semibold tracking-[.2em] uppercase text-dark/15">Photo
                        Placeholder</span>
                </div>
                <?php endif; ?>
                <div class="p-5 md:p-7">
                    <h3 class="font-display text-[1.1rem] text-dark mb-2"><?= esc($fac['name']) ?></h3>
                    <p class="text-[.8rem] font-light leading-[1.75] text-dark/40">
                        <?= esc($fac['shortDescription'] ?? '') ?></p>
                </div>
            </a>
            <?php endforeach; ?>
            <?php else: ?>
            <!-- Fallback static cards -->
            <?php foreach (['AIRCoDe', 'FabLab', 'Co-Working Space', 'Conference Room'] as $name): ?>
            <div class="rc rounded-lg border border-dark/[.06] overflow-hidden bg-white shadow-sm shadow-dark/[.04]">
                <div class="h-[180px] md:h-[200px] bg-[#e9e6e1] flex items-center justify-center">
                    <span class="text-[.6rem] font-semibold tracking-[.2em] uppercase text-dark/15">Photo
                        Placeholder</span>
                </div>
                <div class="p-5 md:p-7">
                    <h3 class="font-display text-[1.1rem] text-dark mb-2"><?= $name ?></h3>
                    <p class="text-[.8rem] font-light leading-[1.75] text-dark/40">Lorem ipsum dolor sit amet,
                        consectetur adipiscing elit.</p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>