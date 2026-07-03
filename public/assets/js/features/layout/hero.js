/* ═══ HERO SLIDESHOW ═══ */
(function () {
    function toArray(list) {
        return Array.isArray(list) ? list : Array.from(list || []);
    }

    var slides = toArray(document.querySelectorAll('#hero .slide'));
    var hls    = toArray(document.querySelectorAll('#hero .hl'));
    var descs  = toArray(document.querySelectorAll('#hero .hl-desc'));
    var links  = toArray(document.querySelectorAll('#hero .hl-link'));
    var dots   = toArray(document.querySelectorAll('#hero .ind'));
    var titleWrap = document.getElementById('heroTitleWrap');
    var descWrap  = document.getElementById('heroDescWrap');
    var hero      = document.getElementById('hero');
    var heroHeading = document.getElementById('heroHeading');
    var heroPageIndicator = hero ? hero.querySelector('[data-hero-page-indicator]') : null;
    var prevBtn = hero ? hero.querySelector('[data-hero-prev]') : null;
    var nextBtn = hero ? hero.querySelector('[data-hero-next]') : null;
    var toggleBtn = hero ? hero.querySelector('[data-hero-toggle]') : null;
    if (slides.length < 2) return;

    var loaderRoot = document.querySelector('[data-asog-loader-root]');
    var waitForLoader = !!(loaderRoot && loaderRoot.dataset.state !== 'complete');
    var mobileRectQuery = window.matchMedia('(max-width: 767px)');
    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var cur   = 0;
    var DELAY = 5500;
    var timer;
    var autoplayPaused = false;

    function setActiveFor(list, idx) {
        list.forEach(function (el, i) {
            if (!el) return;
            var isActive = i === idx;
            el.classList.toggle('active', isActive);
            if (el.classList.contains('hl-link')) {
                el.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                el.tabIndex = isActive ? 0 : -1;
            }
        });
    }

    function ensureSlideBackground(idx) {
        var slide = slides[idx];
        if (!slide || slide.style.backgroundImage) return;

        var bg = slide.getAttribute('data-bg');
        if (bg) {
            slide.style.backgroundImage = 'url("' + bg.replace(/"/g, '\\"') + '")';
        }
    }

    function getSlideHeading(idx) {
        var slide = hls[idx] || null;
        if (!slide) return '';
        return (slide.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function syncHeroHeading() {
        if (!heroHeading) return;
        heroHeading.textContent = getSlideHeading(cur);
    }

    function syncHeroPageIndicator() {
        if (!heroPageIndicator) return;
        heroPageIndicator.textContent = (cur + 1) + '/' + slides.length;
    }

    function syncStackHeights() {
        if (titleWrap && hls.length) {
            var activeTitle = hls[cur] || hls.find(function (el) {
                return el && el.classList && el.classList.contains('active');
            }) || hls[0];

            var activeTitleHeight = activeTitle ? Math.ceil(activeTitle.scrollHeight || 0) : 0;
            if (activeTitleHeight > 0) {
                titleWrap.style.minHeight = activeTitleHeight + 'px';
            }

            if (hero) {
                hero.classList.remove('hero-title-short');
                if (activeTitle && !mobileRectQuery.matches) {
                    var style = window.getComputedStyle(activeTitle);
                    var lineHeight = parseFloat(style.lineHeight) || 0;
                    var visualLines = lineHeight > 0 ? (activeTitleHeight / lineHeight) : 2;
                    if (visualLines <= 1.45) {
                        hero.classList.add('hero-title-short');
                    }
                }
            }
        }

        if (descWrap && descs.length) {
            var hasDescText = descs.some(function (el) {
                return !!(el.textContent && el.textContent.trim());
            });

            if (!hasDescText) {
                descWrap.classList.add('is-empty');
                descWrap.style.minHeight = '0px';
                return;
            }

            descWrap.classList.remove('is-empty');
            var descMax = 0;
            descs.forEach(function (el) {
                descMax = Math.max(descMax, el.scrollHeight || 0);
            });
            if (descMax > 0) descWrap.style.minHeight = Math.ceil(descMax) + 'px';
            else descWrap.style.minHeight = '0px';
        } else if (descWrap) {
            descWrap.classList.add('is-empty');
            descWrap.style.minHeight = '0px';
        }
    }

    function syncHeroViewportHeight() {
        if (!hero) return;
        var isMobileRect = hero.classList.contains('hero-rect-mobile') && mobileRectQuery.matches;
        if (isMobileRect) {
            hero.style.removeProperty('--hero-vh');
            return;
        }
        var vv = window.visualViewport;
        var viewportHeight =
            (vv && vv.height) ||
            document.documentElement.clientHeight ||
            window.innerHeight;

        // Desktop Safari can briefly report a smaller visual viewport while UI animates.
        if (!mobileRectQuery.matches) {
            viewportHeight = Math.max(viewportHeight || 0, window.innerHeight || 0);
        }

        if (!viewportHeight) return;
        hero.style.setProperty('--hero-vh', Math.round(viewportHeight) + 'px');
    }

    function go(n) {
        var max = slides.length;
        if (!max) return;

        var target = Number(n);
        if (!Number.isFinite(target)) target = 0;
        target = ((target % max) + max) % max;
        cur = target;

        setActiveFor(slides, cur);
        setActiveFor(hls, cur);
        setActiveFor(descs, cur);
        setActiveFor(links, cur);
        setActiveFor(dots, cur);

        ensureSlideBackground(cur);

        syncHeroHeading();
        syncHeroPageIndicator();
        syncStackHeights();
    }

    function next() {
        go((cur + 1) % slides.length);
    }

    function stopTimer() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    function updateToggleButton() {
        if (!toggleBtn) return;
        var isPaused = !!autoplayPaused;
        toggleBtn.classList.toggle('is-paused', isPaused);
        toggleBtn.setAttribute('aria-pressed', isPaused ? 'true' : 'false');
        toggleBtn.setAttribute('aria-label', isPaused ? 'Resume autoplay' : 'Pause autoplay');
        toggleBtn.setAttribute('title', isPaused ? 'Resume autoplay' : 'Pause autoplay');
    }

    function startTimer() {
        stopTimer();
        if (waitForLoader) return;
        if (autoplayPaused) return;
        timer = setInterval(next, DELAY);
    }

    function pauseAutoplay() {
        autoplayPaused = true;
        stopTimer();
        updateToggleButton();
    }

    function resumeAutoplay() {
        autoplayPaused = false;
        updateToggleButton();
        startTimer();
    }

    function preloadNextSlide() {
        var nextIdx = (cur + 1) % slides.length;
        ensureSlideBackground(nextIdx);
    }

    function releaseFromLoader() {
        if (!waitForLoader) return;

        waitForLoader = false;
        go(0);
        preloadNextSlide();

        if (!prefersReducedMotion && !autoplayPaused) {
            startTimer();
        }
    }

    function handleManualMove(direction) {
        pauseAutoplay();
        go((cur + direction + slides.length) % slides.length);
        preloadNextSlide();
    }

    /* Expose goTo globally for inline onclick handlers in hero.php */
    window.goTo = function (n) {
        pauseAutoplay();
        go(n);
        preloadNextSlide();
    };

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            handleManualMove(-1);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            handleManualMove(1);
        });
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            if (autoplayPaused) {
                resumeAutoplay();
            } else {
                pauseAutoplay();
            }
        });
    }

    /* Boot */
    slides.forEach(function (slide, idx) {
        if (idx === 0) ensureSlideBackground(idx);
    });
    go(0);
    syncHeroHeading();
    syncHeroPageIndicator();
    syncHeroViewportHeight();
    syncStackHeights();
    
    if (prefersReducedMotion) {
        autoplayPaused = true;
        updateToggleButton();
    } else if (!waitForLoader) {
        startTimer();
    }
    
    preloadNextSlide();

    if (waitForLoader) {
        window.addEventListener('asog-loader:complete', releaseFromLoader, { once: true });
    }

    window.addEventListener('resize', syncHeroViewportHeight);
    window.addEventListener('resize', syncStackHeights);
    window.addEventListener('orientationchange', syncHeroViewportHeight);
    window.addEventListener('pageshow', syncHeroViewportHeight);

    if (mobileRectQuery.addEventListener) {
        mobileRectQuery.addEventListener('change', syncHeroViewportHeight);
    } else if (mobileRectQuery.addListener) {
        mobileRectQuery.addListener(syncHeroViewportHeight);
    }

    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', syncHeroViewportHeight);
    }

    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(syncStackHeights).catch(function () {});
    }
})();
