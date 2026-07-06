<section class="relative bg-off pt-32 pb-16 md:pt-36 md:pb-20 px-6 md:px-10 lg:px-14">
    <div class="ai-grid"></div>
    <div class="ai-grid-fade"></div>

    <div class="relative z-[2] w-full max-w-[620px] mx-auto text-center">
        <div class="w-14 h-14 mx-auto rounded-full bg-gold/10 border border-gold/20 flex items-center justify-center mb-6">
            <?php if (($noticeState ?? '') === 'upcoming'): ?>
                <svg class="w-7 h-7 text-gold" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/>
                </svg>
            <?php else: ?>
                <svg class="w-7 h-7 text-gold" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-center gap-2 mb-5">
            <span class="block w-[18px] h-[2px] bg-gold"></span>
            <p class="text-[.56rem] font-bold tracking-[.2em] uppercase text-gold m-0">Application Period</p>
            <span class="block w-[18px] h-[2px] bg-gold"></span>
        </div>

        <h1 class="font-display text-[1.75rem] md:text-[2.15rem] text-dark leading-tight mb-4">
            <?= esc((string) ($noticeTitle ?? 'Applications are closed')) ?>
        </h1>
        <p class="text-[.88rem] font-light leading-[1.85] text-dark/60 max-w-[580px] mx-auto mb-10">
            <?= esc((string) ($noticeMessage ?? 'The application form is currently unavailable. Please check back later.')) ?>
        </p>
        <a href="<?= site_url('apply') ?>"
            class="inline-block font-body text-[.72rem] font-medium tracking-[.14em] uppercase text-white bg-sky border border-sky px-8 md:px-10 py-4 rounded-sm no-underline transition-all duration-200 hover:bg-sky/80 hover:-translate-y-0.5">
            Application overview
        </a>
    </div>
</section>
