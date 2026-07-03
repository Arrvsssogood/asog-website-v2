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
    root.dataset.static = 'true';
    if (fallback) {
        root.dataset.fallback = 'true';
    }
    if (!fallback) {
        sessionSet(config.sessionKey, '1');
    }
    await wait(config.staticHoldMs);
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
        root.remove();
        state.root = null;
        state.initialized = false;
        return;
    }

    options.onStart?.();

    if (prefersReducedMotion() || !supportsWebGL()) {
        await showStaticAndComplete(root, config, !supportsWebGL());
        options.onComplete?.();
        return;
    }

    try {
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
