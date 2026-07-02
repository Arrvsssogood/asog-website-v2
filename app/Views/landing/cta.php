<!-- ╔══════════════════════════════════════════════════════════════════════╗
     ║  SECTION: CTA / BE AN INCUBATEE                                      ║
     ║  Dark bg · centered call-to-action                                   ║
     ╚══════════════════════════════════════════════════════════════════════╝ -->
<?php
    $ctaTitle       = 'Ready to build your <em class="italic text-gold">business</em> with us?';
    $ctaDesc        = 'Apply to the ASOG TBI incubation program and get access to state-of-the-art facilities, mentorship, and funding opportunities.';
    $ctaBtnText     = 'Be an Incubatee';
    $ctaBtnUrl      = site_url('apply');
    $ctaSecText     = 'Explore Our Program';
    $ctaSecUrl      = site_url('programs');
?>
<section id="cta"
    class="relative overflow-hidden py-20 md:py-28 px-6 md:px-10 lg:px-14 flex flex-col items-center text-center">
    <div class="absolute inset-0 bg-cover bg-center scale-105"
        style="background-image:url('<?= base_url('assets/img/incubatees.webp') ?>');">
    </div>
    <div class="absolute inset-0 bg-[rgba(3,33,52,.82)]"></div>
    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-white/[.06] to-transparent">
    </div>
    <div class="reveal">
        <div
            class="text-[.55rem] md:text-[.58rem] font-semibold tracking-[.22em] uppercase text-white/50 mb-5 relative z-[2]">
            Join the Ecosystem</div>
        <h2
            class="font-display text-[clamp(1.8rem,3.5vw,3rem)] leading-[1.1] text-off max-w-[620px] mb-4 relative z-[2]">
            <?= $ctaTitle ?></h2>
        <p
            class="text-[.85rem] md:text-[.95rem] font-light leading-[1.75] text-white/45 max-w-[480px] mx-auto mb-10 md:mb-12 relative z-[2]">
            <?= esc($ctaDesc) ?></p>
    </div>
    <div class="flex flex-col sm:flex-row gap-4 flex-wrap justify-center relative z-[2] reveal reveal-d1">
        <a href="<?= esc($ctaBtnUrl) ?>"
            class="font-body text-[.72rem] font-medium tracking-[.14em] uppercase text-white bg-sky border border-sky px-8 md:px-10 py-4 rounded-sm no-underline transition-all duration-200 hover:bg-sky/80 hover:-translate-y-0.5 text-center"><?= esc($ctaBtnText) ?></a>
        <a href="<?= esc($ctaSecUrl) ?>"
            class="font-body text-[.72rem] font-medium tracking-[.14em] uppercase text-white/[.60] border border-white/[.15] px-8 md:px-10 py-4 rounded-sm no-underline transition-colors duration-200 hover:border-sky hover:text-sky text-center"><?= esc($ctaSecText) ?></a>
    </div>
</section>