function renderMorphTbi() {
    return [
        '<span class="asog-loader-final-lockup">',
        '<span class="asog-loader-final-asog" data-asog-loader-final-asog>',
        ['A', 'S', 'O', 'G'].map((letter) => [
            '<span class="asog-loader-final-letter" data-asog-loader-final-letter>',
            letter,
            '</span>',
        ].join('')).join(''),
        '</span>',
        '<span class="asog-loader-word-tbi" data-asog-loader-final-tbi>TBI</span>',
        '</span>',
    ].join('');
}

function createFlyingInitials({ morphRoot, initials, finalLetters }) {
    morphRoot.querySelector('[data-asog-loader-flying-layer]')?.remove();

    const rootBounds = morphRoot.getBoundingClientRect();
    const layer = document.createElement('span');
    layer.className = 'asog-loader-flying-layer';
    layer.setAttribute('data-asog-loader-flying-layer', '');
    morphRoot.appendChild(layer);

    const clones = Array.from(initials).map((initial, index) => {
        const target = finalLetters[index];
        if (!target) {
            return null;
        }

        const sourceBounds = initial.getBoundingClientRect();
        const targetBounds = target.getBoundingClientRect();
        const computed = window.getComputedStyle(initial);
        const clone = document.createElement('span');
        clone.className = 'asog-loader-flying-letter';
        clone.textContent = initial.textContent || '';
        clone.style.left = `${sourceBounds.left - rootBounds.left}px`;
        clone.style.top = `${sourceBounds.top - rootBounds.top}px`;
        clone.style.width = `${sourceBounds.width}px`;
        clone.style.height = `${sourceBounds.height}px`;
        clone.style.fontSize = computed.fontSize;
        clone.style.lineHeight = computed.lineHeight;
        clone.dataset.targetX = String((targetBounds.left + (targetBounds.width / 2)) - (sourceBounds.left + (sourceBounds.width / 2)));
        clone.dataset.targetY = String((targetBounds.top + (targetBounds.height / 2)) - (sourceBounds.top + (sourceBounds.height / 2)));
        layer.appendChild(clone);
        return clone;
    }).filter(Boolean);

    return { layer, clones };
}

function renderAcronymCluster() {
    return [
        ['A', 'CADEME'],
        ['S', 'OCIETY'],
        ['O', 'RGANIZATION'],
        ['G', 'OVERNMENT'],
    ].map(([initial, rest]) => [
        '<span class="asog-loader-word-row">',
        '<span class="asog-loader-word-initial">',
        initial,
        '</span><span class="asog-loader-word-rest">',
        rest,
        '</span></span>',
    ].join('')).join('');
}

