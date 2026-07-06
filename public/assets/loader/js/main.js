import { mergeLoaderConfig } from './config.js';
import { preloadLoaderAssets } from './loaders.js';
import {
    loadScript,
    prefersReducedMotion,
    sessionGet,
    sessionSet,
    sessionRemove,
    supportsWebGL,
    wait,
} from './utils.js';
import { ASOGLoaderScene } from './scene.js';
import { createLoaderTimeline } from './timeline.js';

function dispatchLoaderEvent(name, detail = {}) {
    window.dispatchEvent(new CustomEvent(name, { detail }));
}

const state = {
    root: null,
    config: null,
    scene: null,
    timeline: null,
    initialized: false,
    completing: false,
};

async function complete() {
    if (!state.root || state.completing) {
        return;
    }

    state.completing = true;
    dispatchLoaderEvent('asog-loader:complete', { mode: 'animated' });
    sessionSet(state.config.sessionKey, '1');
    state.root.dataset.state = 'complete';
    state.root.setAttribute('aria-busy', 'false');
    await wait(state.config.fadeOutMs);
    state.scene?.destroy();
    state.scene = null;
    state.timeline = null;
    state.root.remove();
    state.root = null;
    state.initialized = false;
    state.completing = false;
}

async function showStaticAndComplete(root, config, fallback = false) {
    const logo = root.querySelector('[data-asog-loader-logo]');
    if (logo && !logo.getAttribute('src')) {
        logo.src = logo.dataset.src || config.logoUrl || '';
    }

    root.dataset.static = 'true';
    if (fallback) {
        root.dataset.fallback = 'true';
    }
    if (!fallback) {
        sessionSet(config.sessionKey, '1');
    }
    await wait(config.staticHoldMs);
    dispatchLoaderEvent('asog-loader:complete', { mode: fallback ? 'fallback' : 'static' });
    root.dataset.state = 'complete';
    root.setAttribute('aria-busy', 'false');
    await wait(config.fadeOutMs);
    root.remove();
}

async function waitForLoaderFonts() {
    if (!document.fonts?.load) {
        return;
    }

    await document.fonts.load('900 122px "ASOG Loader Monas"');
}

function parseBackgroundImageUrl(value) {
    if (!value || value === 'none') {
        return '';
    }

    const match = String(value).match(/url\((['"]?)(.*?)\1\)/i);
    return match ? match[2] : '';
}

function collectLandingAssetUrls(config) {
    const urls = new Set();
    const add = (url) => {
        if (!url || typeof url !== 'string') {
            return;
        }

        try {
            urls.add(new URL(url, window.location.href).href);
        } catch (error) {
            // Ignore malformed URLs from optional content.
        }
    };

    document.querySelectorAll('#hero .slide[data-bg]').forEach((slide) => {
        add(slide.getAttribute('data-bg'));
    });

    document.querySelectorAll('#hero .slide').forEach((slide) => {
        add(parseBackgroundImageUrl(slide.style.backgroundImage));
    });

    document.querySelectorAll('img[fetchpriority="high"], img[data-landing-preload]').forEach((image) => {
        add(image.currentSrc || image.getAttribute('src'));
    });

    return Array.from(urls).slice(0, Math.max(0, config.landingPreloadLimit || 0));
}

function preloadPageImage(url) {
    return new Promise((resolve) => {
        const image = new Image();
        image.decoding = 'async';
        image.onload = async () => {
            if (image.decode) {
                try {
                    await image.decode();
                } catch (error) {
                    // Loaded images are still useful even if decode() rejects.
                }
            }
            resolve({ url, ok: true });
        };
        image.onerror = () => resolve({ url, ok: false });
        image.src = url;
    });
}

function preloadLandingAssets(config) {
    const urls = collectLandingAssetUrls(config);
    if (!urls.length) {
        return Promise.resolve({ urls, timedOut: false });
    }

    let timeoutId = 0;
    const assetWork = Promise.allSettled(urls.map(preloadPageImage)).then((results) => ({
        urls,
        timedOut: false,
        results,
    }));
    const timeoutWork = new Promise((resolve) => {
        timeoutId = window.setTimeout(() => {
            resolve({ urls, timedOut: true });
        }, Math.max(1000, config.landingPreloadMaxMs || 8500));
    });

    return Promise.race([assetWork, timeoutWork]).finally(() => {
        if (timeoutId) {
            window.clearTimeout(timeoutId);
        }
    });
}

async function init(options = {}) {
    const root = options.root || document.querySelector('[data-asog-loader-root]');
    if (!root || state.initialized) {
        return;
    }

    const config = mergeLoaderConfig(root, options);
    state.root = root;
    state.config = config;
    state.initialized = true;
    root.dataset.loaderBooted = 'true';

    if (new URLSearchParams(window.location.search).get('asog-loader') === 'reset') {
        sessionRemove(config.sessionKey);
    }

    if (config.runOnce && sessionGet(config.sessionKey) === '1') {
        dispatchLoaderEvent('asog-loader:complete', { mode: 'skipped' });
        root.remove();
        state.root = null;
        state.initialized = false;
        return;
    }

    options.onStart?.();
    dispatchLoaderEvent('asog-loader:start');

    if (prefersReducedMotion() || !supportsWebGL()) {
        await showStaticAndComplete(root, config, !supportsWebGL());
        options.onComplete?.();
        return;
    }

    try {
        const landingAssetsReady = preloadLandingAssets(config);
        const [assets, gsap] = await Promise.all([
            preloadLoaderAssets(config),
            window.gsap ? Promise.resolve(window.gsap) : loadScript(config.assets.gsap),
        ]);

        if (!gsap) {
            throw new Error('GSAP is unavailable.');
        }

        await waitForLoaderFonts();

        const mount = root.querySelector('[data-asog-loader-stage]');
        state.scene = new ASOGLoaderScene({ root, mount, config, assets });
        state.timeline = createLoaderTimeline({
            scene: state.scene,
            gsap,
            config,
            root,
            onComplete: async () => {
                await landingAssetsReady;
                options.onComplete?.();
                await complete();
            },
        });
    } catch (error) {
        window.ASOGLoader.lastError = error;
        root.dataset.error = error?.message || 'Loader failed.';
        console.warn('[ASOGLoader] Falling back to static logo.', error);
        await showStaticAndComplete(root, config, true);
        options.onComplete?.();
    }
}

function destroy() {
    state.timeline?.kill?.();
    state.scene?.destroy();
    if (state.root) {
        state.root.remove();
    }
    state.root = null;
    state.scene = null;
    state.timeline = null;
    state.initialized = false;
    state.completing = false;
}

async function skip() {
    state.timeline?.progress?.(1, false);
    await complete();
}

window.ASOGLoader = {
    init,
    destroy,
    skip,
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => init(), { once: true });
} else {
    init();
}
