<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <?php
    $defaultTitle = 'ASOG Technology Business Incubator';
    $defaultDescription = 'ASOG Technology Business Incubator (ASOG TBI) - Programs, Mentorship, Facilities, News, and Support for Startups in Camarines Sur.';
    $defaultSocialImage = base_url('assets/img/incubatees.webp');

    $pageTitle = isset($title) && $title !== '' ? $title : $defaultTitle;
    $pageDescription = isset($metaDescription) && $metaDescription !== '' ? $metaDescription : $defaultDescription;
    $pageImage = isset($metaImage) && (string) $metaImage !== '' ? (string) $metaImage : $defaultSocialImage;
    $pageImageAlt = isset($metaImageAlt) && $metaImageAlt !== '' ? (string) $metaImageAlt : $pageTitle;
    $pageType = isset($metaType) && $metaType !== '' ? (string) $metaType : 'website';
    $canonicalUrl = isset($canonical) && $canonical !== '' ? $canonical : current_url();
    $canonicalUrl = preg_replace('#^https://www\\.#i', 'https://', $canonicalUrl ?? '');
    $pageImage = preg_replace('#^https://www\\.#i', 'https://', $pageImage ?? '');
    ?>
    <meta name="description" content="<?= esc($pageDescription) ?>">
    <link rel="canonical" href="<?= esc($canonicalUrl) ?>">
    <meta property="og:title" content="<?= esc($pageTitle) ?>">
    <meta property="og:description" content="<?= esc($pageDescription) ?>">
    <meta property="og:type" content="<?= esc($pageType) ?>">
    <meta property="og:site_name" content="ASOG Technology Business Incubator">
    <meta property="og:url" content="<?= esc($canonicalUrl) ?>">
    <meta property="og:image" content="<?= esc($pageImage) ?>">
    <meta property="og:image:secure_url" content="<?= esc($pageImage) ?>">
    <meta property="og:image:alt" content="<?= esc($pageImageAlt) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?= esc($pageImage) ?>">
    <meta name="twitter:image:alt" content="<?= esc($pageImageAlt) ?>">
    <meta name="twitter:title" content="<?= esc($pageTitle) ?>">
    <meta name="twitter:description" content="<?= esc($pageDescription) ?>">
    <title><?= esc($pageTitle) ?></title>
    <!-- ================== STRUCTURED DATA  ===================== -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "ASOG Technology Business Incubator",
        "alternateName": "ASOG-TBI",
        "url": "<?= base_url() ?>",
        "logo": "<?= base_url('assets/img/ASOG TBI/WebP/ASOG-TBI-stacked-v2.webp') ?>",
        "description": "Supports startup incubation, mentorship, programs, and innovation development in Camarines Sur.",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "Camarines Sur",
            "addressCountry": "PH"
        },
        "sameAs": [
            "https://www.facebook.com/CSPCASOGTBI"
        ]
    }
    </script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "url": "<?= base_url() ?>",
        "name": "ASOG Technology Business Incubator",
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "<?= base_url('search?q={search_term_string}') ?>"
            },
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    <!-- ================== CSS/JS  ===================== -->
    <link href="<?= base_url('style.css') ?>" rel="stylesheet">
    <?php if (!empty($extraCss) && is_array($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?= esc($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <?php if (empty($hideSiteHeader)): ?>
    <script src="<?= base_url('assets/js/features/layout/header.js') ?>" defer></script>
    <?php endif; ?>

    <!-- ================== GOOGLE FONTS  ===================== -->
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,200;0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- ================== FAVICON  ========================== -->
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('icon.png') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('icon.png') ?>">
    <!-- Preload critical WebP logo -->
    <link rel="preload" as="image" href="<?= base_url('assets/img/ASOG TBI/WebP/ASOG-TBI-stacked-v2.webp') ?>"
        type="image/webp">
    <?php if (! empty($heroPreloadImage)): ?>
    <link rel="preload" as="image" href="<?= esc($heroPreloadImage) ?>" fetchpriority="high">
    <?php endif; ?>
</head>

<?php $bodyClass = trim('font-body bg-dark text-off overflow-x-hidden ' . (string) ($bodyClass ?? '')); ?>

<body class="<?= esc($bodyClass) ?>">
    <?php
    /* ── Nav URLs: always link to dedicated pages ── */
    $navAbout      = site_url('about');
    $navOurStory   = $navAbout . ''; # removed about-panel-1 to avoid jitter / page jumping
    $navAboutLogo  = site_url('about/logo');
    $navPrograms   = site_url('programs');
    $navAltitude   = $navPrograms . ' ';
    $navServices   = site_url('services');
    $navFacilities = site_url('facilities');
    $navIncubatees = site_url('incubatees');
    $navNews       = site_url('news');
    $navOrg        = site_url('organization');
    $navContact    = site_url('contact');
    $navCta        = site_url('apply');
    $navCohorts    = $navCohorts ?? [];

    $uri = service('uri');
    $uriPath = trim($uri->getPath(), '/');
    $seg1 = $uri->getSegment(1, '');
    if ($seg1 === 'index.php') {
        $seg1 = $uri->getSegment(2, '');
    }

    $isAbout         = $seg1 === 'about';
    $isProgramsGroup = in_array($seg1, ['programs', 'services', 'facilities'], true);
    $isIncubatees    = in_array($seg1, ['incubatees', 'apply'], true);
    $isNews          = $seg1 === 'news';
    $isNewsDetail    = preg_match('#(?:^|/)news/[^/]+$#', $uriPath) === 1;
    $isContact       = $seg1 === 'contact';
    $isOrg           = $seg1 === 'organization';
    $isAboutGroup    = $isAbout || $isOrg;
    $isProgramsPage  = $seg1 === 'programs';
    $isLandingPage   = ! empty($isLanding);
    $hideSiteHeader  = ! empty($hideSiteHeader);

    $forceWhiteLogoPages = in_array($seg1, ['about', 'programs', 'services', 'facilities', 'news', 'organization', 'contact', 'incubatees'], true)
        || str_starts_with($uriPath, 'apply');

    $initialNavTheme = $forceWhiteLogoPages ? ' on-blue' : '';

    $activeClass = static fn(bool $isActive): string => $isActive ? ' is-active' : '';
    ?>
    <a class="sr-only focus:not-sr-only" href="#main">Skip to content</a>

    <?php if (! $hideSiteHeader): ?>

    <!--
         ╔══════════════════════════════════════════════════════════════╗
         ║  NAVBAR                                                      ║
         ║  Desktop: left / center-logo / right with dropdowns          ║
         ║  Mobile (<lg): logo-left + hamburger menu,                   ║
         ║  slide-out overlay                                           ║
         ╚══════════════════════════════════════════════════════════════╝ 
    -->


    <nav id="navbar"
        class="fixed top-0 left-0 right-0 z-[500]<?= $initialNavTheme ?><?= $isNewsDetail ? ' logo-color-exception' : '' ?><?= $isProgramsPage ? ' nav-programs-desktop' : '' ?><?= $isLandingPage ? ' landing-nav' : '' ?>">
        <div id="navIn" class="flex items-center px-4 lg:px-10 min-h-20 py-2 lg:py-3">

            <!-- desktop left links -->
            <div class="nav-left absolute left-10 flex items-center gap-1 lg:flex hidden">
                <!-- About -->
                <div class="nav-dd group<?= $activeClass($isAboutGroup) ?>">
                    <span role="button" tabindex="0"
                        class="nav-link text-[.68rem] font-medium tracking-[.09em] uppercase text-white/60 px-4 border-b-2 border-transparent -mb-0.5 whitespace-nowrap transition-all duration-200 hover:text-off hover:border-gold cursor-pointer select-none<?= $activeClass($isAboutGroup) ?>">About</span>
                    <div class="dd-menu">
                        <a href="<?= $navOurStory ?>" class="dd-item<?= $activeClass($isAbout) ?>">Our Story</a>
                        <a href="<?= $navOrg ?>" class="dd-item<?= $activeClass($isOrg) ?>">Organization</a>
                    </div>
                </div>
                <!-- Programs & Services -->
                <div class="nav-dd group<?= $activeClass($isProgramsGroup) ?>">
                    <span role="button" tabindex="0"
                        class="nav-link text-[.68rem] font-medium tracking-[.09em] uppercase text-white/60 px-4 border-b-2 border-transparent -mb-0.5 whitespace-nowrap transition-all duration-200 hover:text-off hover:border-gold cursor-pointer select-none<?= $activeClass($isProgramsGroup) ?>">Programs
                        &amp; Services</span>
                    <div class="dd-menu">
                        <a href="<?= $navAltitude ?>" class="dd-item<?= $activeClass($seg1 === 'programs') ?>">The
                            ALTITUDE Program</a>
                        <a href="<?= $navServices ?>" class="dd-item<?= $activeClass($seg1 === 'services') ?>">Services
                            Offered</a>
                        <a href="<?= $navFacilities ?>"
                            class="dd-item<?= $activeClass($seg1 === 'facilities') ?>">Facilities</a>
                    </div>
                </div>
                <!-- Incubatees -->
                <a href="<?= $navIncubatees ?>"
                    class="nl nav-link text-[.68rem] font-medium tracking-[.09em] uppercase text-white/60 no-underline px-4 border-b-2 border-transparent -mb-0.5 whitespace-nowrap transition-all duration-200 hover:text-off hover:border-gold<?= $activeClass($isIncubatees) ?>">Incubatees</a>
            </div>

            <!-- CENTER LOGO -->
            <a href="<?= base_url() ?>" id="navLogo" class="flex no-underline">
                <?= responsiveNavLogo() ?>
                <?= responsiveNavLogoLandscape() ?>
            </a>

            <!-- desktop right links -->
            <div id="navR" class="absolute right-10 lg:flex hidden items-center">
                <!-- News & Insights -->
                <a href="<?= $navNews ?>" data-order="4"
                    class="nl nav-link text-[.68rem] font-medium tracking-[.09em] uppercase text-white/60 no-underline px-4 border-b-2 border-transparent -mb-0.5 whitespace-nowrap transition-all duration-200 hover:text-off hover:border-gold<?= $activeClass($isNews) ?>">News
                    &amp; Insights</a>
                <!-- Contact Us -->
                <div class="nav-dd group" data-order="5">
                    <a href="<?= $navContact ?>"
                        class="nl nav-link text-[.68rem] font-medium tracking-[.09em] uppercase text-white/60 no-underline px-4 border-b-2 border-transparent -mb-0.5 whitespace-nowrap transition-all duration-200 hover:text-off hover:border-gold<?= $activeClass($isContact) ?>">Contact
                        Us</a>
                </div>
                <!-- CTA Button -->
                <a href="<?= $navCta ?>" data-order="6"
                    class="nav-btn ml-4 font-body text-[.63rem] font-semibold tracking-[.13em] uppercase text-navy bg-gold border border-gold px-5 py-2 rounded-sm no-underline whitespace-nowrap shrink-0 transition-colors duration-200 hover:bg-gold-dk">Be
                    an Incubatee</a>

                <!-- COLLAPSED DUPLICATES (appear on scroll via .lo) -->
                <div class="nav-dd group lo<?= $activeClass($isAboutGroup) ?>" data-order="1">
                    <span role="button" tabindex="0"
                        class="nl nav-link text-[.68rem] font-medium tracking-[.09em] uppercase text-white/60 items-center border-b-2 border-transparent -mb-0.5 whitespace-nowrap hover:text-off hover:border-gold cursor-pointer select-none<?= $activeClass($isAboutGroup) ?>">About</span>
                    <div class="dd-menu dd-right">
                        <a href="<?= $navOurStory ?>" class="dd-item<?= $activeClass($isAbout) ?>">Our Story</a>
                        <a href="<?= $navOrg ?>" class="dd-item<?= $activeClass($isOrg) ?>">Organization</a>
                    </div>
                </div>
                <div class="nav-dd group lo<?= $activeClass($isProgramsGroup) ?>" data-order="2">
                    <span role="button" tabindex="0"
                        class="nl nav-link text-[.68rem] font-medium tracking-[.09em] uppercase text-white/60 items-center border-b-2 border-transparent -mb-0.5 whitespace-nowrap hover:text-off hover:border-gold cursor-pointer select-none<?= $activeClass($isProgramsGroup) ?>">Programs
                        &amp; Services</span>
                    <div class="dd-menu dd-right">
                        <a href="<?= $navAltitude ?>" class="dd-item<?= $activeClass($seg1 === 'programs') ?>">The
                            ALTITUDE Program</a>
                        <a href="<?= $navServices ?>" class="dd-item<?= $activeClass($seg1 === 'services') ?>">Services
                            Offered</a>
                        <a href="<?= $navFacilities ?>"
                            class="dd-item<?= $activeClass($seg1 === 'facilities') ?>">Facilities</a>
                    </div>
                </div>
                <a href="<?= $navIncubatees ?>"
                   class="nl nav-link lo text-[.68rem] font-medium tracking-[.09em] uppercase text-white/60 no-underline px-4 border-b-2 border-transparent -mb-0.5 whitespace-nowrap transition-all duration-200 hover:text-off hover:border-gold<?= $activeClass($isIncubatees) ?>"
                    data-order="3">Incubatees</a>
            </div>

            <!-- MOBILE HAMBURGER (visible only on <lg) -->
            <button id="menuBtn"
                class="lg:hidden ml-auto relative z-[600] flex flex-col justify-center items-center w-10 h-10 gap-1.5"
                aria-label="Menu">
                <span id="bar1"
                    class="block w-6 h-[2px] bg-white rounded transition-all duration-300 origin-center"></span>
                <span id="bar2" class="block w-6 h-[2px] bg-white rounded transition-all duration-300"></span>
                <span id="bar3"
                    class="block w-6 h-[2px] bg-white rounded transition-all duration-300 origin-center"></span>
            </button>
        </div>
    </nav>

    <!-- MOBILE SLIDE-OUT MENU (visible only on <lg) -->
    <div id="mobileMenu"
        class="fixed inset-0 z-[550] bg-dark/[.97] backdrop-blur-xl flex flex-col pt-24 px-8 pb-10 overflow-y-auto lg:hidden"
        style="transform:translateX(100%);opacity:0;">
        <!-- Close X button -->
        <button id="closeMenuBtn" class="absolute top-6 right-5 z-[600] w-10 h-10 flex items-center justify-center"
            aria-label="Close menu">
            <svg class="w-6 h-6 text-white/60 transition-colors hover:text-gold" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <nav class="flex flex-col gap-1">
            <a href="<?= base_url() ?>"
                class="text-[.8rem] font-medium tracking-[.1em] uppercase text-white/60 no-underline py-3 border-b border-white/[.06] transition-colors hover:text-gold<?= $activeClass($uriPath === '') ?>">Home</a>
            <button type="button" id="mobAboutToggle"
                class="w-full flex items-center justify-between text-[.8rem] font-medium tracking-[.1em] uppercase text-white/60 py-3 border-b border-white/[.06] bg-transparent cursor-pointer select-none transition-colors hover:text-gold<?= $activeClass($isAboutGroup) ?>">
                About
                <svg id="mobAboutChevron" class="w-4 h-4 transition-transform duration-200" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div id="mobAboutSub" class="overflow-hidden" style="max-height:0;transition:max-height .3s ease">
                <a href="<?= $navOurStory ?>"
                    class="text-[.72rem] font-normal tracking-[.08em] uppercase text-white/40 no-underline py-2 pl-4 border-b border-white/[.04] transition-colors hover:text-gold block<?= $activeClass($isAbout) ?>">Our
                    Story</a>
                <a href="<?= $navOrg ?>"
                    class="text-[.72rem] font-normal tracking-[.08em] uppercase text-white/40 no-underline py-2 pl-4 border-b border-white/[.04] transition-colors hover:text-gold block<?= $activeClass($isOrg) ?>">Organization</a>
            </div>
            <button type="button" id="mobPsToggle"
                class="w-full flex items-center justify-between text-[.8rem] font-medium tracking-[.1em] uppercase text-white/60 py-3 border-b border-white/[.06] bg-transparent cursor-pointer select-none transition-colors hover:text-gold">
                Programs &amp; Services
                <svg id="mobPsChevron" class="w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div id="mobPsSub" class="overflow-hidden" style="max-height:0;transition:max-height .3s ease">
                <a href="<?= $navAltitude ?>"
                    class="text-[.72rem] font-normal tracking-[.08em] uppercase text-white/40 no-underline py-2 pl-4 border-b border-white/[.04] transition-colors hover:text-gold block<?= $activeClass($seg1 === 'programs') ?>">The
                    ALTITUDE Program</a>
                <a href="<?= $navServices ?>"
                    class="text-[.72rem] font-normal tracking-[.08em] uppercase text-white/40 no-underline py-2 pl-4 border-b border-white/[.04] transition-colors hover:text-gold block<?= $activeClass($seg1 === 'services') ?>">Services
                    Offered</a>
                <a href="<?= $navFacilities ?>"
                    class="text-[.72rem] font-normal tracking-[.08em] uppercase text-white/40 no-underline py-2 pl-4 border-b border-white/[.04] transition-colors hover:text-gold block<?= $activeClass($seg1 === 'facilities') ?>">Facilities</a>
            </div>
            <a href="<?= $navIncubatees ?>"
                class="text-[.8rem] font-medium tracking-[.1em] uppercase text-white/60 no-underline py-3 border-b border-white/[.06] transition-colors hover:text-gold<?= $activeClass($isIncubatees) ?>">Incubatees</a>
            <a href="<?= $navNews ?>"
                class="text-[.8rem] font-medium tracking-[.1em] uppercase text-white/60 no-underline py-3 border-b border-white/[.06] transition-colors hover:text-gold<?= $activeClass($isNews) ?>">News
                &amp; Insights</a>
            <a href="<?= $navContact ?>"
                class="text-[.8rem] font-medium tracking-[.1em] uppercase text-white/60 no-underline py-3 border-b border-white/[.06] transition-colors hover:text-gold<?= $activeClass($isContact) ?>">Contact
                Us</a>
        </nav>
        <a href="<?= $navCta ?>"
            class="mt-8 text-center font-body text-[.72rem] font-bold tracking-[.14em] uppercase text-navy bg-gold px-8 py-4 rounded-sm no-underline transition-colors hover:bg-gold-dk">Be
            an Incubatee</a>
    </div>
    <?php endif; ?>
