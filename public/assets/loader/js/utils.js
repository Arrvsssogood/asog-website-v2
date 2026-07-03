export function prefersReducedMotion() {
    return window.matchMedia?.('(prefers-reduced-motion: reduce)').matches === true;
}

export function supportsWebGL() {
    try {
        const canvas = document.createElement('canvas');
        return !!(window.WebGLRenderingContext && (canvas.getContext('webgl') || canvas.getContext('experimental-webgl')));
    } catch (error) {
        return false;
    }
}

export function sessionGet(key) {
    try {
        return window.sessionStorage.getItem(key);
    } catch (error) {
        return null;
    }
}

export function sessionSet(key, value) {
    try {
        window.sessionStorage.setItem(key, value);
    } catch (error) {
        // Storage can be blocked in private modes; the loader should still finish.
    }
}

export function sessionRemove(key) {
    try {
        window.sessionStorage.removeItem(key);
    } catch (error) {
        // Storage can be blocked in private modes; the loader should still finish.
    }
}

export function loadScript(src) {
    return new Promise((resolve, reject) => {
        if (!src) {
            reject(new Error('Missing script source.'));
            return;
        }

        const existing = document.querySelector(`script[src="${CSS.escape(src)}"]`);
        if (existing) {
            if (window.gsap) {
                resolve(window.gsap);
                return;
            }
            existing.addEventListener('load', () => resolve(window.gsap), { once: true });
            existing.addEventListener('error', reject, { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => resolve(window.gsap);
        script.onerror = () => reject(new Error(`Failed to load ${src}`));
        document.head.appendChild(script);
    });
}

export function loadImage(src) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.decoding = 'async';
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error(`Failed to load image ${src}`));
        image.src = src;
    });
}

export function disposeObject3D(object) {
    object?.traverse?.((child) => {
        if (child.geometry) {
            child.geometry.dispose();
        }

        const materials = Array.isArray(child.material) ? child.material : [child.material];
        materials.filter(Boolean).forEach((material) => {
            Object.keys(material).forEach((key) => {
                const value = material[key];
                if (value && typeof value.dispose === 'function') {
                    value.dispose();
                }
            });
            material.dispose?.();
        });
    });
}

export function wait(ms) {
    return new Promise((resolve) => window.setTimeout(resolve, ms));
}
