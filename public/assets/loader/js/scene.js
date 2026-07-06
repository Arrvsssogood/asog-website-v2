import * as THREE from 'three';
import { disposeObject3D } from './utils.js';

function makeImagePlane(THREERef, image, size, depth = 0) {
    const texture = new THREERef.Texture(image);
    texture.colorSpace = THREERef.SRGBColorSpace;
    texture.needsUpdate = true;

    const aspect = image.naturalWidth / Math.max(1, image.naturalHeight);
    const material = new THREERef.MeshBasicMaterial({
        map: texture,
        transparent: true,
        opacity: 0,
        depthWrite: false,
        side: THREERef.DoubleSide,
        premultipliedAlpha: true,
    });
    const mesh = new THREERef.Mesh(new THREERef.PlaneGeometry(size * aspect, size), material);
    mesh.position.z = depth;
    mesh.userData.texture = texture;
    return mesh;
}

function makeGrid(THREERef) {
    const size = 12;
    const step = 0.34;
    const points = [];

    for (let value = -size; value <= size; value += step) {
        points.push(new THREERef.Vector3(-size, value, -1), new THREERef.Vector3(size, value, -1));
        points.push(new THREERef.Vector3(value, -size, -1), new THREERef.Vector3(value, size, -1));
    }

    const geometry = new THREERef.BufferGeometry().setFromPoints(points);
    const material = new THREERef.LineBasicMaterial({
        color: 0x2A4282,
        transparent: true,
        opacity: 0,
        depthWrite: false,
    });
    return new THREERef.LineSegments(geometry, material);
}

export class ASOGLoaderScene {
    constructor({ root, mount, config, assets }) {
        this.root = root;
        this.mount = mount;
        this.config = config;
        this.assets = assets;
        this.animationFrame = 0;
        this.running = false;
        this.items = {};
        this.logoScale = 0.9;

        this.renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true,
            powerPreference: 'high-performance',
        });
        this.renderer.setClearColor(0xf8fbfd, 0);
        this.renderer.outputColorSpace = THREE.SRGBColorSpace;
        this.mount.appendChild(this.renderer.domElement);

        this.scene = new THREE.Scene();
        this.scene.background = null;
        this.camera = new THREE.OrthographicCamera(-5, 5, 3, -3, 0.1, 100);
        this.camera.position.set(0, 0, 8);

        this.logoGroup = new THREE.Group();
        this.items.grid = makeGrid(THREE);
        this.items.logoGroup = this.logoGroup;
        this.scene.add(this.items.grid, this.logoGroup);

        this.createObjects();
        this.resize = this.resize.bind(this);
        this.render = this.render.bind(this);
        window.addEventListener('resize', this.resize, { passive: true });
        this.resize();
    }

    createObjects() {
        const markSize = 4.1;

        this.items.components = {
            gear: makeImagePlane(THREE, this.assets.components.gear, markSize, 0),
            arc: makeImagePlane(THREE, this.assets.components.arc, markSize, 0.04),
            sparkle: makeImagePlane(THREE, this.assets.components.sparkle, markSize, 0.08),
            mountain: makeImagePlane(THREE, this.assets.components.mountain, markSize, 0.12),
        };
        this.items.componentList = [
            this.items.components.gear,
            this.items.components.arc,
            this.items.components.sparkle,
            this.items.components.mountain,
        ];
        this.items.componentList.forEach((part) => {
            part.position.set(0, -0.08, part.position.z);
            part.scale.setScalar(0.94);
        });

        const finalLogoImage = this.assets.stageLogo;
        const logoTexture = new THREE.Texture(finalLogoImage);
        logoTexture.colorSpace = THREE.SRGBColorSpace;
        logoTexture.needsUpdate = true;
        const logoAspect = finalLogoImage.naturalWidth / Math.max(1, finalLogoImage.naturalHeight);
        const finalLogoSize = markSize;
        const finalLogoGeometry = new THREE.PlaneGeometry(finalLogoSize * logoAspect, finalLogoSize);
        this.items.finalLogo = new THREE.Mesh(
            finalLogoGeometry,
            new THREE.MeshBasicMaterial({
                map: logoTexture,
                transparent: true,
                opacity: 0,
                depthWrite: false,
            }),
        );
        this.items.finalLogo.position.set(0, -0.08, 0.6);
        this.items.finalLogo.scale.setScalar(0.94);
        this.items.finalFlash = new THREE.Mesh(
            finalLogoGeometry.clone(),
            new THREE.MeshBasicMaterial({
                map: logoTexture,
                color: 0xeaf7ff,
                transparent: true,
                opacity: 0,
                depthWrite: false,
                blending: THREE.AdditiveBlending,
            }),
        );
        this.items.finalFlash.position.copy(this.items.finalLogo.position);
        this.items.finalFlash.position.z = 0.66;
        this.items.finalFlash.scale.copy(this.items.finalLogo.scale);
        this.items.subtext = makeImagePlane(THREE, this.assets.subtext, finalLogoSize, 0.64);
        this.items.subtext.position.set(0, -1.18, 0.64);
        this.items.subtext.scale.setScalar(0.7);

        this.logoGroup.add(
            ...this.items.componentList,
            this.items.finalLogo,
            this.items.finalFlash,
            this.items.subtext,
        );
        this.logoGroup.scale.setScalar(this.logoScale);
    }

    resize() {
        const width = Math.max(1, this.root.clientWidth || window.innerWidth);
        const height = Math.max(1, this.root.clientHeight || window.innerHeight);
        const aspect = width / height;
        const viewHeight = width < 640 ? 7.2 : 6.2;
        const viewWidth = viewHeight * aspect;
        this.logoScale = width < 640 ? 0.58 : (width < 1024 ? 0.72 : 0.9);

        this.camera.left = -viewWidth / 2;
        this.camera.right = viewWidth / 2;
        this.camera.top = viewHeight / 2;
        this.camera.bottom = -viewHeight / 2;
        this.camera.updateProjectionMatrix();

        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, this.config.pixelRatioMax));
        this.renderer.setSize(width, height, false);
    }

    getLogoScale() {
        return this.logoScale;
    }

    start() {
        if (this.running) {
            return;
        }
        this.running = true;
        this.render();
    }

    render() {
        if (!this.running) {
            return;
        }
        this.animationFrame = window.requestAnimationFrame(this.render);
        this.renderer.render(this.scene, this.camera);
    }

    stop() {
        this.running = false;
        if (this.animationFrame) {
            window.cancelAnimationFrame(this.animationFrame);
            this.animationFrame = 0;
        }
    }

    destroy() {
        this.stop();
        window.removeEventListener('resize', this.resize);
        disposeObject3D(this.scene);
        this.renderer?.dispose?.();
        this.renderer?.domElement?.remove?.();
    }
}
