/**
 * Incubatees Showcase — Card Interactions
 * Requires: GSAP 3.12+, window.__ibData (injected by PHP view)
 */
(function () {
    'use strict';

    var data = window.__ibData;
    if (!data || !data.length) return;

    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ── DOM refs ── */
    var $    = function (id) { return document.getElementById(id); };
    var stack    = $('ibStack'),
        overlay  = $('ibOverlay'),
        backdrop = $('ibBackdrop'),
        bigCard  = $('ibBigCard'),
        bigInner = $('ibBigInner'),
        panel    = $('ibPanel'),
        closeBtn = $('ibClose');

    var bfNum     = $('bfNum'),
        bfLogo    = $('bfLogo'),
        bfName    = $('bfName'),
        bfCohort  = $('bfCohort');

    var bbName = $('bbName'),
        bbTeam = $('bbTeam');

    var pAboutTitle   = $('pAboutTitle'),
        pContent      = $('pContent'),
        pTeamSection  = $('pTeamSection'),
        pTeamList     = $('pTeamList'),
        pTeamLabel    = document.querySelector('#pTeamSection .ib-p-team-label'),
        pContactSection = $('pContactSection'),
        pContactList  = $('pContactList'),
        pSdgSection   = $('pSdgSection'),
        pSdgList      = $('pSdgList'),
        pWebsite      = $('pWebsite'),
        pFacebook     = $('pFacebook');

    var cards      = document.querySelectorAll('.ib-card');
    var seeMoreBtns = document.querySelectorAll('.ib-see-more');
    var isOpen     = false;
    var isFlipped  = false;
    var activeCard = null;
    var isMobileView = function () { return window.innerWidth <= 768; };
    var mobileFlippedCard = null;  // track which small card is flipped on mobile

    /* ── Mobile preview card refs ── */
    var mobPreview   = document.getElementById('ibMobilePreview');
    var mobBackdrop  = document.getElementById('ibMobPreviewBackdrop');
    var mobWrap      = document.getElementById('ibMobPreviewWrap');
    var mobClose     = document.getElementById('ibMobPreviewClose');
    var mpInner      = document.getElementById('mpInner');
    var mpLogo       = document.getElementById('mpLogo');
    var mpNum        = document.getElementById('mpNum');
    var mpName       = document.getElementById('mpName');
    var mpCohort     = document.getElementById('mpCohort');
    var mpBackName   = document.getElementById('mpBackName');
    var mpBackTeam   = document.getElementById('mpBackTeam');
    var mpHint       = document.getElementById('mpHint');
    var mpReadMore   = document.getElementById('mpReadMore');
    var previewIdx   = null;
    var mpFlipped    = false;
    var sdgCatalogById = null;

    function buildFallbackSdgCatalog() {
        var namesById = {
            1: 'No Poverty',
            2: 'Zero Hunger',
            3: 'Good Health and Well-Being',
            4: 'Quality Education',
            5: 'Gender Equality',
            6: 'Clean Water and Sanitation',
            7: 'Affordable and Clean Energy',
            8: 'Decent Work and Economic Growth',
            9: 'Industry, Innovation and Infrastructure',
            10: 'Reduced Inequalities',
            11: 'Sustainable Cities and Communities',
            12: 'Responsible Consumption and Production',
            13: 'Climate Action',
            14: 'Life Below Water',
            15: 'Life on Land',
            16: 'Peace, Justice and Strong Institutions',
            17: 'Partnerships for the Goals'
        };

        var catalog = {};
        Object.keys(namesById).forEach(function (idText) {
            var id = parseInt(idText, 10);
            var number = String(id).padStart(2, '0');
            catalog[id] = {
                id: id,
                name: namesById[id],
                iconWebp: '/assets/img/sdg/sdg-' + number + '.webp',
                iconPng: '/assets/img/sdg/sdg-' + number + '.png',
                goalUrl: 'https://sdgs.un.org/goals/goal' + id
            };
        });

        return catalog;
    }

    function parseSdgNumbers(raw) {
        if (!raw) return [];
        return String(raw)
            .split(',')
            .map(function (value) { return parseInt(value, 10); })
            .filter(function (id) { return Number.isInteger(id) && id >= 1 && id <= 17; })
            .filter(function (id, index, arr) { return arr.indexOf(id) === index; })
            .sort(function (a, b) { return a - b; });
    }

    function extractSdgNumbersFromText(text) {
        if (!text) return [];

        var ids = [];
        var regex = /\bSDG\s*([1-9]|1[0-7])\b/gi;
        var match;

        while ((match = regex.exec(text)) !== null) {
            var id = parseInt(match[1], 10);
            if (Number.isInteger(id) && id >= 1 && id <= 17 && ids.indexOf(id) === -1) {
                ids.push(id);
            }
        }

        return ids.sort(function (a, b) { return a - b; });
    }

    function getSdgCatalog() {
        if (sdgCatalogById) {
            return Promise.resolve(sdgCatalogById);
        }

        function fetchCatalog() {
            return fetch('/api/sdgs', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Failed to load SDGs');
                    }
                    return response.json();
                })
                .then(function (payload) {
                    var items = Array.isArray(payload.data) ? payload.data : [];
                    var catalog = {};
                    items.forEach(function (item) {
                        var id = Number(item.id);
                        if (Number.isInteger(id)) {
                            catalog[id] = item;
                        }
                    });
                    if (!Object.keys(catalog).length) {
                        throw new Error('Empty SDG catalog');
                    }
                    return catalog;
                });
        }

        return fetchCatalog()
            .catch(function () {
                // Retry once because some mobile browsers/extensions transiently fail first fetch.
                return fetchCatalog();
            })
            .catch(function () {
                sdgCatalogById = buildFallbackSdgCatalog();
                return sdgCatalogById;
            })
            .then(function (catalog) {
                sdgCatalogById = catalog;
                return sdgCatalogById;
            });
    }

    function renderSdgBadges(d) {
        if (!pSdgSection || !pSdgList) return;

        var ids = parseSdgNumbers(d.sdgNumbers);
        if (!ids.length) {
            var contentText = decodeRichText(d.content || '');
            ids = extractSdgNumbersFromText(contentText);
        }

        if (!ids.length) {
            pSdgList.innerHTML = '';
            pSdgSection.style.display = 'none';
            return;
        }

        getSdgCatalog().then(function (catalog) {
            var html = '';
            ids.forEach(function (id) {
                var info = catalog[id] || {};
                var name = info.name || ('Goal ' + id);
                var goalUrl = info.goalUrl || ('https://sdgs.un.org/goals/goal' + id);
                var iconWebp = info.iconWebp || '';
                var iconPng = info.iconPng || '';

                html += '<a class="ib-p-sdg-badge" href="' + goalUrl + '" target="_blank" rel="noopener noreferrer" title="Open UN SDG Goal ' + id + '">';
                html += '<span class="ib-p-sdg-icon">';
                html += '<picture>';
                if (iconWebp) {
                    html += '<source srcset="' + iconWebp + '" type="image/webp">';
                }
                html += '<img src="' + (iconPng || iconWebp) + '" alt="SDG ' + id + '" loading="lazy" onerror="this.style.display=\'none\';this.parentElement.parentElement.querySelector(\'.ib-p-sdg-icon-fallback\').style.display=\'flex\';">';
                html += '</picture>';
                html += '<span class="ib-p-sdg-icon-fallback">SDG ' + id + '</span>';
                html += '</span>';
                html += '<span class="ib-p-sdg-text">SDG ' + id + '</span>';
                html += '</a>';
            });

            pSdgList.innerHTML = html;
            pSdgSection.style.display = '';
        });
    }

    function buildDisplayTeam(d) {
        if (!Array.isArray(d.teamMembers)) return [];
        return d.teamMembers.filter(function (m) { return m && m.name; }).map(function (m) {
            return {
                name: m.name,
                role: m.role || '',
                photo: m.photo || ''
            };
        });
    }

    function decodeRichText(html) {
        if (!html) return '';
        if (!/&lt;\/?[a-z][\s\S]*?&gt;/i.test(html)) return html;
        var textarea = document.createElement('textarea');
        textarea.innerHTML = html;
        return textarea.value;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatPlainTextToHtml(text) {
        var lines = String(text || '').replace(/\r\n?/g, '\n').split('\n');
        var out = [];
        var bulletBuffer = [];

        function flushBullets() {
            if (!bulletBuffer.length) return;
            var items = bulletBuffer.map(function (item) {
                return '<li>' + escapeHtml(item) + '</li>';
            }).join('');
            out.push('<ul>' + items + '</ul>');
            bulletBuffer = [];
        }

        lines.forEach(function (line) {
            var trimmed = line.trim();
            var bulletMatch = trimmed.match(/^(?:[-*•])\s+(.+)$/);

            if (!trimmed) {
                flushBullets();
                out.push('');
                return;
            }

            if (bulletMatch) {
                bulletBuffer.push(bulletMatch[1]);
                return;
            }

            flushBullets();
            out.push('<p>' + escapeHtml(line).replace(/\s{2,}/g, ' ') + '</p>');
        });

        flushBullets();

        return out.filter(function (chunk) { return chunk !== ''; }).join('');
    }

    function formatIncubateeContent(d) {
        var rich = decodeRichText((d && d.content) || '').trim();
        var shortDescription = String((d && d.shortDescription) || '').trim();
        var hasHtmlTags = /<\/?[a-z][\s\S]*?>/i.test(rich);

        if (hasHtmlTags && rich) {
            return rich;
        }

        var plainText = rich || shortDescription;
        if (!plainText) {
            return '<p class="ib-p-empty">No details available yet.</p>';
        }

        return formatPlainTextToHtml(plainText);
    }

    function buildContacts(d) {
        var contacts = [];

        if (Array.isArray(d.contacts)) {
            d.contacts.forEach(function (contact) {
                if (!contact) return;

                var person = (contact.person || contact.name || '').trim();
                var number = (contact.number || contact.phone || '').trim();
                var email = (contact.email || '').trim();

                if (!person && !number && !email) return;

                contacts.push({
                    person: person,
                    number: number,
                    email: email
                });
            });
        }

        if (!contacts.length) {
            var legacyPerson = (d.contactName || '').trim();
            var legacyNumber = (d.contactNumber || '').trim();
            var legacyEmail = (d.contactEmail || '').trim();

            if (legacyPerson || legacyNumber || legacyEmail) {
                contacts.push({
                    person: legacyPerson,
                    number: legacyNumber,
                    email: legacyEmail
                });
            }
        }

        return contacts;
    }

    /* ── Entrance animation ── */
    var cards = document.querySelectorAll('.ib-card');

    if (cards.length) {
        if (prefersReducedMotion || typeof gsap === 'undefined') {
            cards.forEach(function (card) {
                card.classList.add('ib-card-visible');
            });
        } else {
            // Ensure they start hidden (CSS already does, but this is a safety net)
            gsap.set(cards, { opacity: 0, y: 35, scale: 0.92 });

            // Animate them in
            gsap.to(cards, {
                opacity: 1,
                y: 0,
                scale: 1,
                duration: 0.45,
                stagger: 0.07,
                ease: 'power2.out',
                delay: 0.15,
                onComplete: function() {
                    // Add a class so CSS takes over after the animation
                    cards.forEach(function(card) {
                        card.classList.add('ib-card-visible');
                        // Remove inline styles to let CSS transitions work
                        card.style.opacity = '';
                        card.style.transform = '';
                        card.style.scale = '';
                    });
                }
            });
        }
    }
    /* ── Hover tilt ── */
    cards.forEach(function (card) {
        var inner = card.querySelector('.ib-inner');

        card.addEventListener('mousemove', function (e) {
            if (prefersReducedMotion) return;
            if (isOpen) return;
            var r  = card.getBoundingClientRect();
            var cx = r.width / 2, cy = r.height / 2;
            var ry = ((e.clientX - r.left - cx) / cx) * 8;
            var rx = ((cy - (e.clientY - r.top)) / cy) * 6;
            gsap.to(inner, {
                rotateX: rx, rotateY: ry, y: -6,
                boxShadow: '0 16px 40px rgba(2,13,24,.1)',
                duration: .3, ease: 'power2.out'
            });
        });

        card.addEventListener('mouseleave', function () {
            if (prefersReducedMotion) return;
            gsap.to(inner, {
                rotateX: 0, rotateY: 0, y: 0,
                boxShadow: '0 2px 8px rgba(2,13,24,.03)',
                duration: .35, ease: 'power2.in'
            });
        });

        card.addEventListener('click', function () {
            if (isMobileView()) {
                showMobilePreview(card, parseInt(card.dataset.ix));
            } else {
                openCard(card, parseInt(card.dataset.ix));
            }
        });
    });

    window.__ibShowcaseReady = true;
    window.dispatchEvent(new CustomEvent('ib:ready'));

    /* ── Mobile: show preview popup (mini version of desktop big card) ── */
    function showMobilePreview(card, idx) {
        if (isOpen) return;
        if (!mobPreview) return;
        previewIdx = idx;
        activeCard = card;
        mpFlipped = false;
        /* Ensure the small card is NOT in a flipped state before opening */
        card.classList.remove('mob-flipped');
        var d   = data[idx];
        var num = String(idx + 1).padStart(2, '0');

        /* ── Populate FRONT (navy) — same as desktop bfXxx ── */
        mpNum.textContent    = num;
        mpName.textContent   = d.companyName;
        mpCohort.textContent = d.cohort;
        /* Prefer white-logo upload first, then fallback to regular logo with white filter */
        var logoSrc = d.logoWhitePath || d.logoPath;
        mpLogo.innerHTML = logoSrc
            ? '<img src="' + logoSrc + '" alt="' + d.companyName + '">'
            : '<span class="ib-init" style="font-size:1.8rem;color:rgba(255,255,255,.5)">' + d.companyName.charAt(0).toUpperCase() + '</span>';

        /* ── Populate BACK (team) — same as desktop bbXxx ── */
        mpBackName.textContent = d.companyName;
        var team = buildDisplayTeam(d);
        var html = '';
        if (team.length) {
                html += '<span class="ib-bb-team-label">' + (team.length === 1 ? 'Founder' : 'Founders') + '</span>';
            team.forEach(function (m) {
                html += '<div class="ib-bb-member flex flex-col items-center">';
                html += '<span class="ib-bb-member-name">' + m.name + '</span>';
                if (m.role) html += '<span class="ib-bb-member-role">' + m.role + '</span>';
                html += '</div>';
            });
        } else {
            html = '<p class="ib-bb-no-team">Founder info coming soon</p>';
        }
        mpBackTeam.innerHTML = html;

        /* Reset flip state */
        if (mpInner) mpInner.classList.remove('is-flipped');
        if (mpHint) { mpHint.textContent = 'Tap card to flip'; mpHint.classList.remove('hidden'); }

        /* Show */
        mobPreview.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeMobilePreview() {
        if (!mobPreview) return;
        mobPreview.classList.remove('is-open');
        document.body.style.overflow = '';
        /* Ensure the source card returns to its normal front-face state */
        if (activeCard) activeCard.classList.remove('mob-flipped');
        previewIdx = null;
        activeCard = null;
        mpFlipped = false;
        if (mpInner) mpInner.classList.remove('is-flipped');
    }

    /* ── Tap the mini card to flip it ── */
    if (mpInner) {
        mpInner.addEventListener('click', function (e) {
            e.stopPropagation();
            mpFlipped = !mpFlipped;
            mpInner.classList.toggle('is-flipped', mpFlipped);
            if (mpHint) {
                mpHint.textContent = mpFlipped ? 'Tap card to flip back' : 'Tap card to flip';
            }
        });
    }

    /* ── Mobile preview: Read More → full detail panel ── */
    if (mpReadMore) {
        mpReadMore.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var idx  = previewIdx;
            var card = activeCard;
            if (idx === null || !card) return;
            closeMobilePreview();
            /* Delay so the preview closes visually first */
            setTimeout(function () {
                openCard(card, idx);
            }, 200);
        });
    }

    /* ── Mobile preview: close triggers ── */
    if (mobClose) {
        mobClose.addEventListener('click', function (e) { e.stopPropagation(); closeMobilePreview(); });
    }
    if (mobBackdrop) {
        mobBackdrop.addEventListener('click', function (e) { e.stopPropagation(); closeMobilePreview(); });
    }
    if (mobWrap) {
        mobWrap.addEventListener('click', function (e) { e.stopPropagation(); });
    }

    /* ── Mobile "See More" buttons on card back ── */
    seeMoreBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var ix = parseInt(btn.dataset.ix);
            var card = document.querySelector('.ib-card[data-ix="' + ix + '"]');
            // Unflip the card first, then open modal
            if (card) card.classList.remove('mob-flipped');
            mobileFlippedCard = null;
            openCard(card, ix);
        });
    });

    /* ── Open card ── */
    function openCard(card, idx) {
        if (isOpen) return;
        isOpen = true;
        isFlipped = false;
        activeCard = card;

        var d   = data[idx];
        var num = String(idx + 1).padStart(2, '0');

        /* Big front */
        bfNum.textContent    = num;
        bfName.textContent   = d.companyName;
        bfCohort.textContent = d.cohort;
        /* Prefer white-logo upload first, then fallback to regular logo with white filter */
        var logoSrc = d.logoWhitePath || d.logoPath;
        bfLogo.innerHTML = logoSrc
            ? '<img src="' + logoSrc + '" alt="' + d.companyName + '">'
            : '<span class="ib-init" style="font-size:2.4rem;color:rgba(255,255,255,.5)">'
              + d.companyName.charAt(0).toUpperCase() + '</span>';

        /* Big back */
        bbName.textContent = d.companyName;
        var team = buildDisplayTeam(d);
        var html = '';
        if (team.length) {
            html += '<span class="ib-bb-team-label">' + (team.length === 1 ? 'Founder' : 'Founders') + '</span>';
            team.forEach(function (m) {
                html += '<div class="ib-bb-member flex flex-col items-center">';
                html += '<span class="ib-bb-member-name">' + m.name + '</span>';
                if (m.role) html += '<span class="ib-bb-member-role">' + m.role + '</span>';
                html += '</div>';
            });
        } else {
            html = '<p class="ib-bb-no-team">Founder info coming soon</p>';
        }
        bbTeam.innerHTML = html;

        /* Panel */
        if (pAboutTitle) {
            pAboutTitle.textContent = 'About ' + d.companyName;
        }
        pContent.innerHTML = formatIncubateeContent(d);
        renderSdgBadges(d);

        var team = buildDisplayTeam(d);

        if (pTeamList && pTeamSection) {
            if (team.length) {
                if (pTeamLabel) {
                    pTeamLabel.textContent = team.length === 1 ? 'Founder' : 'Founders';
                }

                var teamHtml = '';
                team.forEach(function (m) {
                    var initial = (m.name || '?').trim().charAt(0).toUpperCase();
                    var photoMarkup = m.photo
                        ? '<img class="ib-p-member-photo" src="' + m.photo + '" alt="' + m.name + '">'
                        : '<div class="ib-p-member-photo-default"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg></div>';

                    teamHtml += '<div class="ib-p-member-card">';
                    teamHtml += photoMarkup;
                    teamHtml += '<div class="ib-p-member-info">';
                    teamHtml += '<span class="ib-p-member-name">' + m.name + '</span>';
                    if (m.role) {
                        teamHtml += '<span class="ib-p-member-role">' + m.role + '</span>';
                    }
                    teamHtml += '</div>';
                    teamHtml += '</div>';
                });
                pTeamList.innerHTML = teamHtml;
                pTeamSection.style.display = '';
            } else {
                pTeamList.innerHTML = '';
                pTeamSection.style.display = 'none';
            }
        }

        if (pContactSection && pContactList) {
            var contacts = buildContacts(d);

            if (contacts.length) {
                var contactHtml = '';
                contacts.forEach(function (contact) {
                    contactHtml += '<div class="ib-p-contact-item">';
                    contactHtml += '<span class="ib-p-contact-piece">';
                    contactHtml += '<i class="fa-solid fa-user ib-p-contact-icon" aria-hidden="true"></i>';
                    contactHtml += '<span>' + escapeHtml(contact.person || '-') + '</span>';
                    contactHtml += '</span>';
                    contactHtml += '<span class="ib-p-contact-sep">|</span>';
                    contactHtml += '<span class="ib-p-contact-piece">';
                    contactHtml += '<i class="fa-solid fa-phone ib-p-contact-icon" aria-hidden="true"></i>';
                    contactHtml += '<span>' + escapeHtml(contact.number || '-') + '</span>';
                    contactHtml += '</span>';
                    contactHtml += '<span class="ib-p-contact-sep">|</span>';
                    contactHtml += '<span class="ib-p-contact-piece">';
                    contactHtml += '<i class="fa-solid fa-envelope ib-p-contact-icon" aria-hidden="true"></i>';
                    contactHtml += '<span>' + escapeHtml(contact.email || '-') + '</span>';
                    contactHtml += '</span>';
                    contactHtml += '</div>';
                });
                pContactList.innerHTML = contactHtml;
                pContactSection.style.display = '';
            } else {
                pContactList.innerHTML = '';
                pContactSection.style.display = 'none';
            }
        }

        if (d.websiteUrl)  { pWebsite.href = d.websiteUrl;   pWebsite.style.display = 'inline-flex'; }
        else               { pWebsite.style.display = 'none'; }
        if (d.facebookUrl) { pFacebook.href = d.facebookUrl; pFacebook.style.display = 'inline-flex'; }
        else               { pFacebook.style.display = 'none'; }

        /* Animate in */
        stack.classList.add('has-active');
        card.classList.add('is-picked');
        if (!prefersReducedMotion && typeof gsap !== 'undefined') {
            gsap.set(card.querySelector('.ib-inner'), { rotateX: 0, rotateY: 0 });
        }

        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';

        if (prefersReducedMotion || typeof gsap === 'undefined') {
            if (isMobileView()) {
                panel.style.transform = 'translateY(0)';
            } else {
                bigCard.style.opacity = '1';
                bigCard.style.transform = 'none';
                panel.style.transform = 'none';
            }
        } else if (isMobileView()) {
            /* Mobile: slide panel up from bottom, skip big card */
            gsap.set(panel, { x: 0, y: '100%' });
            gsap.to(panel, { y: 0, duration: .4, ease: 'power3.out' });
        } else {
            /* Desktop: big card + side panel */
            gsap.set(bigInner, { rotateY: 0 });
            gsap.set(bigCard,  { opacity: 0, scale: .7, y: 30 });
            gsap.to(bigCard,   { opacity: 1, scale: 1, y: 0, duration: .5, delay: .1, ease: 'back.out(1.4)' });
            gsap.to(panel,     { x: 0, duration: .45, delay: .2, ease: 'power3.out' });
        }
    }

    /* ── Close overlay ── */
    function closeOverlay() {
        if (!isOpen) return;
        if (isFlipped) {
            if (prefersReducedMotion || typeof gsap === 'undefined') {
                isFlipped = false;
                doClose();
                return;
            }
            gsap.to(bigInner, { rotateY: 0, duration: .35, ease: 'power2.inOut', onComplete: doClose });
            isFlipped = false;
        } else {
            doClose();
        }
    }

    function doClose() {
        if (prefersReducedMotion || typeof gsap === 'undefined') {
            overlay.classList.remove('is-open');
            stack.classList.remove('has-active');
            if (activeCard) activeCard.classList.remove('is-picked');
            if (mobileFlippedCard) {
                mobileFlippedCard.classList.remove('mob-flipped');
                mobileFlippedCard = null;
            }
            activeCard = null;
            isOpen = false;
            document.body.style.overflow = '';
            panel.style.transform = '';
            bigCard.style.opacity = '';
            bigCard.style.transform = '';
            return;
        }

        if (isMobileView()) {
            /* Mobile: slide panel down to bottom */
            gsap.to(panel, {
                x: 0, y: '100%', duration: .3, ease: 'power2.in',
                onComplete: function () {
                    overlay.classList.remove('is-open');
                    stack.classList.remove('has-active');
                    if (activeCard) activeCard.classList.remove('is-picked');
                    if (mobileFlippedCard) {
                        mobileFlippedCard.classList.remove('mob-flipped');
                        mobileFlippedCard = null;
                    }
                    activeCard = null;
                    isOpen = false;
                    document.body.style.overflow = '';
                    /* Reset to CSS default (offscreen right) */
                    panel.style.transform = '';
                }
            });
        } else {
            gsap.to(panel,   { x: '100%', duration: .3, ease: 'power2.in' });
            gsap.to(bigCard, {
                opacity: 0, scale: .8, y: 20, duration: .3, ease: 'power2.in',
                onComplete: function () {
                    overlay.classList.remove('is-open');
                    stack.classList.remove('has-active');
                    if (activeCard) activeCard.classList.remove('is-picked');
                    activeCard = null;
                    isOpen = false;
                    document.body.style.overflow = '';
                    gsap.set(panel, { x: '100%' });
                }
            });
        }
    }

    /* ── Flip (counter-clockwise) ── */
    bigInner.addEventListener('click', function (e) {
        e.stopPropagation();
        isFlipped = !isFlipped;
        if (prefersReducedMotion || typeof gsap === 'undefined') {
            bigInner.style.transform = isFlipped ? 'rotateY(-180deg)' : 'rotateY(0deg)';
            return;
        }
        gsap.to(bigInner, { rotateY: isFlipped ? -180 : 0, duration: .55, ease: 'power2.inOut' });
    });

    /* ── Close triggers ── */
    closeBtn.addEventListener('click', function (e) { e.stopPropagation(); closeOverlay(); });
    panel.addEventListener('click', function (e) { e.stopPropagation(); });
    backdrop.addEventListener('click', closeOverlay);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (mobPreview && mobPreview.classList.contains('is-open')) {
                closeMobilePreview();
            } else if (isOpen) {
                closeOverlay();
            }
        }
    });
})();
