<!--

     ╔══════════════════════════════════════════════════════════════════════╗
     ║  SECTION: ABOUT US                                                   ║
     ║  Light bg (#F8F6F2) · Who We Are · Description ng ASOG TBI           ║
     ╚══════════════════════════════════════════════════════════════════════╝ 
     
-->
<section id="about" class="relative overflow-hidden bg-off pt-12 pb-16 md:py-24 px-6 md:px-10 lg:px-14">
    <div class="grid grid-cols-1 lg:grid-cols-[280px_1px_1fr] gap-5 lg:gap-14 items-start relative z-[1]">

        <!-- Left heading -->

        <div class="reveal mt-3 md:mt-0">
            <div class="flex items-center gap-2 mb-3">
                <span class="block w-[18px] h-[1.5px] bg-navy"></span>
                <span class="text-[.58rem] font-semibold tracking-[.2em] uppercase text-navy">Who We Are</span>
            </div>
            <h2 class="font-display text-3xl md:text-[2.1rem] leading-[1.12] text-navy">
                Built for <em class="italic text-gold">Bicol's</em> Future
            </h2>
        </div>

        <!-- Divider (desktop only) -->

        <div class="hidden lg:block bg-navy/15 reveal reveal-d1"></div>

        <!-- Right body -->

        <div class="reveal reveal-d2">
            <div class="text-sm md:text-base font-light leading-[1.72] md:leading-[2.0] mb-5 text-left"
                style="color:#020d18;">
                <p>The ASOG Technology Business Incubator (TBI) is an initiative of Camarines Sur Polytechnic
                    Colleges (CSPC), funded by DOST-PCIEERD in coordination with DOST Region V,
                    aimed at fostering Engineering and AI-based innovations for food value chain management.
                </p>
                <p>
                    Our mission is to empower startups and Micro, Small, and Medium Enterprises (MSMEs) with the
                    resources, mentorship, and the support they need to develop cutting-edge solutions that enhance
                    efficiency, productivity, and sustainability in the food industry.</p>
            </div>

            <!-- Partner Organization Logos -->
            <div class="mt-8 mb-6">
                <span class="text-[.52rem] font-bold tracking-[.18em] uppercase text-navy/80 block mb-4">Supported
                    by</span>
                <div class="flex flex-wrap items-end gap-4 sm:gap-5 md:gap-6">
                    <a href="https://pcieerd.dost.gov.ph/" target="_blank" rel="noopener" title="PCIEERD"
                        class="inline-flex items-center justify-center touch-manipulation w-16 h-11 sm:w-[72px] sm:h-[50px] md:w-[88px] md:h-[62px] transition-transform duration-300 md:hover:scale-105">
                        <?= responsiveStaticImg('assets/img/partners/pcieerd', 'partner', 'PCIEERD', 'w-full h-full object-contain', true) ?>
                    </a>
                    <a href="https://region5.dost.gov.ph/" target="_blank" rel="noopener" title="DOST Region V"
                        class="inline-flex items-center justify-center touch-manipulation w-16 h-11 sm:w-[72px] sm:h-[50px] md:w-[88px] md:h-[62px] transition-transform duration-300 md:hover:scale-105">
                        <?= responsiveStaticImg('assets/img/partners/dost-region5', 'partner', 'DOST Region V', 'w-full h-full object-contain', true) ?>
                    </a>
                    <a href="https://cspc.edu.ph/" target="_blank" rel="noopener" title="CSPC"
                        class="inline-flex items-center justify-center touch-manipulation w-16 h-11 sm:w-[72px] sm:h-[50px] md:w-[88px] md:h-[62px] transition-transform duration-300 md:hover:scale-105">
                        <?= responsiveStaticImg('assets/img/partners/cspc', 'partner', 'CSPC', 'w-full h-full object-contain', true) ?>
                    </a>
                </div>
            </div>
            <a href="<?= site_url('about') ?>"
                class="group inline-flex items-center gap-1.5 md:gap-2 mt-5 text-[.65rem] font-bold tracking-[.13em] uppercase text-navy no-underline transition-[gap,color] duration-200 hover:text-gold md:hover:gap-3">
                Read More <span class="transition-transform duration-200 group-hover:translate-x-1">→</span>
            </a>
        </div>
    </div>
</section>