function createWordMorphTimeline({ gsap, root, scale }) {
    const morphRoot = root.querySelector('[data-asog-loader-word-morph]');
    const wordA = root.querySelector('[data-asog-loader-word-a]');
    const wordB = root.querySelector('[data-asog-loader-word-b]');

    if (!morphRoot || !wordA || !wordB) {
        return gsap.timeline();
    }

    const dur = (seconds) => seconds * scale;
    const tl = gsap.timeline();
    wordA.innerHTML = renderAcronymCluster();
    wordB.innerHTML = renderMorphTbi();
    const rows = wordA.querySelectorAll('.asog-loader-word-row');
    const initials = wordA.querySelectorAll('.asog-loader-word-initial');
    const suffixes = wordA.querySelectorAll('.asog-loader-word-rest');
    const finalAsog = wordB.querySelector('[data-asog-loader-final-asog]');
    const finalTbi = wordB.querySelector('[data-asog-loader-final-tbi]');
    const finalLetters = wordB.querySelectorAll('[data-asog-loader-final-letter]');
    let flying = { layer: null, clones: [] };

    tl.call(() => {
        root.dataset.loaderPhase = 'words';
        morphRoot.querySelector('[data-asog-loader-flying-layer]')?.remove();
        gsap.set(morphRoot, { autoAlpha: 1, xPercent: -50, yPercent: -50, x: 0, y: 0, scale: 1 });
        gsap.set(wordA, { opacity: 1, filter: 'blur(0px)' });
        gsap.set(wordB, { opacity: 1, x: 0, y: 0, scale: 1, filter: 'blur(0px)' });
        gsap.set(finalAsog, { opacity: 0, scale: 1, filter: 'blur(0px)' });
        gsap.set(finalTbi, { opacity: 0, x: -10, filter: 'blur(12px)' });
        gsap.set(rows, {
            opacity: 0,
            x: -18,
            y: 12,
            filter: 'blur(10px)',
        });
        gsap.set(initials, { x: 0, y: 0, scale: 1 });
        gsap.set(suffixes, { display: 'inline-block', opacity: 1, x: 0, filter: 'blur(0px)' });
    }, null, 0);

    tl.to(rows, {
        opacity: 1,
        x: 0,
        y: 0,
        filter: 'blur(0px)',
        duration: dur(0.68),
        stagger: dur(0.105),
        ease: 'power3.out',
    }, dur(0.12));
    tl.to(suffixes, {
        opacity: 0,
        x: 8,
        filter: 'blur(10px)',
        duration: dur(0.74),
        stagger: dur(0.045),
        ease: 'power2.inOut',
    }, dur(1.48));
    tl.call(() => {
        flying = createFlyingInitials({ morphRoot, initials, finalLetters });
        gsap.set(initials, { opacity: 0 });
        gsap.set(flying.clones, { opacity: 1, scale: 1.02, filter: 'blur(0px)' });
        gsap.to(flying.clones, {
            x: (index, target) => Number(target.dataset.targetX || 0),
            y: (index, target) => Number(target.dataset.targetY || 0),
            scale: 1,
            duration: dur(1.02),
            stagger: dur(0.018),
            ease: 'power3.inOut',
        });
    }, null, dur(1.94));
    tl.to(rows, {
        opacity: 0,
        duration: dur(0.32),
        ease: 'sine.out',
    }, dur(2.18));
    tl.to(finalTbi, {
        opacity: 1,
        x: 0,
        filter: 'blur(0px)',
        duration: dur(0.46),
        ease: 'power3.out',
    }, dur(3.24));

    tl.to(morphRoot, {
        scale: 0.985,
        duration: dur(0.5),
        ease: 'sine.inOut',
    }, dur(3.24));
    tl.to(morphRoot, {
        autoAlpha: 0,
        y: -8,
        duration: dur(0.5),
        ease: 'power2.inOut',
    }, dur(3.68));
    tl.call(() => {
        flying.layer?.remove();
    }, null, dur(4.24));

    return tl;
}

