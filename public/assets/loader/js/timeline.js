function renderMorphWord(word) {
    if (word === 'ASOG TBI') {
        return [
            '<span class="asog-loader-word-initial">ASOG</span>',
            '<span class="asog-loader-word-gap">&nbsp;</span>',
            '<span class="asog-loader-word-tbi">TBI</span>',
        ].join('');
    }

    return [
        '<span class="asog-loader-word-initial">',
        word.charAt(0),
        '</span><span class="asog-loader-word-rest">',
        word.slice(1),
        '</span>',
    ].join('');
}

function createWordMorphTimeline({ gsap, root, scale }) {
    const morphRoot = root.querySelector('[data-asog-loader-word-morph]');
    const wordA = root.querySelector('[data-asog-loader-word-a]');
    const wordB = root.querySelector('[data-asog-loader-word-b]');
    const words = ['ACADEME', 'SOCIETY', 'ORGANIZATION', 'GOVERNMENT', 'ASOG TBI'];

    if (!morphRoot || !wordA || !wordB) {
        return gsap.timeline();
    }

    const dur = (seconds) => seconds * scale;
    const tl = gsap.timeline();
    let active = wordA;
    let inactive = wordB;

    tl.call(() => {
        root.dataset.loaderPhase = 'words';
        wordA.innerHTML = renderMorphWord(words[0]);
        wordB.innerHTML = '';
        gsap.set(morphRoot, { autoAlpha: 1, y: 0, scale: 1 });
        gsap.set(wordA, { opacity: 1, filter: 'blur(0px)' });
        gsap.set(wordB, { opacity: 0, filter: 'blur(18px)' });
    }, null, 0);

    words.slice(1).forEach((word, index) => {
        const start = 0.7 + index * 0.88;
        const from = active;
        const to = inactive;

        tl.call(() => {
            to.innerHTML = renderMorphWord(word);
            gsap.set(to, { opacity: 0, filter: 'blur(18px)' });
        }, null, dur(start));
        tl.to(from, {
            opacity: 0,
            filter: 'blur(20px)',
            duration: dur(0.5),
            ease: 'power2.inOut',
        }, dur(start));
        tl.to(to, {
            opacity: 1,
            filter: 'blur(0px)',
            duration: dur(0.58),
            ease: 'power2.out',
        }, dur(start + 0.12));

        active = inactive;
        inactive = from;
    });

    tl.to(morphRoot, {
        scale: 0.98,
        duration: dur(0.46),
        ease: 'sine.inOut',
    }, dur(4.24));
    tl.to(morphRoot, {
        autoAlpha: 0,
        y: -10,
        duration: dur(0.46),
        ease: 'power2.inOut',
    }, dur(4.5));

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
    delete root.dataset.shine;
    root.dataset.loaderPhase = 'words';

    scene.camera.zoom = 0.98;
    scene.camera.updateProjectionMatrix();

    tl.add(createWordMorphTimeline({ gsap, root, scale }), at(0));
    tl.to(scene.camera, {
        zoom: 1.022,
        duration: dur(10.2),
        ease: 'sine.inOut',
        onUpdate: () => scene.camera.updateProjectionMatrix(),
    }, at(0));
    tl.to(s.grid.material, { opacity: 0.014, duration: dur(0.7), ease: 'sine.out' }, at(0.85));
    tl.to(s.grid.position, { x: 0.05, y: -0.04, duration: dur(10.4), ease: 'sine.inOut' }, at(0));

    tl.call(() => {
        root.dataset.loaderPhase = 'build';
    }, null, at(5));
    tl.to(s.grid.material, { opacity: 0.03, duration: dur(0.5), ease: 'sine.out' }, at(5));

    tl.fromTo(s.logoGroup.position, { y: -0.02 }, { y: 0, duration: dur(2.1), ease: 'sine.inOut' }, at(5));
    tl.fromTo(s.logoGroup.scale, {
        x: logoScale * 0.92,
        y: logoScale * 0.92,
    }, {
        x: logoScale,
        y: logoScale,
        duration: dur(2.1),
        ease: 'power2.out',
    }, at(5));

    tl.fromTo(s.components.mountain.position, { y: -0.34 }, { y: -0.08, duration: dur(0.74), ease: 'power3.out' }, at(5.08));
    tl.fromTo(s.components.mountain.scale, { x: 0.86, y: 0.86 }, { x: 1.02, y: 1.02, duration: dur(0.82), ease: 'power2.out' }, at(5.08));
    tl.to(s.components.mountain.material, { opacity: 1, duration: dur(0.5), ease: 'power2.out' }, at(5.08));

    tl.fromTo(s.components.arc.scale, { x: 0.82, y: 0.82 }, { x: 1.02, y: 1.02, duration: dur(0.78), ease: 'power2.out' }, at(5.62));
    tl.fromTo(s.components.arc.rotation, { z: 0.1 }, { z: 0, duration: dur(0.82), ease: 'power2.out' }, at(5.62));
    tl.to(s.components.arc.material, { opacity: 1, duration: dur(0.46), ease: 'power2.out' }, at(5.58));

    tl.fromTo(s.components.gear.scale, { x: 0.82, y: 0.82 }, { x: 1.02, y: 1.02, duration: dur(0.86), ease: 'power2.out' }, at(6.08));
    tl.fromTo(s.components.gear.rotation, { z: -0.18 }, { z: 0.018, duration: dur(1.1), ease: 'power2.inOut' }, at(6.06));
    tl.to(s.components.gear.material, { opacity: 1, duration: dur(0.5), ease: 'power2.out' }, at(6.02));

    tl.fromTo(s.components.sparkle.scale, { x: 0.78, y: 0.78 }, { x: 1.04, y: 1.04, duration: dur(0.54), ease: 'back.out(1.1)' }, at(6.72));
    tl.to(s.components.sparkle.material, { opacity: 1, duration: dur(0.4), ease: 'power2.out' }, at(6.64));

    tl.to(s.logoGroup.scale, {
        x: logoScale * 1.012,
        y: logoScale * 1.012,
        duration: dur(0.32),
        ease: 'sine.out',
    }, at(7.06));
    tl.to(s.logoGroup.scale, {
        x: logoScale,
        y: logoScale,
        duration: dur(0.36),
        ease: 'sine.inOut',
    }, at(7.38));
    tl.to(s.componentList.map((part) => part.scale), {
        x: 0.94,
        y: 0.94,
        duration: dur(0.46),
        ease: 'power2.inOut',
    }, at(7.2));
    tl.to(s.componentList.map((part) => part.position), {
        y: -0.08,
        duration: dur(0.46),
        ease: 'power2.inOut',
    }, at(7.2));

    tl.call(() => {
        root.dataset.loaderPhase = 'final';
    }, null, at(7.52));
    tl.set(s.finalLogo.scale, { x: 0.94, y: 0.94 }, at(7.52));
    tl.set(s.finalFlash.scale, { x: 0.94, y: 0.94 }, at(7.52));
    tl.to(s.finalLogo.material, { opacity: 1, duration: dur(0.62), ease: 'power2.inOut' }, at(7.52));
    tl.to(s.componentList.map((part) => part.material), { opacity: 0, duration: dur(0.62), ease: 'power2.inOut' }, at(7.54));
    tl.to(s.finalFlash.material, { opacity: 0.42, duration: dur(0.18), ease: 'power2.out' }, at(7.82));
    tl.to(s.finalFlash.scale, { x: 0.985, y: 0.985, duration: dur(0.42), ease: 'sine.out' }, at(7.82));
    tl.to(s.finalFlash.material, { opacity: 0, duration: dur(0.46), ease: 'power2.out' }, at(8.02));
    tl.to(s.grid.material, { opacity: 0.015, duration: dur(0.8), ease: 'sine.out' }, at(7.78));

    tl.to(s.logoGroup.scale, {
        x: finalLockupScale,
        y: finalLockupScale,
        duration: dur(0.86),
        ease: 'power3.inOut',
    }, at(8.42));
    tl.to(s.logoGroup.position, {
        y: 0.78,
        duration: dur(0.86),
        ease: 'power3.inOut',
    }, at(8.42));

    tl.fromTo(s.subtext.position, { y: -3.22 }, {
        y: -3.02,
        duration: dur(0.84),
        ease: 'power3.out',
    }, at(9.18));
    tl.fromTo(s.subtext.scale, { x: 0.66, y: 0.66 }, {
        x: 0.7,
        y: 0.7,
        duration: dur(0.84),
        ease: 'power2.out',
    }, at(9.18));
    tl.to(s.subtext.material, {
        opacity: 1,
        duration: dur(0.54),
        ease: 'power2.out',
    }, at(9.3));

    tl.to({}, { duration: dur(2.18) }, at(9.86));

    return tl;
}
