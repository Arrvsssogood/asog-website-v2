export const ASOG_LOADER_CONFIG = {
    sessionKey: 'asog_loader_seen_v1',
    durationScale: 1,
    runOnce: true,
    pixelRatioMax: 1.75,
    fadeOutMs: 860,
    staticHoldMs: 920,
    landingPreloadMaxMs: 8500,
    landingPreloadLimit: 8,
    colors: {
        blue: 0x42A3D8,
        deepBlue: 0x2A4282,
        navy: 0x2A4282,
        gold: 0xF8AF21,
        orange: 0xF47B20,
        white: 0xFFFFFF,
        graphite: 0x17344D,
    },
    assets: {
        gsap: 'vendor/gsap.min.js',
        stageLogo: 'img/full-logo.png',
        subtext: 'img/subtext.png',
        logo: '../img/ASOG TBI/PNG/vertical-light.png',
        components: {
            gear: 'img/gear.png',
            arc: 'img/arc.png',
            mountain: 'img/mountain.png',
            sparkle: 'img/sparkle.png',
        },
    },
};

export function mergeLoaderConfig(root, overrides = {}) {
    const base = root?.dataset?.loaderBase || '/assets/loader';
    const logoUrl = root?.dataset?.logoUrl || ASOG_LOADER_CONFIG.assets.logo;

    return {
        ...ASOG_LOADER_CONFIG,
        ...overrides,
        base,
        logoUrl,
        durationScale: Number(overrides.durationScale || ASOG_LOADER_CONFIG.durationScale) || 1,
        runOnce: overrides.runOnce ?? ASOG_LOADER_CONFIG.runOnce,
        assets: {
            ...ASOG_LOADER_CONFIG.assets,
            ...(overrides.assets || {}),
            stageLogo: `${base}/${ASOG_LOADER_CONFIG.assets.stageLogo}`,
            subtext: `${base}/${ASOG_LOADER_CONFIG.assets.subtext}`,
            logo: logoUrl,
            gsap: `${base}/${ASOG_LOADER_CONFIG.assets.gsap}`,
            components: Object.fromEntries(
                Object.entries(ASOG_LOADER_CONFIG.assets.components).map(([key, path]) => [key, `${base}/${path}`]),
            ),
        },
    };
}
