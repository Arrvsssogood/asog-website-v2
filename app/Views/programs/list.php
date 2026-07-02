<!-- ╔══════════════════════════════════════════════════════════════════════╗
     ║  PROGRAMS & SERVICES — TOC + ALTITUDE, Services, Facilities         ║
     ╚══════════════════════════════════════════════════════════════════════╝ -->
<!-- Programs page wrapper -->
<div class="relative bg-off">
    <div class="max-w-[1320px] mx-auto px-6 md:px-10 lg:px-14">
        <div class="min-w-0">
            <!-- ╔═══════════════════════════════════════════════════════════════════╗
                 ║  SECTION 1: ALTITUDE PROGRAM                                      ║
                 ╚═══════════════════════════════════════════════════════════════════╝ -->
            <section class="relative py-20 md:py-25" data-navhint="blue">

                <div id="altitude-program" class="scroll-mt-28"></div>

                <div class="max-w-[1120px] mx-auto relative z-[2]">
                    <div id="altitudeExperienceRoot" class="altitude-exp-root">
                        <div id="altitudeLandingPage" class="altitude-exp-page altitude-exp-landing is-active">
                            <div class="altitude-exp-hero">
                                <div class="altitude-exp-mtn" aria-hidden="true">
                                    <svg viewBox="0 0 720 360" fill="none" preserveAspectRatio="none">
                                        <path
                                            d="M0 360L88 228L158 274L260 136L352 232L442 92L556 210L652 126L720 192V360H0Z"
                                            fill="rgba(6,34,56,.34)" />
                                        <path
                                            d="M0 360L64 252L146 292L236 176L330 248L436 122L530 214L630 152L720 206V360H0Z"
                                            fill="rgba(8,44,70,.42)" />
                                        <path
                                            d="M0 360L46 286L120 314L210 228L306 286L400 176L496 256L600 202L720 248V360H0Z"
                                            fill="rgba(14,61,90,.5)" />
                                    </svg>
                                </div>
                                <div class="altitude-exp-hero-content">
                                    <!-- <p class="altitude-exp-kicker">ASOG TBI Incubation Program</p> -->
                                    <h1 class="altitude-exp-title">ALTITUDE Program</h1>
                                    <p class="altitude-exp-subtitle">ALTITUDE (Advancing Local Technology and
                                        Innovation through Transformative Upskilling, Development, and
                                        Entrepreneurship) is the official incubation program of the ASOG Technology
                                        Business Incubator. The program supports early-stage startups by providing
                                        structured guidance, mentorship, and resources that help transform innovative
                                        ideas into viable ventures.
                                        ALTITUDE follows a staged incubation approach designed to guide startups from
                                        idea development to scaling. Each stage focuses on specific milestones that help
                                        founders refine their technology, validate market demand, and prepare for
                                        sustainable growth.</p>
                                    <button id="altitudeEnterProgram" type="button"
                                        class="altitude-exp-enter-btn">Explore the Program</button>
                                </div>
                            </div>
                        </div>

                        <div id="altitudeProgramPage" class="altitude-exp-page altitude-exp-program" hidden>
                            <!-- Ghost trigger keeps the 3D module's click handler alive -->
                            <div id="altitudeExploreCard" role="button" tabindex="-1"
                                aria-label="Open ALTITUDE interactive view" style="display:none"></div>
                        </div>
                    </div>
                </div>

            </section>
        </div>
    </div>
</div>

<!-- ╔══════════════════════════════════════════════════════════════════════╗
     ║  ALTITUDE 3D — Wilderness Zoom Overlay + Fullscreen 3D Scene         ║
     ╚══════════════════════════════════════════════════════════════════════╝ -->

<!-- Phase 1: Wilderness Zoom Transition Overlay -->
<div id="alt3dZoomOverlay" class="alt3d-zoom-overlay">
    <div class="alt3d-zoom-mountains">
        <svg viewBox="0 0 1440 800" preserveAspectRatio="none">
            <path id="alt3dMtnFar" class="alt3d-mtn-far"
                d="M0 800 L200 400 L360 520 L540 280 L720 450 L900 200 L1100 380 L1300 180 L1440 350 L1440 800Z" />
            <path id="alt3dMtnMid" class="alt3d-mtn-mid"
                d="M0 800 L140 480 L300 560 L480 320 L660 500 L840 260 L1020 420 L1200 220 L1380 380 L1440 440 L1440 800Z" />
            <path id="alt3dMtnNear" class="alt3d-mtn-near"
                d="M0 800 L100 560 L260 620 L400 440 L560 580 L720 380 L880 520 L1060 340 L1240 480 L1440 520 L1440 800Z" />
        </svg>
    </div>
    <div id="alt3dZoomText" class="alt3d-zoom-text" style="opacity:0; transform:translateY(20px);">
        <p class="zt-eyebrow">ASOG-TBI · The ALTITUDE Program</p>
        <h2 class="zt-title">The Journey to Summit</h2>
    </div>
</div>