export function createLoaderTimeline({ scene, gsap, config, root, onComplete }) {
    const s = scene.items;
    const scale = config.durationScale || 1;
    const logoScale = scene.getLogoScale?.() || 0.9;
    const finalLockupScale = logoScale * 0.5;
    const tl = gsap.timeline({
        defaults: { ease: 'power3.out' },
        onStart() {
            scene.start();
        },
        onComplete,
    });

    const at = (seconds) => seconds * scale;
    const dur = (seconds) => seconds * scale;

    delete root.dataset.final;
    root.dataset.loaderPhase = 'words';

    scene.camera.zoom = 0.98;
    scene.camera.updateProjectionMatrix();

    tl.add(createWordMorphTimeline({ gsap, root, scale }), at(0));
    tl.to(scene.camera, {
        zoom: 1.018,
        duration: dur(6.4),
        ease: 'sine.inOut',
        onUpdate: () => scene.camera.updateProjectionMatrix(),
    }, at(0));
    tl.to(s.grid.material, { opacity: 0.014, duration: dur(0.6), ease: 'sine.out' }, at(0.52));
    tl.to(s.grid.position, { x: 0.04, y: -0.035, duration: dur(6.5), ease: 'sine.inOut' }, at(0));

    tl.call(() => {
        root.dataset.loaderPhase = 'build';
    }, null, at(4));
    tl.to(s.grid.material, { opacity: 0.028, duration: dur(0.42), ease: 'sine.out' }, at(4));

    tl.fromTo(s.logoGroup.position, { y: -0.02 }, { y: 0, duration: dur(1.8), ease: 'sine.inOut' }, at(4));
    tl.fromTo(s.logoGroup.scale, {
        x: logoScale * 0.92,
        y: logoScale * 0.92,
    }, {
        x: logoScale,
        y: logoScale,
        duration: dur(1.8),
        ease: 'power2.out',
    }, at(4));

    tl.fromTo(s.components.mountain.position, { y: -0.34 }, { y: -0.08, duration: dur(0.64), ease: 'power3.out' }, at(4.07));
    tl.fromTo(s.components.mountain.scale, { x: 0.86, y: 0.86 }, { x: 1.02, y: 1.02, duration: dur(0.72), ease: 'power2.out' }, at(4.07));
    tl.to(s.components.mountain.material, { opacity: 1, duration: dur(0.42), ease: 'power2.out' }, at(4.07));

    tl.fromTo(s.components.arc.scale, { x: 0.82, y: 0.82 }, { x: 1.02, y: 1.02, duration: dur(0.66), ease: 'power2.out' }, at(4.53));
    tl.fromTo(s.components.arc.rotation, { z: 0.1 }, { z: 0, duration: dur(0.7), ease: 'power2.out' }, at(4.53));
    tl.to(s.components.arc.material, { opacity: 1, duration: dur(0.4), ease: 'power2.out' }, at(4.49));

    tl.fromTo(s.components.gear.scale, { x: 0.82, y: 0.82 }, { x: 1.02, y: 1.02, duration: dur(0.74), ease: 'power2.out' }, at(4.89));
    tl.fromTo(s.components.gear.rotation, { z: -0.18 }, { z: 0.018, duration: dur(0.9), ease: 'power2.inOut' }, at(4.87));
    tl.to(s.components.gear.material, { opacity: 1, duration: dur(0.42), ease: 'power2.out' }, at(4.85));

    tl.fromTo(s.components.sparkle.scale, { x: 0.78, y: 0.78 }, { x: 1.04, y: 1.04, duration: dur(0.48), ease: 'back.out(1.1)' }, at(5.39));
    tl.to(s.components.sparkle.material, { opacity: 1, duration: dur(0.34), ease: 'power2.out' }, at(5.33));

    tl.to(s.logoGroup.scale, {
        x: logoScale * 1.012,
        y: logoScale * 1.012,
        duration: dur(0.28),
        ease: 'sine.out',
    }, at(5.77));
    tl.to(s.logoGroup.scale, {
        x: logoScale,
        y: logoScale,
        duration: dur(0.32),
        ease: 'sine.inOut',
    }, at(6.05));
    tl.to(s.componentList.map((part) => part.scale), {
        x: 0.94,
        y: 0.94,
        duration: dur(0.38),
        ease: 'power2.inOut',
    }, at(5.93));
    tl.to(s.componentList.map((part) => part.position), {
        y: -0.08,
        duration: dur(0.38),
        ease: 'power2.inOut',
    }, at(5.93));

    tl.call(() => {
        root.dataset.loaderPhase = 'final';
    }, null, at(6.27));
    tl.set(s.finalLogo.scale, { x: 0.94, y: 0.94 }, at(6.27));
    tl.set(s.finalFlash.scale, { x: 0.94, y: 0.94 }, at(6.27));
    tl.to(s.finalLogo.material, { opacity: 1, duration: dur(0.48), ease: 'power2.inOut' }, at(6.27));
    tl.to(s.componentList.map((part) => part.material), { opacity: 0, duration: dur(0.48), ease: 'power2.inOut' }, at(6.29));
    tl.to(s.finalFlash.material, { opacity: 0.36, duration: dur(0.14), ease: 'power2.out' }, at(6.51));
    tl.to(s.finalFlash.scale, { x: 0.985, y: 0.985, duration: dur(0.34), ease: 'sine.out' }, at(6.51));
    tl.to(s.finalFlash.material, { opacity: 0, duration: dur(0.36), ease: 'power2.out' }, at(6.67));
    tl.to(s.grid.material, { opacity: 0.015, duration: dur(0.64), ease: 'sine.out' }, at(6.49));

    tl.to(s.logoGroup.scale, {
        x: finalLockupScale,
        y: finalLockupScale,
        duration: dur(0.72),
        ease: 'power3.inOut',
    }, at(6.89));
    tl.to(s.logoGroup.position, {
        y: 0.78,
        duration: dur(0.72),
        ease: 'power3.inOut',
    }, at(6.89));

    tl.fromTo(s.subtext.position, { y: -3.22 }, {
        y: -3.02,
        duration: dur(0.68),
        ease: 'power3.out',
    }, at(7.47));
    tl.fromTo(s.subtext.scale, { x: 0.66, y: 0.66 }, {
        x: 0.7,
        y: 0.7,
        duration: dur(0.68),
        ease: 'power2.out',
    }, at(7.47));
    tl.to(s.subtext.material, {
        opacity: 1,
        duration: dur(0.42),
        ease: 'power2.out',
    }, at(7.55));

    tl.to({}, { duration: dur(0.88) }, at(8.07));

    return tl;
}
