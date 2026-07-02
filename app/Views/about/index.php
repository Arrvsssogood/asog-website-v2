<!--
     ╔══════════════════════════════════════════════════════════════════════╗
    ║  ABOUT PAGE — TOC tabs (1/2/3/4) with single-panel view             ║
     ╚══════════════════════════════════════════════════════════════════════╝ 
-->

<section class="relative bg-off py-16 md:py-24 px-6 md:px-10 lg:px-14" data-toc-mode="tabs">
    <div class="ai-grid"></div>
    <div class="ai-grid-fade"></div>

    <div class="max-w-[1200px] mx-auto relative z-[2]">
        <div class="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-8 lg:gap-12">

            <!-- ── TOC Sidebar ── -->
            <?php
                $tocTitle = 'About Us';
                $tocItems = [
                    ['id' => 'about-panel-1', 'label' => 'Our Story'],
                    ['id' => 'about-panel-2', 'label' => 'Why Choose Us?'],
                    ['id' => 'about-panel-3', 'label' => 'Our Impact'],
                    ['id' => 'about-panel-4', 'label' => 'Logo'],
                ];
            ?>
            <?= view('templates/toc', compact('tocTitle', 'tocItems')) ?>

            <!-- ── Main Content ── -->
            <div class="min-w-0">
                <div class="max-w-[820px] text-[.95rem] font-normal leading-[1.72] text-left" style="color:#020d18">

                    <article id="about-panel-1" data-toc-panel>
                        <h2 class="font-display text-[1.8rem] md:text-[2.2rem] leading-[1.18] text-dark mb-4">Our Story
                        </h2>
                        <p class="mb-2 text-[.78rem] font-semibold tracking-wide uppercase text-gold/80">Who We Are</p>
                        <p class="mb-2.5">
                            The ASOG Technology Business Incubator (TBI) is an initiative of Camarines Sur Polytechnic
                            Colleges (CSPC) aimed at fostering Engineering and AI-based innovations for food value chain
                            management. Our mission is to empower startups and Micro, Small, and Medium Enterprises
                            (MSMEs)
                            with the resources, mentorship, and support they need to develop cutting-edge solutions that
                            enhance efficiency, productivity, and sustainability in the food industry.
                        </p>
                        <p class="mb-2.5">
                            It is funded by the Department of Science and Technology - Philippine Council for Industry,
                            Energy and Emerging Technology Research and Development (DOST-PCIEERD) and co-monitored by
                            DOST
                            Region V.
                        </p>
                        <p class="mb-2 text-[.78rem] font-semibold tracking-wide uppercase text-gold/80">What We Do</p>
                        <p class="mb-2.5">At ASOG TBI, we incubate and accelerate startups by providing them with:</p>
                        <ul class="space-y-2 list-none pl-0 mb-3">
                            <li class="flex gap-3 items-start"><span
                                    class="text-gold text-lg leading-none mt-1">•</span><span><strong
                                        class="text-dark/75">Access to state-of-the-art facilities</strong> like the AI
                                    Research Center for Community Development (AIRCoDe), Fabrication Laboratory
                                    (FabLab),
                                    Rinconada Food Processing Hub - Shared Service Facility (SSF), and Business
                                    Incubation
                                    Center</span></li>
                            <li class="flex gap-3 items-start"><span
                                    class="text-gold text-lg leading-none mt-1">•</span><span><strong
                                        class="text-dark/75">Mentorship and training</strong> from industry experts,
                                    engineers, AI specialists, and business leaders</span></li>
                            <li class="flex gap-3 items-start"><span
                                    class="text-gold text-lg leading-none mt-1">•</span><span><strong
                                        class="text-dark/75">Support in prototyping, market validation, and technology
                                        transfer</strong></span></li>
                            <li class="flex gap-3 items-start"><span
                                    class="text-gold text-lg leading-none mt-1">•</span><span><strong
                                        class="text-dark/75">Networking opportunities</strong> with investors, venture
                                    capitalists,
                                    and government agencies</span></li>
                            <li class="flex gap-3 items-start"><span
                                    class="text-gold text-lg leading-none mt-1">•</span><span><strong
                                        class="text-dark/75">Intellectual property assistance</strong> to secure patents
                                    and
                                    trademarks</span></li>
                        </ul>
                    </article>

                    <article id="about-panel-2" data-toc-panel class="hidden">
                        <h2 class="font-display text-[1.8rem] md:text-[2.2rem] leading-[1.18] text-dark mb-4">Why Choose
                            Us?</h2>
                        <p class="mb-2 text-[.78rem] font-semibold tracking-wide uppercase text-gold/80">Why ASOG?</p>
                        <p class="mb-2.5">
                            ASOG TBI stands out because we integrate expertise from <strong
                                class="text-dark/75">Academe,
                                Society, Organizations, and Government</strong> (ASOG) to build a robust innovation
                            ecosystem. We work closely with DOST-PCIEERD, other national government agencies, and local
                            industries to bridge the gap between research and commercialization, ensuring that AI-driven
                            solutions are practical, market-ready, and impactful.
                        </p>

                        <p class="mb-2 text-[.78rem] font-semibold tracking-wide uppercase text-gold/80">Who Can Join?
                        </p>
                        <p class="mb-2.5">We support a diverse group of innovators, researchers, and entrepreneurs,
                            including:</p>
                        <ul class="space-y-2 list-none pl-0">
                            <li class="flex gap-3 items-start"><span
                                    class="text-gold text-lg leading-none mt-1">•</span><span><strong
                                        class="text-dark/75">Startups &amp; MSMEs</strong> in food value chain
                                    management</span></li>
                            <li class="flex gap-3 items-start"><span
                                    class="text-gold text-lg leading-none mt-1">•</span><span><strong
                                        class="text-dark/75">Students &amp; Faculty Researchers</strong> from CSPC and
                                    partner institutions</span></li>
                            <li class="flex gap-3 items-start"><span
                                    class="text-gold text-lg leading-none mt-1">•</span><span><strong
                                        class="text-dark/75">Tech enthusiasts &amp; AI innovators</strong> exploring AI
                                    applications in
                                    the food industry</span></li>
                        </ul>
                    </article>

                    <article id="about-panel-3" data-toc-panel class="hidden">
                        <h2 class="font-display text-[1.8rem] md:text-[2.2rem] leading-[1.18] text-dark mb-4">Our Impact
                        </h2>
                        <p class="mb-2.5">We aim to revolutionize the food industry in Camarines Sur and beyond by:</p>
                        <ul class="space-y-2 list-none pl-0">
                            <li class="flex gap-3 items-start"><span
                                    class="text-gold text-lg leading-none mt-1">•</span><span>Improving
                                    food production, processing, and distribution using AI-driven solutions</span></li>
                            <li class="flex gap-3 items-start"><span
                                    class="text-gold text-lg leading-none mt-1">•</span><span>Increasing
                                    profitability and efficiency for MSMEs</span></li>
                            <li class="flex gap-3 items-start"><span
                                    class="text-gold text-lg leading-none mt-1">•</span><span>Strengthening
                                    the regional startup ecosystem through strategic partnerships</span></li>
                            <li class="flex gap-3 items-start"><span
                                    class="text-gold text-lg leading-none mt-1">•</span><span>Creating
                                    employment opportunities and promoting inclusive economic growth</span></li>
                        </ul>
                    </article>

                    <article id="about-panel-4" data-toc-panel class="hidden">
                        <h2 class="font-display text-[1.8rem] md:text-[2.2rem] leading-[1.18] text-dark mb-4">ASOG TBI
                            Logo</h2>

                        <div class="grid grid-cols-2 gap-2 md:gap-6 mb-8 max-w-[720px] mx-auto place-items-center">
                            <div
                                class="p-1 md:p-2 flex items-center justify-center min-h-[140px] md:min-h-[220px] w-full">
                                <?= responsiveStaticImg('assets/img/ASOG TBI/WebP/ASOG-TBI_full-colored_stacked', 'default', 'ASOGTBI Full Colored Stacked Logo', 'w-auto max-w-full h-[120px] sm:h-[140px] md:h-[190px] object-contain block select-none', true) ?>
                            </div>
                            <div
                                class="p-1 md:p-2 flex items-center justify-center min-h-[140px] md:min-h-[220px] w-full">
                                <?= responsiveStaticImg('assets/img/ASOG TBI/WebP/ASOG-TBI_seal', 'default', 'ASOG TBI Seal', 'w-auto max-w-full h-[120px] sm:h-[140px] md:h-[190px] object-contain block select-none', true) ?>
                            </div>
                        </div>

                        <p class="mb-2.5">
                            The ASOG TBI logo embodies the institution's mission to advance innovation, engineering, and
                            technology-driven entrepreneurship through strong collaboration among the academe, society,
                            organizations, and government.
                        </p>
                        <p class="mb-2.5"><strong>The Mountain: Grounded Innovation and Local Identity</strong><br>
                            A prominent element of the logo is a stylized mountain, a direct reference to Mount Asog,
                            from which the incubator derives its name. The mountain anchors the TBI's identity to place,
                            symbolizing stability, resilience, and grounded problem-solving rooted in the needs of local
                            communities. Beyond its geographic meaning, the mountain represents the challenges that
                            innovators and startups confront and the steady ascent toward scalable, impact-driven
                            solutions.
                        </p>
                        <p class="mb-2.5"><strong>The Rising Sun-Gear: Engineering, Growth, and Polytechnic
                                Excellence</strong><br>
                            Behind the mountain rises a sun rendered in the form of a gear, merging two powerful ideas
                            into a single visual element. As the sun, it signifies growth, opportunity, and forward
                            momentum. As a gear, it reflects CSPC's identity as a polytechnic institution and
                            underscores
                            the central role of engineering, applied sciences, and technical rigor in the incubator's
                            work.
                            The gear conveys precision, systems thinking, and the transformation of ideas into
                            functional technologies.
                        </p>
                        <p class="mb-2.5"><strong>The Sparkles: Artificial Intelligence and Intelligent
                                Systems</strong><br>
                            Within the sun appear sparkle elements, representing artificial intelligence and emerging
                            digital technologies. The sparkles suggest clarity, innovation, and enhancement, qualities
                            often associated with intelligent systems that augment human capability. In contemporary
                            design
                            language, sparkles have emerged as a widely recognized symbol for AI-driven features. In the
                            ASOG TBI logo, these elements signify the integration of human intuition with machine
                            learning,
                            highlighting the incubator's focus on solutions that are both technically advanced and
                            human-centered.
                        </p>
                        <p class="mb-2.5"><strong>Color Meaning: Intelligence and Optimism</strong><br>
                            The logo's color palette reinforces these meanings. Blue represents wisdom, intelligence,
                            and
                            depth of thought, aligning with research, learning, and data-driven decision-making. Yellow
                            conveys optimism, energy, and creative momentum, reflecting the incubator's role in
                            energizing
                            startups and fostering an environment of possibility and growth.
                        </p>
                    </article>
                </div>
            </div>
        </div>
    </div>
</section>