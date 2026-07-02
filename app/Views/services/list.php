<?php
$services = [
    [
        'title' => 'Startup Incubation',
        'kicker' => 'Support includes:',
        'desc' => 'ASOG TBI offers a staged incubation pathway through the ALTITUDE Program, guiding startups from early idea validation to market readiness and long-term growth.',
        'items' => ['Problem and solution validation', 'Startup readiness assessment', 'Business model development', 'Product development guidance', 'Market preparation and scaling support'],
        'image' => 'assets/img/offered/incubation.webp',
        'alt' => 'Startup incubation workspace',
        'tone' => 'tone-blue',
    ],
    [
        'title' => 'Mentorship and Expert Guidance',
        'kicker' => 'Mentorship formats include:',
        'desc' => 'Startups receive access to mentors from industry, academe, and government who provide technical and business advice throughout the incubation journey.',
        'items' => ['One-on-one mentoring', 'Expert clinics', 'Technical consultations', 'Pitch rehearsals', 'Demo day coaching'],
        'image' => 'assets/img/offered/activity.webp',
        'alt' => 'Mentorship and advisory session',
        'tone' => 'tone-sand',
    ],
    [
        'title' => 'Prototype Development Support',
        'kicker' => 'Support includes:',
        'desc' => 'ASOG TBI helps startup teams refine product concepts into working prototypes through technical guidance and access to fabrication resources.',
        'items' => ['Prototype planning and refinement', 'Product modeling assistance', 'Access to CSPC fabrication resources', 'Technical review sessions'],
        'image' => 'assets/img/offered/prototype.webp',
        'alt' => 'Prototype development laboratory',
        'tone' => 'tone-ice',
    ],
    [
        'title' => 'Business Development Support',
        'kicker' => 'Services include:',
        'desc' => 'The incubator helps founders strengthen their market strategy and prepare for commercial viability.',
        'items' => ['Customer discovery support', 'Market validation', 'Pricing and positioning guidance', 'Go to market preparation', 'Partnership building'],
        'image' => 'assets/img/offered/support.webp',
        'alt' => 'Business development consultation',
        'tone' => 'tone-blue',
    ],
    [
        'title' => 'Intellectual Property and Innovation Support',
        'kicker' => 'This includes:',
        'desc' => 'Startups receive guidance on protecting innovations and understanding intellectual property pathways.',
        'items' => ['Patent search orientation', 'IP awareness sessions', 'Referral for intellectual property support', 'Innovation documentation guidance'],
        'image' => 'assets/img/offered/ip.webp',
        'alt' => 'Intellectual property support center',
        'tone' => 'tone-sand',
    ],
    [
        'title' => 'Capacity Building Activities',
        'kicker' => 'Topics may include:',
        'desc' => 'ASOG TBI regularly conducts workshops, huddles, and learning sessions that build entrepreneurial and technical capability.',
        'items' => ['Branding essentials', 'Startup pitching', 'Product validation', 'Technology trends', 'Business communication'],
        'image' => 'assets/img/offered/activity.webp',
        'alt' => 'Capacity building workshop',
        'tone' => 'tone-ice',
    ],
    [
        'title' => 'Networking and Ecosystem Linkages',
        'kicker' => 'This includes access to:',
        'desc' => 'The incubator connects startups to opportunities beyond the campus through engagement with partners and innovation networks.',
        'items' => ['Government innovation programs', 'Startup events and conferences', 'Industry consultations', 'Local ecosystem partnerships'],
        'image' => 'assets/img/offered/networking.webp',
        'alt' => 'Networking and ecosystem event',
        'tone' => 'tone-blue',
    ],
    [
        'title' => 'Funding Readiness Support',
        'kicker' => 'Support includes:',
        'desc' => 'For startups entering advanced stages, ASOG TBI helps founders prepare for grants, investor discussions, and strategic funding opportunities.',
        'items' => ['Pitch deck review', 'Grant preparation guidance', 'Funding document support', 'Investor readiness coaching'],
        'image' => 'assets/img/offered/funding.webp',
        'alt' => 'Funding readiness and pitching',
        'tone' => 'tone-sand',
    ],
];
?>

<link rel="stylesheet" href="<?= base_url('assets/css/services.css') ?>">

<section class="svc-experience relative text-black">
    <div class="mx-auto max-w-[1120px] px-6 sm:px-9 md:px-12 lg:px-16">
        <header class="svc-hero pt-12 pb-9 md:pt-14 md:pb-10 lg:pt-16 lg:pb-12 text-left">
            <div class="max-w-[940px]">
                <div class="mb-4 flex items-center gap-2">
                    <span class="svc-kicker-line block h-[1.5px] w-7 bg-gold"></span>
                    <span class="text-[.58rem] font-semibold tracking-[.24em] uppercase text-gold"> What we offer</span>
                    <span class="svc-kicker-line block h-[1.5px] w-7 bg-gold"></span>
                </div>

                <h1
                    class="svc-hero-title svc-theme-title font-display text-[2rem] leading-[1.12] sm:text-[2.35rem] md:text-[2.85rem] lg:text-[3.1rem] text-navy max-w-[18ch]">
                    Supporting startups from idea to scale
                </h1>
                <p class="svc-hero-copy mt-5 max-w-[80ch] text-[1rem] md:text-[1.06rem] leading-[1.8] text-black">
                    The ASOG Technology Business Incubator (ASOG TBI) provides structured support for early-stage
                    startups, innovators, and technology-driven ventures through a range of incubation services designed
                    to help ideas move toward real-world application.
                </p>
                <p class="svc-hero-copy mt-3 max-w-[80ch] text-[1rem] md:text-[1.06rem] leading-[1.8] text-black">
                    Our services focus on strengthening technical development, business readiness, and strategic
                    connections, particularly in engineering, artificial intelligence, and food value chain innovation.
                </p>
            </div>
        </header>

        <div class="space-y-0 pb-14 md:pb-16">
            <?php foreach ($services as $index => $service): ?>
            <article class="svc-panel" data-ix="<?= $index + 1 ?>">
                <div class="svc-row grid gap-5 py-6 md:grid-cols-12 md:items-center md:gap-7 md:py-8">
                    <div class="svc-media md:col-span-6 <?= $index % 2 === 1 ? 'md:order-2' : '' ?>">
                        <img src="<?= base_url($service['image']) ?>" alt="<?= esc($service['alt']) ?>" width="1200" height="800"
                            class="svc-image w-full" loading="lazy" decoding="async" />
                    </div>

                    <div class="svc-copy md:col-span-6 <?= $index % 2 === 1 ? 'md:order-1' : '' ?>">
                        <p class="text-[.62rem] font-semibold tracking-[.12em] uppercase text-navy/80">
                            <?= esc($service['kicker']) ?></p>
                        <h2
                            class="svc-section-title mt-1 font-display text-[1.45rem] leading-[1.16] text-navy sm:text-[1.75rem] md:text-[2rem]">
                            <?= esc($service['title']) ?>
                        </h2>
                        <p class="mt-3 max-w-[58ch] text-[.95rem] leading-[1.78] text-black sm:text-[1rem]">
                            <?= esc($service['desc']) ?>
                        </p>

                        <ul class="mt-4 grid gap-x-6 gap-y-2 sm:grid-cols-2 sm:gap-x-8 sm:gap-y-1">
                            <?php foreach ($service['items'] as $item): ?>
                            <li class="svc-bullet text-[.84rem] leading-[1.6] text-black">
                                <span class="svc-bullet-dot" aria-hidden="true"></span>
                                <span><?= esc($item) ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
<script src="<?= base_url('assets/js/features/services/services.js') ?>" defer></script>