<!-- Phase 2: Fullscreen 3D Scene Overlay -->
<div id="alt3dOverlay" class="alt3d-overlay">
    <canvas id="alt3dCanvas"></canvas>

    <!-- Close button -->
    <button id="alt3dClose" class="alt3d-close" aria-label="Close 3D view">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
            stroke-linejoin="round">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
        </svg>
    </button>

    <!-- Small info toggle (replaces top title) -->
    <div class="alt3d-mini-info-wrap">
        <button id="alt3dInfoBtn" class="alt3d-mini-info-btn" aria-label="About this experience" aria-expanded="false">
            <span aria-hidden="true">i</span>
        </button>
    </div>

    <div id="alt3dInfoModal" class="alt3d-info-modal" hidden>
        <div class="alt3d-info-modal-card">
            <button id="alt3dInfoModalClose" class="alt3d-info-modal-close"
                aria-label="Close program information">&times;</button>
            <h3>ALTITUDE Program</h3>
            <p>ALTITUDE - Advancing Local Technology and Innovation through Transformative Upskilling, Development, and
                Entrepreneurship - is the official incubation program of the ASOG Technology Business Incubator.</p>
            <p>The program supports early-stage startups by providing structured guidance, mentorship, and resources
                that help transform innovative ideas into viable ventures.</p>
            <p>ALTITUDE follows a staged incubation approach designed to guide startups from idea development to
                scaling. Each stage focuses on specific milestones that help founders refine their technology, validate
                market demand, and prepare for sustainable growth.</p>
        </div>
    </div>

    <!-- Flag labels container -->
    <div id="alt3dLabels" class="alt3d-labels"></div>

    <!-- Info card -->
    <div id="alt3dInfo" class="alt3d-info">
        <div class="ci-num" id="ciNum"></div>
        <div class="ci-name" id="ciName"></div>
        <div class="ci-phase" id="ciPhase"></div>
        <div class="ci-dur" id="ciDur"></div>
        <button id="ciBtnAboutStep" class="ci-btn-about" type="button">Read More</button>
        <div class="ci-desc" id="ciDesc"></div>
        <div class="ci-nav">
            <button class="ci-btn-main" id="ciBtnPrev">&larr; Prev</button>
            <button class="ci-btn-main" id="ciBtnNext">Next &rarr;</button>
            <button class="ci-btn-close" id="ciBtnOverview">&times; Overview</button>
        </div>
    </div>

    <div id="alt3dStepModal" class="alt3d-step-modal" hidden>
        <div class="alt3d-step-modal-card">
            <button id="alt3dStepModalClose" class="alt3d-step-modal-close"
                aria-label="Close step details">&times;</button>
            <div class="ci-num" id="ciStepNum"></div>
            <div class="ci-name" id="ciStepName"></div>
            <div class="ci-phase" id="ciStepPhase"></div>
            <div class="ci-dur" id="ciStepDur"></div>
            <div class="ci-desc" id="ciStepDesc"></div>
        </div>
    </div>

    <!-- Progress dots -->
    <div id="alt3dDots" class="alt3d-dots">
        <button class="alt3d-dot" data-i="0"></button>
        <button class="alt3d-dot" data-i="1"></button>
        <button class="alt3d-dot" data-i="2"></button>
        <button class="alt3d-dot" data-i="3"></button>
        <button class="alt3d-dot" data-i="4"></button>
    </div>

    <!-- Instruction Panel -->
    <div id="alt3dHint" class="alt3d-hint alt3d-instruction-panel">
        <div class="alt3d-instruction-content">
            <span class="alt3d-instruction-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                    <circle cx="12" cy="10" r="3" />
                </svg>
            </span>
            <span class="alt3d-instruction-text">Click a checkpoint to explore the journey</span>
            <span class="alt3d-instruction-esc">Press <kbd>ESC</kbd> to exit fullscreen</span>
        </div>
        <button id="alt3dHintClose" class="alt3d-instruction-exit-btn" type="button" aria-label="Exit 3D view">Exit</button>
    </div>

    <!-- <button id="alt3dShowPartners" class="alt3d-mentors-btn" type="button" aria-controls="alt3dPartnersPanel"
        aria-expanded="false">
        Industry Partners <span class="alt3d-mentors-btn-arrow" aria-hidden="true">↓</span>
    </button> -->


</div>

<div id="altitudeProgramModal" class="alt3d-info-modal" hidden>
    <div class="alt3d-info-modal-card">
        <button id="altitudeProgramModalClose" class="alt3d-info-modal-close"
            aria-label="Close ALTITUDE program details">&times;</button>
        <h3>ALTITUDE Program</h3>
        <p>ALTITUDE - Advancing Local Technology and Innovation through Transformative Upskilling, Development, and
            Entrepreneurship - is the official incubation program of the ASOG Technology Business Incubator.</p>
        <p>The program supports early-stage startups by providing structured guidance, mentorship, and resources that
            help transform innovative ideas into viable ventures.</p>
        <p>ALTITUDE follows a staged incubation approach designed to guide startups from idea development to scaling.
            Each stage focuses on specific milestones that help founders refine their technology, validate market
            demand, and prepare for sustainable growth.</p>
    </div>
</div>

<!-- Three.js + Altitude 3D Module Url Definition -->
<script>
window.altitude3DScriptUrl = "<?= base_url('assets/js/altitude/main.js') ?>";
</script>
<script src="<?= base_url('assets/js/features/programs/programsAltitudePage.js') ?>" defer></script>