/* =====================================================================
   ALTITUDE 3D — Wilderness-Zoom Transition + Three.js Mountain
   ===================================================================== */
import * as THREE from 'https://esm.sh/three@0.170.0';
import { OrbitControls } from 'https://esm.sh/three@0.170.0/examples/jsm/controls/OrbitControls.js';

/* =============================================================
   DOM REFS
   ============================================================= */
const card      = document.getElementById('altitudeExploreCard');
const zoomOvl   = document.getElementById('alt3dZoomOverlay');
const zoomText  = document.getElementById('alt3dZoomText');
const overlay   = document.getElementById('alt3dOverlay');
const cv        = document.getElementById('alt3dCanvas');
const closeBtn  = document.getElementById('alt3dClose');
const hintCloseBtn = document.getElementById('alt3dHintClose');
const infoCard  = document.getElementById('alt3dInfo');
const lblCont   = document.getElementById('alt3dLabels');
const hintEl    = document.getElementById('alt3dHint');
const miniInfoBtn = document.getElementById('alt3dInfoBtn');
const infoModal = document.getElementById('alt3dInfoModal');
const infoModalClose = document.getElementById('alt3dInfoModalClose');
const stepAboutBtn = document.getElementById('ciBtnAboutStep');
const stepModal = document.getElementById('alt3dStepModal');
const stepModalClose = document.getElementById('alt3dStepModalClose');
const ciStepNum = document.getElementById('ciStepNum');
const ciStepName = document.getElementById('ciStepName');
const ciStepPhase = document.getElementById('ciStepPhase');
const ciStepDur = document.getElementById('ciStepDur');
const ciStepDesc = document.getElementById('ciStepDesc');
const partnersBtn = document.getElementById('alt3dShowPartners');
const partnersPanel = document.getElementById('alt3dPartnersPanel');
const partnersUpBtn = document.getElementById('alt3dPartnersUp');

/* Info card fields */
const ciNum   = document.getElementById('ciNum');
const ciName  = document.getElementById('ciName');
const ciPhase = document.getElementById('ciPhase');
const ciDur   = document.getElementById('ciDur');
const ciDesc  = document.getElementById('ciDesc');
const btnPrev = document.getElementById('ciBtnPrev');
const btnNext = document.getElementById('ciBtnNext');
const btnOvw  = document.getElementById('ciBtnOverview');

if (!card || !overlay || !cv) {
  console.warn('[altitude3d] missing DOM elements');
}

/* =============================================================
   STATE
   ============================================================= */
let sceneReady = false;
let active = -1, zoomed = false;
let R, scene, cam, oc, G;
const pinHits = [], flags = [];
let isClosingOverlay = false;
let hArmL = null, hArmR = null;
let hHeadRef = null;
let awarenessBubble = null;
let awarenessBubbleTimer = null;

/* Hero state — module scope so zoomToFlag / overviewCamera can access */
let hero, heroStage = -1, heroAnimState = 'idle', heroAnimTime = 0;
let stageCams = [];
let hStick, hMapMesh, heroFlagGroup;
let heroTrailheadMarks = null;
let confettiGroup = null, confettiParts = [];
let trailCurve = null;
let cloudMatRef = null;
let summitFlagGroup = null;
let summitFlagPlanted = false;
const AWARENESS_INDEX = 0;
const TRAILHEAD_INDEX = 1;
const BASECAMP_INDEX = 2;
const ASCENT_INDEX = 3;
const SUMMIT_INDEX = 4;
const stageTValues = [0.02, 0.20, 0.44, 0.70, 0.94]; /* includes Stage 00 before Step 1 */
let heroTrailT = stageTValues[AWARENESS_INDEX];
let heroWalkTween = null;

const MAIN_PEAK_CENTER = new THREE.Vector3(0.3, 0, -1.5);

/* -- Stage data -- */
const S = [
  { id:'00', n:'Awareness Caravan', p:'Program Entry', d:'Before Month 1',
    x:'Potential teams are identified and oriented before formal incubation begins.',
    m:[
      'Identifying potential startups and founder teams',
      'Introducing innovators to ASOG TBI opportunities',
      'Selecting teams for formal incubation stages'
    ],
    pos: null },
  { id:'01', n:'Trailhead', p:'Pre-Incubation', d:'Month 1',
    x:'The Trailhead stage focuses on refining the startup concept and validating whether the identified problem is worth solving. Founders work closely with mentors and industry validators to ensure that their proposed solution addresses real market needs within the food and agri-tech value chain.',
    m:[
      'Clarifying the target problem and user segment',
      "Validating the startup's problem-solution fit",
      'Refining the initial concept through expert feedback'
    ],
    pos: null },
  { id:'02', n:'Basecamp', p:'Incubation Phase', d:'Months 2-4',
    x:"During the Basecamp stage, startups begin building and testing their solutions. Founders develop minimum viable products (MVPs), validate product features with early adopters, and shape both the technical and business foundations of the venture.",
    m:[
      'Building and testing MVP prototypes',
      "Developing the startup's business model",
      'Preparing for market entry and early partnerships',
      'Planning customer acquisition strategies within the agri and food ecosystem'
    ],
    pos: null },
  { id:'03', n:'Ascent', p:'Post-Validation', d:'Months 5-6',
    x:'At this stage, startups prepare for investment and funding opportunities. Teams develop the documentation and presentation materials necessary to approach investors, grant providers, and funding institutions.',
    m:[
      'Developing investor-ready pitch decks',
      'Preparing funding proposals and business documentation',
      'Connecting with potential agri investors and angel networks'
    ],
    pos: null },
  { id:'04', n:'Summit Launch', p:'Post-Incubation', d:'Beyond Month 6',
    x:'The Summit Launch stage supports startups as they transition from early validation to long-term growth. Startups receive continued strategic support as they scale operations and expand partnerships.',
    m:[
      'Developing long-term scaling strategies',
      'Strengthening partnerships and market networks',
      'Connecting with venture builders and ecosystem partners'
    ],
    pos: null },
];

/* =============================================================
   WILDERNESS-ZOOM TRANSITION
   ============================================================= */

function spawnParticles() {
  for (let i = 0; i < 15; i++) {
    const p = document.createElement('div');
    p.className = 'alt3d-particle';
    p.style.left = Math.random() * 100 + '%';
    p.style.top  = Math.random() * 100 + '%';
    zoomOvl.appendChild(p);
    gsap.fromTo(p,
      { opacity: 0, y: 0 },
      { opacity: Math.random() * .3 + .1,
        y: -(Math.random() * 120 + 60),
        duration: Math.random() * 1 + .6,
        delay: Math.random() * .4,
        ease: 'power1.out',
        onComplete() { p.remove(); }
      }
    );
  }
}

export function openWildernessZoom() {
  if (zoomOvl.classList.contains('active') || overlay.classList.contains('active') || isClosingOverlay) {
    return;
  }
  document.body.style.overflow = 'hidden';
  zoomOvl.classList.add('active');
  const tl = gsap.timeline();
  tl.to(zoomOvl, { opacity: 1, duration: .4, ease: 'power2.inOut' })
    .call(spawnParticles, [], '<+=.05')
    .fromTo('#alt3dMtnFar',  { y: '20%' }, { y: '0%', duration: .8, ease: 'power2.out' }, '<')
    .fromTo('#alt3dMtnMid',  { y: '28%' }, { y: '0%', duration: .8, ease: 'power2.out' }, '<+=.05')
    .fromTo('#alt3dMtnNear', { y: '35%' }, { y: '0%', duration: .8, ease: 'power2.out' }, '<+=.05')
    .to(zoomText, { opacity: 1, y: 0, duration: .45, ease: 'power2.out' }, '-=.4')
    .call(() => {
      if (!sceneReady) initScene();
      overlay.scrollTop = 0;
      overlay.classList.add('active');
      if (overlay.requestFullscreen) {
        overlay.requestFullscreen().catch(() => {});
      }
    }, [], '+=.3')
    .to(overlay, { opacity: 1, duration: .5, ease: 'power2.inOut' }, '+=.02')
    .to(zoomOvl, { opacity: 0, duration: .35, ease: 'power2.in' }, '-=.25')
    .call(() => {
      zoomOvl.classList.remove('active');
      zoomText.style.opacity = 0;
      if (overlay.classList.contains('active')) {
        zoomed = false;
        active = -1;
        overlay.classList.remove('zoomed');
        if (oc) {
          oc.enabled = true;
          oc.target.copy(OVT);
          oc.update();
        }
        if (cam) cam.position.copy(OVP);
        hideInfoCard();
        hideMiniInfo();
        closeStepModal();
      }
    });
}

function closeOverlay() {
  if (isClosingOverlay) return;
  isClosingOverlay = true;
  hideMiniInfo();
  closeStepModal();
  hideAwarenessBubble();
  if (overlay) {
    overlay.classList.remove('is-below-scene');
    overlay.scrollTop = 0;
  }
  if (partnersBtn) partnersBtn.setAttribute('aria-expanded', 'false');
  if (zoomed) overviewCamera();
  const tl = gsap.timeline();
  tl.to(overlay, { opacity: 0, duration: .45, ease: 'power2.in' })
    .call(() => {
      overlay.classList.remove('active');
      document.body.style.overflow = '';
      if (document.fullscreenElement && document.exitFullscreen) {
        document.exitFullscreen().catch(() => {});
      }
      isClosingOverlay = false;
    });
}

function hideMiniInfo() {
  if (!infoModal || !miniInfoBtn) return;
  infoModal.hidden = true;
  miniInfoBtn.setAttribute('aria-expanded', 'false');
}

function showMiniInfo() {
  if (!infoModal || !miniInfoBtn) return;
  infoModal.hidden = false;
  miniInfoBtn.setAttribute('aria-expanded', 'true');
}

function closeStepModal() {
  if (!stepModal) return;
  stepModal.hidden = true;
}

function ensureAwarenessBubble() {
  if (awarenessBubble || !overlay) return awarenessBubble;
  awarenessBubble = document.createElement('div');
  awarenessBubble.id = 'alt3dThinkingBubble';
  awarenessBubble.className = 'alt3d-thinking-bubble';
  awarenessBubble.setAttribute('aria-hidden', 'true');
  awarenessBubble.innerHTML = `
    <span class="alt3d-thinking-bubble-dots" aria-hidden="true"><span></span><span></span><span></span></span>
  `;
  overlay.appendChild(awarenessBubble);
  return awarenessBubble;
}

function positionAwarenessBubble() {
  if (!awarenessBubble || !cam || !hHeadRef) return;

  const headWorld = new THREE.Vector3();
  hHeadRef.getWorldPosition(headWorld);
  const headScreen = headWorld.project(cam);
  if (headScreen.z >= 1) return;

  const px = (headScreen.x * 0.5 + 0.5) * window.innerWidth;
  const py = (-headScreen.y * 0.5 + 0.5) * window.innerHeight;

  const bubbleW = awarenessBubble.offsetWidth || 92;
  const bubbleH = awarenessBubble.offsetHeight || 56;
  const pad = 10;

  const left = Math.min(
    Math.max(px + 28, pad),
    window.innerWidth - bubbleW - pad
  );
  const top = Math.min(
    Math.max(py - bubbleH * 0.5, pad),
    window.innerHeight - bubbleH - pad
  );

  awarenessBubble.style.left = `${Math.round(left)}px`;
  awarenessBubble.style.top = `${Math.round(top)}px`;
}

function hideAwarenessBubble() {
  if (awarenessBubbleTimer) {
    clearTimeout(awarenessBubbleTimer);
    awarenessBubbleTimer = null;
  }
  if (!awarenessBubble) return;
  awarenessBubble.classList.remove('is-visible');
  awarenessBubble.setAttribute('aria-hidden', 'true');
}

function showAwarenessBubble() {
  const bubble = ensureAwarenessBubble();
  if (!bubble) return;
  if (awarenessBubbleTimer) {
    clearTimeout(awarenessBubbleTimer);
    awarenessBubbleTimer = null;
  }
  bubble.setAttribute('aria-hidden', 'false');
  bubble.classList.add('is-visible');
  positionAwarenessBubble();
  requestAnimationFrame(positionAwarenessBubble);
  awarenessBubbleTimer = setTimeout(() => {
    hideAwarenessBubble();
  }, 2400);
}

function syncOverlayScrollState() {
  if (!overlay) return;
  const belowScene = overlay.scrollTop > window.innerHeight * 0.35;
  overlay.classList.toggle('is-below-scene', belowScene);
  if (partnersBtn) partnersBtn.setAttribute('aria-expanded', belowScene ? 'true' : 'false');
}

function scrollToPartners() {
  if (!overlay || !partnersPanel) return;


  overlay.scrollTo({ top: window.innerHeight, behavior: 'smooth' });
}

function scrollToScene() {
  if (!overlay) return;
  overlay.scrollTo({ top: 0, behavior: 'smooth' });
}

function openStepModal(i) {
  if (!stepModal || !ciStepDesc) return;
  const s = S[i];
  if (!s) return;
  ciStepNum.textContent = 'Stage ' + s.id;
  ciStepName.textContent = s.n;
  ciStepPhase.textContent = s.p;
  ciStepDur.textContent = s.d;
  const milestones = Array.isArray(s.m)
    ? `<ul class="ci-list">${s.m.map(item => `<li>${item}</li>`).join('')}</ul>`
    : '';
  const descText = (s.x && String(s.x).trim().length)
    ? s.x
    : 'This stage contains the key activities and milestones for startup progress.';
  ciStepDesc.innerHTML = `<p>${descText}</p>${milestones}${i === SUMMIT_INDEX ? '<p class="ci-impact">Through the ALTITUDE Program, ASOG TBI gives startups a clear pathway from idea to impact.</p>' : ''}`;
  stepModal.hidden = false;
}

function setCloudOpacity(target, duration = 0.35) {
  if (!cloudMatRef) return;
  gsap.to(cloudMatRef, { opacity: target, duration, ease: 'power2.out' });
}

/* =============================================================
   INPUT VALIDATION UTILITIES
   ============================================================= */

/**
 * Validates text input (non-empty)
 * @param {string} value - Value to validate
 * @param {number} minLength - Minimum length (default 1)
 * @param {number} maxLength - Maximum length (default 500)
 * @returns {object} {valid: boolean, error: string}
 */
function validateTextInput(value, minLength = 1, maxLength = 500) {
  const trimmed = String(value).trim();
  if (trimmed.length < minLength) {
    return { valid: false, error: `Required. Minimum ${minLength} character(s).` };
  }
  if (trimmed.length > maxLength) {
    return { valid: false, error: `Maximum ${maxLength} characters allowed.` };
  }
  return { valid: true, error: '' };
}

/**
 * Validates email format
 * @param {string} email - Email to validate
 * @returns {object} {valid: boolean, error: string}
 */
function validateEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(String(email).trim())) {
    return { valid: false, error: 'Please enter a valid email address.' };
  }
  return { valid: true, error: '' };
}

/**
 * Validates phone number (flexible format)
 * @param {string} phone - Phone number to validate
 * @returns {object} {valid: boolean, error: string}
 */
function validatePhoneNumber(phone) {
  const phoneRegex = /^[0-9\-\+\s\(\)]{7,}$/;
  if (!phoneRegex.test(String(phone).trim())) {
    return { valid: false, error: 'Please enter a valid phone number.' };
  }
  return { valid: true, error: '' };
}

/**
 * Displays validation error message on an element
 * @param {HTMLElement} element - Form input element
 * @param {string} errorMessage - Error message to display
 */
function showValidationError(element, errorMessage) {
  if (!element) return;
  element.classList.add('is-invalid');
  let errorEl = element.parentElement?.querySelector('.validation-error');
  if (!errorEl) {
    errorEl = document.createElement('span');
    errorEl.className = 'validation-error';
    element.parentElement?.appendChild(errorEl);
  }
  errorEl.textContent = errorMessage;
}

/**
 * Clears validation error from an element
 * @param {HTMLElement} element - Form input element
 */
function clearValidationError(element) {
  if (!element) return;
  element.classList.remove('is-invalid');
  const errorEl = element.parentElement?.querySelector('.validation-error');
  if (errorEl) errorEl.textContent = '';
}

/**
 * Validates entire form and shows errors
 * @param {HTMLFormElement} form - Form element to validate
 * @returns {boolean} True if all fields are valid
 */
function validateForm(form) {
  if (!form) return false;
  let isValid = true;
  const inputs = form.querySelectorAll('[required]');
  
  inputs.forEach(input => {
    let validation;
    const value = String(input.value).trim();
    
    if (input.type === 'email') {
      validation = validateEmail(value);
    } else if (input.type === 'tel') {
      validation = validatePhoneNumber(value);
    } else {
      const minLen = parseInt(input.dataset.minLength) || 1;
      const maxLen = parseInt(input.dataset.maxLength) || 500;
      validation = validateTextInput(value, minLen, maxLen);
    }
    
    if (!validation.valid) {
      showValidationError(input, validation.error);
      isValid = false;
    } else {
      clearValidationError(input);
    }
  });
  
  return isValid;
}

if (card) {
  card.addEventListener('click', openWildernessZoom);
  card.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openWildernessZoom(); } });
}
if (closeBtn) closeBtn.addEventListener('click', closeOverlay);
if (hintCloseBtn) hintCloseBtn.addEventListener('click', closeOverlay);

if (miniInfoBtn && infoModal) {
  miniInfoBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    if (infoModal.hidden) showMiniInfo(); else hideMiniInfo();
  });
}
if (infoModalClose) infoModalClose.addEventListener('click', hideMiniInfo);
if (infoModal) {
  infoModal.addEventListener('click', (e) => {
    if (e.target === infoModal) hideMiniInfo();
  });
}
if (stepAboutBtn) {
  stepAboutBtn.addEventListener('click', () => {
    const targetStage = active >= 0 ? active : AWARENESS_INDEX;
    openStepModal(targetStage);
  });
}
if (stepModalClose) stepModalClose.addEventListener('click', closeStepModal);
if (stepModal) {
  stepModal.addEventListener('click', (e) => {
    if (e.target === stepModal) closeStepModal();
  });
}

if (partnersBtn) {
  partnersBtn.addEventListener('click', () => {
    scrollToPartners();
  });
}

if (partnersUpBtn) {
  partnersUpBtn.addEventListener('click', () => {
    scrollToScene();
  });
}

if (overlay) {
  overlay.addEventListener('scroll', () => {
    syncOverlayScrollState();
  }, { passive: true });
}

// When the browser exits fullscreen (e.g. user presses ESC), we intentionally
// do NOT close the overlay. The 3D experience continues running in windowed mode.
// The overlay is only dismissed by the X button or Escape when NOT in fullscreen.
document.addEventListener('fullscreenchange', () => {
  // no-op: let fullscreen exit silently without closing the overlay
});

/* =============================================================
   THREE.JS SCENE — Cartoonish grassland mountain
   ============================================================= */

function initScene() {
  if (sceneReady) return;
  sceneReady = true;

  /* Renderer */
  R = new THREE.WebGLRenderer({ canvas: cv, antialias: true });
  R.setPixelRatio(Math.min(devicePixelRatio, 2));
  R.setSize(window.innerWidth, window.innerHeight);
  R.shadowMap.enabled = true;
  R.shadowMap.type = THREE.PCFSoftShadowMap;
  R.toneMapping = THREE.ACESFilmicToneMapping;
  R.toneMappingExposure = 1.2;

  /* Scene */
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0x87ceeb);
  scene.fog = new THREE.Fog(0x87ceeb, 25, 55);

  /* Camera — cinematic hiking perspective, closer to trail */
  cam = new THREE.PerspectiveCamera(42, window.innerWidth / window.innerHeight, 0.1, 200);
  cam.position.set(10.5, 9.0, 19.5);

  oc = new OrbitControls(cam, cv);
  oc.enableDamping = true; oc.dampingFactor = 0.06;
  oc.enablePan = false;
  oc.minDistance = 5; oc.maxDistance = 40;
  oc.minPolarAngle = 0.28;
  oc.maxPolarAngle = Math.PI / 2.1;
  oc.target.set(0, 2.5, 0); oc.update();

  /* Lights */
  scene.add(new THREE.HemisphereLight(0xdceef8, 0x8ab0c4, 0.7));
  scene.add(new THREE.AmbientLight(0xffffff, 0.4));
  const sun = new THREE.DirectionalLight(0xfffaf0, 1.4);
  sun.position.set(7, 16, 9);
  sun.castShadow = true;
  sun.shadow.mapSize.set(1024, 1024);
  sun.shadow.camera.far = 45;
  sun.shadow.camera.left = -14; sun.shadow.camera.right = 14;
  sun.shadow.camera.top = 18; sun.shadow.camera.bottom = -4;
  sun.shadow.bias = -0.0005;
  scene.add(sun);
  const fill = new THREE.DirectionalLight(0xb0d0e0, 0.35);
  fill.position.set(-5, 5, -3);
  scene.add(fill);

  /* === Materials — warm cartoonish palette === */
  const mGrass    = new THREE.MeshStandardMaterial({ color: 0x5a9e3e, roughness: 0.85 });
  const mGrassD   = new THREE.MeshStandardMaterial({ color: 0x3d7a28, roughness: 0.85 });
  const mDirt     = new THREE.MeshStandardMaterial({ color: 0xc4a46c, roughness: 0.95 });
  const mRock     = new THREE.MeshStandardMaterial({ color: 0x8a8070, roughness: 0.9 });
  const mRockD    = new THREE.MeshStandardMaterial({ color: 0x6e655a, roughness: 0.9 });
  const mTrunk    = new THREE.MeshStandardMaterial({ color: 0x6b4226, roughness: 0.85 });
  const mLeaf1    = new THREE.MeshStandardMaterial({ color: 0x2d6b1a, roughness: 0.8 });
  const mLeaf2    = new THREE.MeshStandardMaterial({ color: 0x4a8c2a, roughness: 0.8 });
  const mLeaf3    = new THREE.MeshStandardMaterial({ color: 0x60a836, roughness: 0.8 });
  const mFlower1  = new THREE.MeshStandardMaterial({ color: 0xf7d75a, roughness: 0.6 });
  const mFlower2  = new THREE.MeshStandardMaterial({ color: 0xe86040, roughness: 0.6 });
  const mFlower3  = new THREE.MeshStandardMaterial({ color: 0xd05aba, roughness: 0.6 });
  const mSkin     = new THREE.MeshStandardMaterial({ color: 0xf0c8a0, roughness: 0.7 });
  const mShirt1   = new THREE.MeshStandardMaterial({ color: 0xe85050, roughness: 0.7 });
  const mShirt2   = new THREE.MeshStandardMaterial({ color: 0x3588d0, roughness: 0.7 });
  const mShirt3   = new THREE.MeshStandardMaterial({ color: 0xf0a030, roughness: 0.7 });
  const mPants    = new THREE.MeshStandardMaterial({ color: 0x3a4a5a, roughness: 0.8 });
  const mBackpack = new THREE.MeshStandardMaterial({ color: 0xc06030, roughness: 0.75 });
  const mRed      = new THREE.MeshStandardMaterial({ color: 0xe85040, roughness: 0.45, side: THREE.DoubleSide });
  const mGold     = new THREE.MeshStandardMaterial({ color: 0xF8AF21, roughness: 0.35, metalness: 0.25, side: THREE.DoubleSide });

  function makeMarkMaterial(color, emissive) {
    return new THREE.MeshStandardMaterial({
      color,
      emissive,
      emissiveIntensity: 0.24,
      roughness: 0.38,
      metalness: 0.12
    });
  }

  function makeTrailheadMark(kind, color, emissive) {
    const g = new THREE.Group();
    if (kind === 'lightbulb') {
      const glassMat = new THREE.MeshStandardMaterial({
        color,
        emissive,
        emissiveIntensity: 0.34,
        roughness: 0.18,
        metalness: 0.04,
        transparent: true,
        opacity: 0.9
      });
      const glowMat = new THREE.MeshStandardMaterial({
        color: 0xfff3c2,
        emissive: 0xa67a12,
        emissiveIntensity: 0.28,
        roughness: 0.35,
        metalness: 0.02,
        transparent: true,
        opacity: 0.68
      });
      const metalMat = new THREE.MeshStandardMaterial({
        color: 0xc3cad3,
        emissive: 0x202833,
        emissiveIntensity: 0.1,
        roughness: 0.34,
        metalness: 0.78
      });
      const contactMat = new THREE.MeshStandardMaterial({
        color: 0x7b4d1f,
        emissive: 0x281507,
        emissiveIntensity: 0.08,
        roughness: 0.55,
        metalness: 0.22
      });
      const filamentMat = new THREE.MeshStandardMaterial({
        color: 0xf2b94b,
        emissive: 0x7f5a12,
        emissiveIntensity: 0.2,
        roughness: 0.48,
        metalness: 0.28
      });

      const bulb = new THREE.Mesh(new THREE.SphereGeometry(0.152, 26, 22), glassMat);
      bulb.scale.set(1.0, 1.22, 1.0);
      bulb.position.y = 0.16;
      g.add(bulb);

      const bulbCore = new THREE.Mesh(new THREE.SphereGeometry(0.094, 20, 16), glowMat);
      bulbCore.scale.set(0.92, 1.08, 0.92);
      bulbCore.position.y = 0.155;
      g.add(bulbCore);

      const glassNeck = new THREE.Mesh(new THREE.CylinderGeometry(0.05, 0.066, 0.07, 20), glassMat);
      glassNeck.position.y = 0.018;
      g.add(glassNeck);

      const baseBody = new THREE.Mesh(new THREE.CylinderGeometry(0.064, 0.072, 0.112, 20), metalMat);
      baseBody.position.y = -0.08;
      g.add(baseBody);

      [-0.038, -0.062, -0.086, -0.11].forEach(y => {
        const ring = new THREE.Mesh(new THREE.TorusGeometry(0.064, 0.008, 10, 26), metalMat);
        ring.rotation.x = Math.PI / 2;
        ring.position.y = y;
        g.add(ring);
      });

      const contact = new THREE.Mesh(new THREE.CylinderGeometry(0.015, 0.018, 0.022, 12), contactMat);
      contact.position.y = -0.145;
      g.add(contact);

      const supportL = new THREE.Mesh(new THREE.CylinderGeometry(0.006, 0.006, 0.06, 8), filamentMat);
      supportL.position.set(-0.022, 0.07, 0);
      supportL.rotation.z = -0.14;
      g.add(supportL);

      const supportR = new THREE.Mesh(new THREE.CylinderGeometry(0.006, 0.006, 0.06, 8), filamentMat);
      supportR.position.set(0.022, 0.07, 0);
      supportR.rotation.z = 0.14;
      g.add(supportR);

      const filamentCurve = new THREE.CatmullRomCurve3([
        new THREE.Vector3(-0.03, 0.102, 0),
        new THREE.Vector3(-0.01, 0.084, 0),
        new THREE.Vector3(0.0, 0.096, 0),
        new THREE.Vector3(0.01, 0.084, 0),
        new THREE.Vector3(0.03, 0.102, 0),
      ]);
      const filament = new THREE.Mesh(new THREE.TubeGeometry(filamentCurve, 18, 0.0065, 8, false), filamentMat);
      g.add(filament);
    }

    g.traverse(node => {
      if (node.isMesh) {
        node.castShadow = true;
        node.receiveShadow = true;
      }
    });
    return g;
  }

  /* Master group */
  G = new THREE.Group();
  scene.add(G);

  /* =============================================================
     CARTOONISH GRASSLAND MOUNTAIN — pointed peaks, dirt trail,
     lush green with hikers. No snow, stylized & beautiful.
     ============================================================= */

  /* ====== BASE GROUND — flat circular grassy plate ====== */
  const ground = new THREE.Mesh(
    new THREE.CylinderGeometry(9.0, 9.2, 0.4, 64), mGrassD);
  ground.position.y = -0.20; ground.receiveShadow = true; G.add(ground);
  /* Rim */
  const gRim = new THREE.Mesh(
    new THREE.TorusGeometry(9.1, 0.18, 12, 64), mDirt);
  gRim.rotation.x = Math.PI / 2; gRim.position.y = 0.0; G.add(gRim);

  /* -- Helper: smooth natural mountain peak — no rings, organic shape -- */
  function mtnPeak(baseR, height, segs, mat) {
    segs = segs || 48;
    const pts = [];
    const N = 80;
    for (let i = 0; i <= N; i++) {
      const t = i / N;
      /* Natural mountain profile: wide base, smooth concave taper to pointed summit */
      /* pow(0.7) gives a natural slope, small sin adds organic surface bumps */
      let r = baseR * Math.pow(1 - t, 0.7) * (1.0 + 0.03 * Math.sin(t * Math.PI * 6));
      /* Slightly wider at very base for natural foot */
      if (t < 0.08) r += baseR * 0.05 * (1 - t / 0.08);
      pts.push(new THREE.Vector2(Math.max(r, 0.002), t * height));
    }
    /* Rounded summit tip */
    pts.push(new THREE.Vector2(0.001, height + 0.02));
    const geo = new THREE.LatheGeometry(pts, segs);
    const m = new THREE.Mesh(geo, mat);
    m.castShadow = true; m.receiveShadow = true;
    return m;
  }

  /* -- Helper: rolling hill — half-ellipsoid blob -- */
  function hill(rx, ry, rz, mat) {
    const geo = new THREE.SphereGeometry(1, 48, 32, 0, Math.PI * 2, 0, Math.PI / 2);
    const m = new THREE.Mesh(geo, mat);
    m.scale.set(rx, ry, rz);
    m.castShadow = true; m.receiveShadow = true;
    return m;
  }

  /* ====== MOUNTAIN PEAKS — smooth natural mountains ====== */
  /* Main peak — tallest, center-back */
  const pk1 = mtnPeak(3.5, 7.0, 48, mGrass);
  pk1.position.set(0.3, 0, -1.5);
  G.add(pk1);

  /* Left peak removed per request */

  /* Right peak — shorter, pulled forward for depth */
  const pk3 = mtnPeak(2.5, 4.2, 36, mGrass);
  pk3.position.set(3.8, 0, 0.5);
  G.add(pk3);

  /* Far back small peak */
  const pk4 = mtnPeak(2.0, 3.5, 28, mGrassD);
  pk4.position.set(-1.5, 0, -3.5);
  G.add(pk4);

  /* Rocky patches near upper slopes — natural detail without rings */
  function slopeRocks(cx, cy, cz, count, spread, size) {
    for (let i = 0; i < count; i++) {
      const a = Math.random() * Math.PI * 2;
      const d = Math.random() * spread;
      const r = new THREE.Mesh(
        new THREE.DodecahedronGeometry(size * (0.4 + Math.random() * 0.6), 0),
        Math.random() > 0.5 ? mRock : mRockD
      );
      r.position.set(cx + Math.cos(a) * d, cy + Math.random() * size * 0.5, cz + Math.sin(a) * d);
      r.rotation.set(Math.random() * Math.PI, Math.random() * Math.PI, 0);
      r.castShadow = true; r.receiveShadow = true;
      G.add(r);
    }
  }
  /* Removed upper-slope loose rocks to avoid any floating artifacts */

  /* -- Rocky ridges near peaks — adds realism -- */
  function rockCluster(x, y, z, s) {
    for (let i = 0; i < 4; i++) {
      const r = new THREE.Mesh(
        new THREE.DodecahedronGeometry(s * (0.3 + Math.random() * 0.4), 0),
        Math.random() > 0.5 ? mRock : mRockD
      );
      r.position.set(
        x + (Math.random() - 0.5) * s * 1.5,
        y + Math.random() * s * 0.3,
        z + (Math.random() - 0.5) * s * 1.5
      );
      r.rotation.set(Math.random() * Math.PI, Math.random() * Math.PI, 0);
      r.castShadow = true; r.receiveShadow = true;
      G.add(r);
    }
  }
  /* Removed decorative rock clusters to ensure no detached/floating rocks */

  /* -- Green rolling hills — flattened so trail stays visible -- */
  const h1 = hill(6.5, 1.4, 5.5, mGrass);
  h1.position.set(0.5, 0, 3.5); G.add(h1);

  const h2 = hill(5.0, 1.2, 4.5, mGrassD);
  h2.position.set(-1.5, 0, 1.0); G.add(h2);

  const h3 = hill(4.0, 1.2, 3.5, mGrass);
  h3.position.set(3.5, 0, 1.5); G.add(h3);

  const h4 = hill(3.5, 1.8, 3.5, mGrassD);
  h4.position.set(-2.8, 0, -0.5); G.add(h4);

  const h5 = hill(4.0, 1.5, 3.8, mGrass);
  h5.position.set(2.5, 0, -0.3); G.add(h5);

  const h6 = hill(3.0, 1.0, 2.5, mGrassD);
  h6.position.set(-4.0, 0, 2.5); G.add(h6);

  const h7 = hill(2.5, 0.8, 2.0, mGrass);
  h7.position.set(5.0, 0, 3.0); G.add(h7);

  /* Terrain height estimation — grounds objects to hill surface */
  function terrainY(x, z) {
    let maxH = 0.05;
    [[0.5,3.5,6.5,1.4,5.5],[-1.5,1.0,5.0,1.2,4.5],[3.5,1.5,4.0,1.2,3.5],
     [-2.8,-0.5,3.5,1.8,3.5],[2.5,-0.3,4.0,1.5,3.8],[-4.0,2.5,3.0,1.0,2.5],
     [5.0,3.0,2.5,0.8,2.0]].forEach(([cx,cz,rx,ry,rz]) => {
      const dx = (x-cx)/rx, dz = (z-cz)/rz, d2 = dx*dx+dz*dz;
      if (d2 < 1) maxH = Math.max(maxH, ry * Math.sqrt(1-d2));
    });
    return maxH;
  }

  /* ====== PROCEDURAL MOUNTAIN TRAIL — contour-safe spiral ====== */
  const trailPts = [];
  const peakCx = 0.3;
  const peakCz = -1.5;
  const peakBaseR = 3.5;
  const peakH = 7.0;

  function peakSurfaceRadiusAtY(y) {
    const ny = Math.max(0, Math.min(1, 1 - (y / peakH)));
    return peakBaseR * Math.pow(ny, 0.7);
  }

  /* Entry approach before spiral wraps around the mountain. */
  const entryPts = [
    new THREE.Vector3(5.05, 0.94, 5.30),
    new THREE.Vector3(4.55, 1.02, 4.52),
    new THREE.Vector3(4.05, 1.08, 3.70),
    new THREE.Vector3(3.55, 1.14, 2.92),
  ];
  entryPts.forEach((p) => trailPts.push(p));

  /* Spiral segment: always projected outside mountain profile. */
  const spiralStart = entryPts[entryPts.length - 1];
  const startAng = Math.atan2(spiralStart.z - peakCz, spiralStart.x - peakCx);
  const turns = 1.52;
  const spiralCount = 52;
  const yStart = 1.18;
  const yEnd = 7.16;

  for (let i = 0; i < spiralCount; i++) {
    const t = i / (spiralCount - 1);
    const ease = t * t * (3 - 2 * t);
    const y = yStart + (yEnd - yStart) * ease;
    const surfaceR = peakSurfaceRadiusAtY(y);
    const clearance = 1.08 + (1 - ease) * 0.18;
    const r = Math.max(surfaceR + clearance, 1.02);
    const ang = startAng + (Math.PI * 2 * turns * ease);
    const x = peakCx + Math.cos(ang) * r;
    const z = peakCz + Math.sin(ang) * r;
    trailPts.push(new THREE.Vector3(x, y, z));
  }

  trailCurve = new THREE.CatmullRomCurve3(trailPts, false, 'centripetal', 0.24);

  /* Curve mesh base: keeps a single clean continuous trail body. */
  const mTrailTube = new THREE.MeshStandardMaterial({
    color: 0x8f836f,
    roughness: 0.95,
    metalness: 0.02
  });
  const trailTube = new THREE.Mesh(
    new THREE.TubeGeometry(trailCurve, 180, 0.18, 6, false),
    mTrailTube
  );
  trailTube.scale.y = 0.34;
  trailTube.position.y += 0.06;
  trailTube.castShadow = true;
  trailTube.receiveShadow = true;
  G.add(trailTube);

  /* Stone slabs: spaced platforms that follow curve direction and slope. */
  const mStepStone = new THREE.MeshStandardMaterial({ color: 0xb7aa94, roughness: 0.93, metalness: 0.03 });
  const slabGeo = new THREE.BoxGeometry(0.78, 0.10, 0.48);
  const lookTarget = new THREE.Vector3();
  const trailNormalUp = new THREE.Vector3(0, 1, 0);
  const slabCount = 92;
  for (let i = 0; i < slabCount; i++) {
    const t = 0.02 + (i / (slabCount - 1)) * 0.95;
    const pt = trailCurve.getPointAt(t);
    const tang = trailCurve.getTangentAt(t).normalize();
    const slab = new THREE.Mesh(slabGeo, mStepStone);
    slab.position.copy(pt);
    slab.position.y += 0.11 + ((i % 2) ? 0.008 : -0.004);
    lookTarget.copy(pt).add(tang);
    slab.lookAt(lookTarget);
    slab.rotateY(Math.PI);
    slab.rotation.z += Math.sin(i * 0.55) * 0.03;
    slab.rotation.x += Math.cos(i * 0.33) * 0.02;
    slab.scale.x = 0.9 + ((i % 5) * 0.03);
    slab.scale.z = 0.92 + (((i + 2) % 4) * 0.02);
    slab.castShadow = true;
    slab.receiveShadow = true;
    G.add(slab);

    /* Tiny side stones keep the low-poly handcrafted path feel. */
    if (i % 6 === 0) {
      const side = new THREE.Vector3().crossVectors(tang, trailNormalUp).normalize();
      for (const sgn of [-1, 1]) {
        const chip = new THREE.Mesh(
          new THREE.DodecahedronGeometry(0.03 + Math.random() * 0.02, 0),
          mRockD
        );
        chip.position.set(
          pt.x + side.x * (0.42 + Math.random() * 0.1) * sgn,
          pt.y + 0.06,
          pt.z + side.z * (0.42 + Math.random() * 0.1) * sgn
        );
        chip.castShadow = true;
        chip.receiveShadow = true;
        G.add(chip);
      }
    }
  }

  /* ====== STAGE POSITIONS + PLATFORMS ====== */
  const platformRadius = 1.05;
  const platformMat = new THREE.MeshStandardMaterial({ color: 0xaba08d, roughness: 0.9, metalness: 0.02 });
  const platformEdgeMat = new THREE.MeshStandardMaterial({ color: 0x8f846f, roughness: 0.95 });
  const checkpointYLift = [0.06, 0.12, 0.04, 0.20, 0.04];
  const summitPos = new THREE.Vector3(0.30, 7.04, -1.50);

  function addCheckpointPlatform(pos, yLift) {
    const plate = new THREE.Mesh(new THREE.CylinderGeometry(platformRadius, platformRadius * 1.08, 0.16, 10), platformMat);
    plate.position.copy(pos);
    plate.position.y += yLift;
    plate.castShadow = true;
    plate.receiveShadow = true;
    G.add(plate);

    const rim = new THREE.Mesh(new THREE.TorusGeometry(platformRadius * 0.92, 0.05, 8, 14), platformEdgeMat);
    rim.position.copy(pos);
    rim.position.y += yLift + 0.09;
    rim.rotation.x = Math.PI / 2;
    rim.receiveShadow = true;
    G.add(rim);
  }

  S.forEach((s, i) => {
    const p = (i === SUMMIT_INDEX)
      ? summitPos.clone()
      : trailCurve.getPointAt(stageTValues[i]).clone();

    if (i === ASCENT_INDEX) {
      /* Keep stage 3 click target clear of nearby mesh by nudging outward. */
      const outward = new THREE.Vector3(p.x - peakCx, 0, p.z - peakCz);
      if (outward.lengthSq() > 1e-6) {
        outward.normalize();
        p.x += outward.x * 0.52;
        p.z += outward.z * 0.52;
      }
      p.y += 0.16;
    }

    s.pos = p;
    addCheckpointPlatform(p, checkpointYLift[i]);
  });

  /* ====== FLAGS — brown poles with red pennants (gold on summit) ====== */
  function mkFlag(pos, stageIdx) {
    const g = new THREE.Group();
    const isAwareness = stageIdx === AWARENESS_INDEX;
    const isSummit = stageIdx === SUMMIT_INDEX;

    if (isAwareness) {
      const markerMat = new THREE.MeshStandardMaterial({ color: 0xd8cfbe, roughness: 0.85, metalness: 0.03 });
      const ring = new THREE.Mesh(new THREE.TorusGeometry(0.28, 0.05, 8, 20), markerMat);
      ring.rotation.x = Math.PI / 2;
      ring.position.y = 0.18;
      ring.castShadow = true;
      ring.receiveShadow = true;
      g.add(ring);

      const disc = new THREE.Mesh(new THREE.CylinderGeometry(0.16, 0.16, 0.06, 20), markerMat);
      disc.position.y = 0.08;
      disc.castShadow = true;
      disc.receiveShadow = true;
      g.add(disc);
    } else {
      const poleH = isSummit ? 1.4 : (stageIdx === BASECAMP_INDEX ? 0.82 : 1.0);
      const flagMat = isSummit ? mGold : mRed;

      const pole = new THREE.Mesh(
        new THREE.CylinderGeometry(0.04, 0.06, poleH, 8), mTrunk);
      pole.position.y = poleH / 2;
      pole.castShadow = true; g.add(pole);

      const ball = new THREE.Mesh(
        new THREE.SphereGeometry(0.06, 10, 8), mTrunk);
      ball.position.y = poleH + 0.03; g.add(ball);

      const fs = new THREE.Shape();
      fs.moveTo(0, 0);
      fs.lineTo(0.42, 0.08);
      fs.lineTo(0, 0.24);
      fs.closePath();
      const fl = new THREE.Mesh(new THREE.ShapeGeometry(fs), flagMat);
      fl.position.set(0.04, poleH - 0.26, 0.015);
      fl.castShadow = true;
      g.add(fl);
      flags.push(fl);
    }

    g.position.copy(pos);
    g.position.y += isAwareness ? 0.06 : 0.14;
    if (!isAwareness) {
      /* Rotate flag group so its face (+Z) points outward from mountain center,
         ensuring it's visible from the zoom camera which approaches from that direction. */
      const fdx = pos.x - MAIN_PEAK_CENTER.x;
      const fdz = pos.z - MAIN_PEAK_CENTER.z;
      if (fdx * fdx + fdz * fdz > 1e-4) {
        g.rotation.y = Math.atan2(fdx, fdz);
      }
    }
    if (isSummit) {
      summitFlagGroup = g;
      summitFlagGroup.visible = false;
      summitFlagPlanted = false;
    }
    G.add(g);

    const hitRadius = stageIdx === ASCENT_INDEX ? 1.55 : (isAwareness ? 0.74 : 0.9);
    const hitLift = stageIdx === ASCENT_INDEX ? 1.78 : (isAwareness ? 0.44 : 0.76);
    const ht = new THREE.Mesh(
      new THREE.SphereGeometry(hitRadius, 10, 8),
      new THREE.MeshBasicMaterial({ visible: false })
    );
    ht.position.copy(pos); ht.position.y += hitLift;
    G.add(ht); pinHits.push(ht);
  }

  S.forEach((s, i) => {
    s.hitIdx = i;
    mkFlag(s.pos, i);
    pinHits[i].userData.idx = i;
  });

  /* ====== TREES — cartoonish pines (no snow) ====== */
  function mkTree(x, y, z, h, variant) {
    const g = new THREE.Group();
    const leafMats = [mLeaf1, mLeaf2, mLeaf3];
    const mat = leafMats[variant % 3];
    const th = h * 0.25;

    /* Trunk */
    const trunk = new THREE.Mesh(
      new THREE.CylinderGeometry(h * 0.04, h * 0.07, th, 6), mTrunk);
    trunk.position.y = th / 2;
    trunk.castShadow = true; g.add(trunk);

    /* 3 foliage layers for fuller look */
    const c1 = new THREE.Mesh(new THREE.ConeGeometry(h * 0.35, h * 0.38, 8), mat);
    c1.position.y = th + h * 0.17;
    c1.castShadow = true; g.add(c1);

    const c2 = new THREE.Mesh(new THREE.ConeGeometry(h * 0.28, h * 0.32, 8), mat);
    c2.position.y = th + h * 0.40;
    c2.castShadow = true; g.add(c2);

    const c3 = new THREE.Mesh(new THREE.ConeGeometry(h * 0.18, h * 0.26, 8), mat);
    c3.position.y = th + h * 0.60;
    c3.castShadow = true; g.add(c3);

    g.position.set(x, terrainY(x, z), z);
    G.add(g);
  }

  /* Dense forest — auto-grounded to terrain surface */
  /* -- BEHIND peaks (tall background forest visible above peaks) -- */
  mkTree(-2.0, 0.10, -5.5, 2.80, 0);
  mkTree( 1.5, 0.10, -5.0, 2.60, 1);
  mkTree(-4.0, 0.10, -4.5, 2.40, 2);
  mkTree( 3.5, 0.10, -4.8, 2.30, 0);
  mkTree( 0.0, 0.10, -6.0, 2.70, 1);
  mkTree(-5.5, 0.10, -3.5, 2.10, 2);
  mkTree( 5.0, 0.10, -3.5, 2.00, 0);
  mkTree(-3.0, 0.10, -5.8, 2.20, 1);
  mkTree( 2.5, 0.10, -5.5, 2.30, 2);
  mkTree(-1.0, 0.10, -6.5, 2.50, 0);
  mkTree( 3.0, 0.10, -6.0, 2.40, 1);
  /* -- Left side — tall silhouette framing -- */
  mkTree(-6.0, 0.10, 0.0, 1.50, 0);
  mkTree(-6.5, 0.10, 1.5, 1.40, 1);
  mkTree(-5.5, 0.10, 2.5, 1.20, 2);
  mkTree(-6.2, 0.10, -1.5, 1.60, 0);
  mkTree(-7.0, 0.10, 3.0, 1.30, 1);
  /* -- Right side — tall silhouette framing -- */
  mkTree( 6.0, 0.10, 0.5, 1.45, 1);
  mkTree( 6.5, 0.10, 1.8, 1.35, 2);
  mkTree( 6.2, 0.10, -1.0, 1.55, 0);
  mkTree( 7.0, 0.10, 3.0, 1.25, 1);
  /* -- Front hills — relocated away from trail corridor -- */
  mkTree(-4.2, 0.10, 4.5, 0.85, 0);
  mkTree( 0.3, 0.10, 6.5, 0.82, 1);
  mkTree(-0.5, 0.10, 6.2, 0.78, 2);
  mkTree( 4.8, 0.10, 3.2, 0.72, 0);
  mkTree(-4.0, 0.10, 3.2, 0.68, 1);
  mkTree(-2.5, 0.10, 6.5, 0.65, 0);
  mkTree( 1.0, 0.10, 7.0, 0.70, 1);
  mkTree( 5.2, 0.10, 3.5, 0.58, 2);
  mkTree(-3.5, 0.10, 5.5, 0.60, 0);
  mkTree(-0.8, 0.10, 7.2, 0.52, 1);
  /* -- Between peaks — mid elevation, away from trail -- */
  mkTree(-3.8, 0.10, 1.2, 0.52, 0);
  mkTree( 2.8, 0.10, 1.2, 0.48, 1);
  mkTree(-4.2, 0.10, 0.5, 0.42, 2);
  mkTree( 4.2, 0.10, 0.5, 0.40, 0);
  /* -- Thinning at higher elevation -- */
  mkTree(-2.5, 0.10, -0.5, 0.38, 0);
  mkTree( 2.5, 0.10, -0.8, 0.35, 1);
  mkTree(-1.0, 0.10, -1.5, 0.32, 2);
  mkTree( 1.5, 0.10, -1.5, 0.30, 0);

  /* ====== GROUND COVER BUSHES — layered vegetation for forest depth ====== */
  function mkBush(x, y, z, s, variant) {
    const leafMats = [mLeaf1, mLeaf2, mLeaf3];
    const mat = leafMats[variant % 3];
    const g = new THREE.Group();
    for (let i = 0; i < 3 + Math.floor(Math.random() * 3); i++) {
      const puff = new THREE.Mesh(
        new THREE.SphereGeometry(s * (0.5 + Math.random() * 0.5), 8, 6),
        mat
      );
      puff.position.set(
        (Math.random() - 0.5) * s * 1.2,
        s * 0.3 + Math.random() * s * 0.2,
        (Math.random() - 0.5) * s * 1.2
      );
      puff.scale.y = 0.6;
      puff.castShadow = true;
      g.add(puff);
    }
    g.position.set(x, terrainY(x, z), z);
    G.add(g);
  }
  /* Bushes along forest floor — relocated away from trail corridor */
  mkBush(-1.5, 0.0, 5.8, 0.25, 0);
  mkBush( 0.8, 0.0, 6.5, 0.22, 1);
  mkBush(-4.5, 0.0, 3.5, 0.28, 2);
  mkBush( 2.5, 0.0, 6.0, 0.24, 0);
  mkBush(-4.5, 0.0, 1.8, 0.20, 1);
  mkBush( 4.5, 0.0, 2.0, 0.22, 2);
  mkBush(-2.5, 0.0, 6.8, 0.18, 0);
  mkBush( 1.5, 0.0, 7.2, 0.20, 1);
  mkBush(-5.0, 0.0, 3.0, 0.22, 2);
  mkBush( 5.0, 0.0, 3.0, 0.20, 0);
  mkBush(-3.0, 0.0, -2.0, 0.26, 1);
  mkBush( 3.0, 0.0, -2.0, 0.24, 2);
  mkBush( 0.0, 0.0, -3.5, 0.22, 0);
  mkBush(-4.0, 0.0, -1.0, 0.20, 1);
  mkBush( 4.0, 0.0, -1.0, 0.18, 2);

  /* ====== FLOWERS — small clusters of colour on the grass ====== */
  function mkFlower(x, y, z, mat) {
    const g = new THREE.Group();
    const stem = new THREE.Mesh(
      new THREE.CylinderGeometry(0.015, 0.015, 0.12, 4), mLeaf2);
    stem.position.y = 0.06; g.add(stem);
    const head = new THREE.Mesh(
      new THREE.SphereGeometry(0.06, 8, 6), mat);
    head.position.y = 0.14; g.add(head);
    g.position.set(x, terrainY(x, z), z);
    G.add(g);
  }
  const flowerMats = [mFlower1, mFlower2, mFlower3];
  const flowerSpots = [
    [-1.5,0.05,3.5], [0.8,0.05,3.8], [-2.8,0.05,3.8], [2.5,0.05,4.2],
    [-0.3,0.05,4.0], [3.5,0.05,4.8], [-3.8,0.05,3.5], [1.8,0.05,4.5],
    [-1.0,0.05,5.0], [0.5,0.05,5.5], [-2.2,0.05,5.2], [3.0,0.05,3.5],
    [-4.2,0.05,2.5], [4.5,0.05,3.8], [-0.8,0.05,2.8], [1.2,0.05,2.8],
    [-3.0,0.05,2.0], [2.0,0.05,1.8], [-1.5,0.05,1.5], [0.0,0.05,2.0],
  ];
  flowerSpots.forEach((p, i) => mkFlower(p[0], p[1], p[2], flowerMats[i % 3]));

  /* ====== FOREST FLOOR — moss patches + ground cover for layered depth ====== */
  const mMoss1 = new THREE.MeshStandardMaterial({ color: 0x2a5520, roughness: 0.95 });
  const mMoss2 = new THREE.MeshStandardMaterial({ color: 0x3a6828, roughness: 0.95 });
  const mGroundCov = new THREE.MeshStandardMaterial({
    color: 0x4a6a35, roughness: 0.9, transparent: true, opacity: 0.65
  });
  function mkMoss(x, z, s) {
    const m = new THREE.Mesh(
      new THREE.SphereGeometry(s, 8, 5, 0, Math.PI*2, 0, Math.PI/2),
      Math.random() > 0.5 ? mMoss1 : mMoss2
    );
    m.position.set(x, terrainY(x, z) + 0.01, z);
    m.scale.y = 0.25;
    m.receiveShadow = true;
    G.add(m);
  }
  function mkGroundPatch(x, z, r) {
    const p = new THREE.Mesh(new THREE.CircleGeometry(r, 12), mGroundCov);
    p.position.set(x, terrainY(x, z) + 0.01, z);
    p.rotation.x = -Math.PI / 2;
    p.receiveShadow = true;
    G.add(p);
  }
  /* Moss mounds near trees and rocks */
  [[-2.8,3.5],[-1.5,4.5],[1.2,3.8],[3.0,4.0],[-3.8,2.5],[2.2,2.5],
   [-0.5,5.0],[4.0,3.5],[-4.5,1.5],[0.5,4.8],[-2.0,2.0],[1.5,1.5],
   [-3.2,4.5],[2.8,3.0],[-0.8,3.2],[1.8,5.2]].forEach(([x,z]) =>
    mkMoss(x, z, 0.12 + Math.random() * 0.18));
  /* Dark ground patches — gives forest floor texture variation */
  [[-1.0,4.0,0.5],[2.0,3.5,0.4],[-3.0,3.0,0.45],[0.0,5.0,0.35],
   [3.5,2.5,0.3],[-4.0,4.0,0.4],[1.5,5.5,0.3],[-2.5,5.0,0.35],
   [-1.5,2.5,0.3],[3.0,1.5,0.25],[0.5,3.0,0.4],[-4.5,3.5,0.3]].forEach(([x,z,r]) =>
    mkGroundPatch(x, z, r));

  /* ====== STARTUP FOUNDERS — stylized professionals on the trail ====== */
  const hikerData = [];
  const mBlazer1  = new THREE.MeshStandardMaterial({ color: 0x1e3a5f, roughness: 0.7 }); /* navy */
  const mBlazer2  = new THREE.MeshStandardMaterial({ color: 0x3a3a3a, roughness: 0.7 }); /* charcoal */
  const mBlazer3  = new THREE.MeshStandardMaterial({ color: 0x5a3a2a, roughness: 0.7 }); /* brown */
  const mWhiteShirt = new THREE.MeshStandardMaterial({ color: 0xf0ede8, roughness: 0.6 });
  const mTie      = new THREE.MeshStandardMaterial({ color: 0xc0392b, roughness: 0.5 }); /* red tie */
  const mBriefcase = new THREE.MeshStandardMaterial({ color: 0x5a3a20, roughness: 0.75 }); /* leather */
  const mSlacks   = new THREE.MeshStandardMaterial({ color: 0x2c2c3a, roughness: 0.8 }); /* dark */
  const mShoe     = new THREE.MeshStandardMaterial({ color: 0x2a2018, roughness: 0.8 });
  const mHair1    = new THREE.MeshStandardMaterial({ color: 0x2a1a0a, roughness: 0.85 });
  const mHair2    = new THREE.MeshStandardMaterial({ color: 0x4a3018, roughness: 0.85 });

  function mkFounder(x, y, z, blazerMat, hairMat, faceDir) {
    const g = new THREE.Group();
    /* Body — blazer */
    const body = new THREE.Mesh(
      new THREE.CapsuleGeometry(0.12, 0.22, 6, 8), blazerMat);
    body.position.y = 0.30; body.castShadow = true; g.add(body);
    /* Shirt collar — visible at neckline */
    const collar = new THREE.Mesh(
      new THREE.CylinderGeometry(0.08, 0.10, 0.04, 8), mWhiteShirt);
    collar.position.y = 0.43; g.add(collar);
    /* Tie */
    const tie = new THREE.Mesh(
      new THREE.BoxGeometry(0.03, 0.12, 0.02), mTie);
    tie.position.set(0, 0.32, 0.10); g.add(tie);
    /* Head */
    const head = new THREE.Mesh(
      new THREE.SphereGeometry(0.10, 10, 8), mSkin);
    head.position.y = 0.55; head.castShadow = true; g.add(head);
    /* Hair — full visible hairstyle */
    const hair = new THREE.Mesh(
      new THREE.SphereGeometry(0.11, 10, 8, 0, Math.PI * 2, 0, Math.PI * 0.6), hairMat);
    hair.position.y = 0.58; hair.scale.set(1.08, 0.85, 1.05); g.add(hair);
    const hairBack = new THREE.Mesh(
      new THREE.SphereGeometry(0.08, 8, 6), hairMat);
    hairBack.position.set(0, 0.60, -0.03); hairBack.scale.set(1.1, 0.5, 0.9); g.add(hairBack);
    /* Legs — slacks */
    const legL = new THREE.Mesh(
      new THREE.CapsuleGeometry(0.04, 0.14, 4, 6), mSlacks);
    legL.position.set(-0.05, 0.10, 0); g.add(legL);
    const legR = new THREE.Mesh(
      new THREE.CapsuleGeometry(0.04, 0.14, 4, 6), mSlacks);
    legR.position.set(0.05, 0.10, 0); g.add(legR);
    /* Shoes */
    const shoeL = new THREE.Mesh(new THREE.BoxGeometry(0.06, 0.03, 0.09), mShoe);
    shoeL.position.set(-0.05, 0.02, 0.01); g.add(shoeL);
    const shoeR = new THREE.Mesh(new THREE.BoxGeometry(0.06, 0.03, 0.09), mShoe);
    shoeR.position.set(0.05, 0.02, 0.01); g.add(shoeR);
    /* Briefcase — carried at side */
    const bc = new THREE.Mesh(
      new THREE.BoxGeometry(0.10, 0.08, 0.03), mBriefcase);
    bc.position.set(0.15, 0.18, 0.04); bc.castShadow = true; g.add(bc);
    /* Briefcase handle */
    const bcH = new THREE.Mesh(
      new THREE.TorusGeometry(0.025, 0.005, 6, 8, Math.PI), mBriefcase);
    bcH.position.set(0.15, 0.23, 0.04); bcH.rotation.z = Math.PI; g.add(bcH);

    g.position.set(x, y + 0.18, z);
    g.rotation.y = faceDir;
    G.add(g);
    hikerData.push({ group: g, legL, legR, baseY: y + 0.18 });
    return g;
  }

  /* Place founders along trail curve — grounded on trail surface */
  const blazerMats = [mBlazer1, mBlazer2, mBlazer3];
  const hairMats = [mHair1, mHair2, mHair1];
  [0.08, 0.20, 0.44, 0.68].forEach((t, i) => {
    const pt = trailCurve.getPoint(t);
    const tang = trailCurve.getTangent(t);
    const faceAngle = Math.atan2(tang.x, tang.z);
    mkFounder(pt.x, pt.y + 0.02, pt.z, blazerMats[i % 3], hairMats[i % 3], faceAngle);
  });

  /* ====== HERO — startup CEO protagonist, larger + detailed ====== */
  const mHeroBlazer = new THREE.MeshStandardMaterial({ color: 0x1a2e4a, roughness: 0.65 }); /* dark navy */
  const mHeroWhite = new THREE.MeshStandardMaterial({ color: 0xf5f0ea, roughness: 0.55 });
  const mHeroTie = new THREE.MeshStandardMaterial({ color: 0xF8AF21, roughness: 0.4 }); /* gold tie */
  const mHeroSlacks = new THREE.MeshStandardMaterial({ color: 0x1e2a38, roughness: 0.75 });
  const mHeroShoe = new THREE.MeshStandardMaterial({ color: 0x2a1810, roughness: 0.8 });
  const mHeroHair = new THREE.MeshStandardMaterial({ color: 0x1a0e05, roughness: 0.85 });
  const mHeroBrief = new THREE.MeshStandardMaterial({ color: 0x6a3a18, roughness: 0.7 }); /* leather */
  const mStick = new THREE.MeshStandardMaterial({ color: 0x8a6a3a, roughness: 0.9 });

  hero = new THREE.Group();
  /* Body — blazer */
  const hBody = new THREE.Mesh(new THREE.CapsuleGeometry(0.18, 0.30, 8, 10), mHeroBlazer);
  hBody.position.y = 0.42; hBody.castShadow = true; hero.add(hBody);
  /* Shirt collar */
  const hCollar = new THREE.Mesh(new THREE.CylinderGeometry(0.12, 0.14, 0.05, 10), mHeroWhite);
  hCollar.position.y = 0.60; hero.add(hCollar);
  /* Tie */
  const hTie = new THREE.Mesh(new THREE.BoxGeometry(0.04, 0.16, 0.02), mHeroTie);
  hTie.position.set(0, 0.44, 0.14); hero.add(hTie);
  /* Head */
  const hHead = new THREE.Mesh(new THREE.SphereGeometry(0.15, 12, 10), mSkin);
  hHead.position.y = 0.78; hHead.castShadow = true; hero.add(hHead);
  hHeadRef = hHead;
  /* Hair — full styled hair, clearly visible */
  const hHairMain = new THREE.Mesh(
    new THREE.SphereGeometry(0.16, 12, 10, 0, Math.PI * 2, 0, Math.PI * 0.6), mHeroHair);
  hHairMain.position.y = 0.82; hHairMain.scale.set(1.08, 0.85, 1.05); hero.add(hHairMain);
  /* Hair side volume */
  const hHairSide = new THREE.Mesh(
    new THREE.SphereGeometry(0.12, 10, 8), mHeroHair);
  hHairSide.position.set(0, 0.84, -0.04); hHairSide.scale.set(1.15, 0.55, 0.9); hero.add(hHairSide);
  /* Hair top tuft for style */
  const hHairTop = new THREE.Mesh(
    new THREE.SphereGeometry(0.08, 8, 6), mHeroHair);
  hHairTop.position.set(0.02, 0.90, 0.04); hHairTop.scale.set(1.2, 0.6, 1.0); hero.add(hHairTop);
  /* Left arm */
  hArmL = new THREE.Mesh(new THREE.CapsuleGeometry(0.05, 0.18, 4, 6), mHeroBlazer);
  hArmL.position.set(-0.22, 0.48, 0); hArmL.rotation.z = 0.25; hero.add(hArmL);
  /* Right arm */
  hArmR = new THREE.Mesh(new THREE.CapsuleGeometry(0.05, 0.18, 4, 6), mHeroBlazer);
  hArmR.position.set(0.22, 0.48, 0); hArmR.rotation.z = -0.25; hero.add(hArmR);
  /* Legs — slacks */
  const hLegL = new THREE.Mesh(new THREE.CapsuleGeometry(0.06, 0.20, 4, 6), mHeroSlacks);
  hLegL.position.set(-0.08, 0.13, 0); hero.add(hLegL);
  const hLegR = new THREE.Mesh(new THREE.CapsuleGeometry(0.06, 0.20, 4, 6), mHeroSlacks);
  hLegR.position.set(0.08, 0.13, 0); hero.add(hLegR);
  /* Dress shoes */
  const hShoeL = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.04, 0.12), mHeroShoe);
  hShoeL.position.set(-0.08, 0.02, 0.02); hero.add(hShoeL);
  const hShoeR = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.04, 0.12), mHeroShoe);
  hShoeR.position.set(0.08, 0.02, 0.02); hero.add(hShoeR);
  /* Briefcase — carried at right side */
  const hBrief = new THREE.Mesh(new THREE.BoxGeometry(0.16, 0.12, 0.04), mHeroBrief);
  hBrief.position.set(0.26, 0.22, 0.06); hBrief.castShadow = true; hero.add(hBrief);
  const hBriefH = new THREE.Mesh(
    new THREE.TorusGeometry(0.035, 0.008, 6, 8, Math.PI), mHeroBrief);
  hBriefH.position.set(0.26, 0.29, 0.06); hBriefH.rotation.z = Math.PI; hero.add(hBriefH);
  /* Walking stick — placeholder, hidden when not used */
  hStick = new THREE.Mesh(new THREE.CylinderGeometry(0.015, 0.02, 0.9, 6), mStick);
  hStick.position.set(0.28, 0.35, 0.08); hStick.rotation.z = -0.15;
  hStick.visible = false; hero.add(hStick);

  /* Business plan — canvas texture with visible content, shown at stage 2 */
  const planCanvas = document.createElement('canvas');
  planCanvas.width = 256; planCanvas.height = 192;
  const ctx = planCanvas.getContext('2d');
  /* Background */
  ctx.fillStyle = '#f5f0e0'; ctx.fillRect(0, 0, 256, 192);
  /* Header */
  ctx.fillStyle = '#1a2e4a'; ctx.font = 'bold 16px sans-serif';
  ctx.fillText('BUSINESS PLAN', 20, 28);
  /* Horizontal rule */
  ctx.strokeStyle = '#F8AF21'; ctx.lineWidth = 2;
  ctx.beginPath(); ctx.moveTo(20, 36); ctx.lineTo(236, 36); ctx.stroke();
  /* Section lines */
  ctx.fillStyle = '#3a4a5a'; ctx.font = '10px sans-serif';
  const lines = ['Market Analysis', 'Target Customers', 'Revenue Model',
    'Growth Strategy', 'Team & Advisors', 'Financial Projections'];
  lines.forEach((l, li) => {
    const yy = 52 + li * 22;
    ctx.fillStyle = '#1a2e4a'; ctx.font = 'bold 9px sans-serif';
    ctx.fillText(l, 20, yy);
    ctx.fillStyle = '#8a8a8a'; ctx.font = '8px sans-serif';
    ctx.fillRect(20, yy + 4, 120 + Math.random() * 80, 1.5);
    ctx.fillRect(20, yy + 9, 80 + Math.random() * 60, 1.5);
  });
  /* Chart icon */
  ctx.strokeStyle = '#F8AF21'; ctx.lineWidth = 2;
  ctx.beginPath(); ctx.moveTo(180, 120); ctx.lineTo(195, 90);
  ctx.lineTo(210, 100); ctx.lineTo(230, 60); ctx.stroke();
  /* Logo placeholder */
  ctx.fillStyle = '#F8AF21'; ctx.beginPath();
  ctx.arc(230, 26, 8, 0, Math.PI * 2); ctx.fill();

  const planTex = new THREE.CanvasTexture(planCanvas);
  const mPlan = new THREE.MeshStandardMaterial({
    map: planTex, roughness: 0.5, side: THREE.DoubleSide
  });
  const hMapGeo = new THREE.PlaneGeometry(0.34, 0.25);
  hMapMesh = new THREE.Mesh(hMapGeo, mPlan);
  hMapMesh.position.set(-0.32, 0.55, 0.18);
  hMapMesh.rotation.set(-0.3, 0.4, 0);
  hMapMesh.visible = false;
  hero.add(hMapMesh);

  /* Mini flag — hidden by default, shown in stage 4 summit */
  heroFlagGroup = new THREE.Group();
  const hfPole = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.025, 0.6, 6), mStick);
  hfPole.position.y = 0.3; heroFlagGroup.add(hfPole);
  const hfShape = new THREE.Shape();
  hfShape.moveTo(0,0); hfShape.lineTo(0.22,0.05); hfShape.lineTo(0,0.14); hfShape.closePath();
  const hfFlag = new THREE.Mesh(new THREE.ShapeGeometry(hfShape), mGold);
  hfFlag.position.set(0.025, 0.42, 0.01); heroFlagGroup.add(hfFlag);
  heroFlagGroup.visible = false;
  heroFlagGroup.position.set(-0.25, 0, 0.15);
  hero.add(heroFlagGroup);

  /* Trailhead decision symbols above hero head (Stage 01 only). */
  heroTrailheadMarks = new THREE.Group();
  const lightBulb = makeTrailheadMark('lightbulb', 0xF8AF21, 0x6f5212);
  lightBulb.position.set(-0.02, 1.22, 0.12);
  lightBulb.userData.baseY = 1.22;
  lightBulb.userData.phase = 0.0;
  lightBulb.userData.floatAmp = 0.016;
  lightBulb.userData.rollAmp = 0.08;
  lightBulb.userData.baseScale = 1.06;
  lightBulb.userData.pulseAmp = 0.04;
  heroTrailheadMarks.add(lightBulb);

  heroTrailheadMarks.visible = false;
  hero.add(heroTrailheadMarks);

  /* Start hero at stage 00 — grounded on trail slab surface */
  const startPoint = trailCurve.getPointAt(stageTValues[AWARENESS_INDEX]);
  hero.position.copy(startPoint);
  hero.position.y += 0.20;
  heroTrailT = stageTValues[AWARENESS_INDEX];
  hero.scale.setScalar(1.3);
  G.add(hero);

  /* Hero animation state — reinitialize (declared at module scope) */
  heroStage = -1;
  heroAnimState = 'idle';
  heroAnimTime = 0;

  /* Stage-specific camera angles — top-side-left perspective along trail */
  stageCams = [
    { dist: 4.7, y: 2.2, side: 0.45, lookUp: 0.24 },
    { dist: 5.2, y: 2.6, side: 0.75, lookUp: 0.28 },
    { dist: 5.9, y: 3.05, side: 0.95, lookUp: 0.34 },
    { dist: 6.6, y: 3.55, side: 1.05, lookUp: 0.46 },
    { dist: 6.1, y: 3.45, side: -1.15, lookUp: 0.52 },
  ];

  /* ====== CLOUDS — puffy cartoonish clouds ====== */
  const cloudMat = new THREE.MeshStandardMaterial({
    color: 0xffffff, roughness: 0.9, transparent: true, opacity: 0.85
  });
  cloudMatRef = cloudMat;
  const clouds = [];
  function mkCloud(x, y, z, s) {
    const g = new THREE.Group();
    const puffs = [
      { x: 0, y: 0, z: 0, r: s },
      { x: s * 0.7, y: s * 0.15, z: 0, r: s * 0.7 },
      { x: -s * 0.65, y: s * 0.1, z: 0, r: s * 0.65 },
      { x: s * 0.3, y: s * 0.35, z: 0, r: s * 0.5 },
      { x: -s * 0.2, y: s * 0.3, z: s * 0.2, r: s * 0.45 },
    ];
    puffs.forEach(p => {
      const m = new THREE.Mesh(new THREE.SphereGeometry(p.r, 12, 10), cloudMat);
      m.position.set(p.x, p.y, p.z);
      m.scale.y = 0.5;
      g.add(m);
    });
    g.position.set(x, y, z);
    G.add(g);
    clouds.push(g);
    return g;
  }
  mkCloud(-5, 9.0, -3, 1.2);
  mkCloud(6, 9.5, -2, 1.0);
  mkCloud(-2, 10, -5, 0.9);
  mkCloud(4, 8.5, 2, 0.8);
  mkCloud(0, 10, -7, 1.1);

  /* ====== LABELS (HTML floating) ====== */
  S.forEach((s, i) => {
    const el = document.createElement('div');
    el.className = 'alt3d-flag-label';
    el.innerHTML = `<span data-i="${i}">${s.id} \u00b7 ${s.n}</span>`;
    lblCont.appendChild(el);
    s.el = el;
    el.querySelector('span').addEventListener('click', () => zoomToFlag(i));
  });

  /* Dots */
  const dotCont = document.getElementById('alt3dDots');
  dotCont.querySelectorAll('.alt3d-dot').forEach(d =>
    d.addEventListener('click', () => zoomToFlag(parseInt(d.dataset.i)))
  );

  /* Click / hover on canvas */
  const ray = new THREE.Raycaster();
  const ms = new THREE.Vector2();

  cv.addEventListener('pointermove', e => {
    ms.x = (e.clientX / window.innerWidth) * 2 - 1;
    ms.y = -(e.clientY / window.innerHeight) * 2 + 1;
    ray.setFromCamera(ms, cam);
    cv.style.cursor = ray.intersectObjects(pinHits).length ? 'pointer' : 'grab';
  });

  cv.addEventListener('click', e => {
    ms.x = (e.clientX / window.innerWidth) * 2 - 1;
    ms.y = -(e.clientY / window.innerHeight) * 2 + 1;
    ray.setFromCamera(ms, cam);
    const h = ray.intersectObjects(pinHits);
    if (h.length) zoomToFlag(h[0].object.userData.idx);
  });

  /* Keyboard */
  document.addEventListener('keydown', e => {
    if (!overlay.classList.contains('active')) return;



    if (e.key === 'Escape') {
      if (overlay.scrollTop > 40) {
        scrollToScene();
        return;
      }
      if (stepModal && !stepModal.hidden) {
        closeStepModal();
        return;
      }
      if (infoModal && !infoModal.hidden) {
        hideMiniInfo();
        return;
      }
      if (document.fullscreenElement && document.exitFullscreen) {
        document.exitFullscreen().catch(() => {});
        return;
      }
      if (zoomed) overviewCamera(); else closeOverlay();
    }
    if (e.key === 'ArrowRight' && zoomed && active < S.length - 1) { hideInfoCard(); setTimeout(() => zoomToFlag(active + 1), 300); }
    if (e.key === 'ArrowLeft' && zoomed && active > 0) { hideInfoCard(); setTimeout(() => zoomToFlag(active - 1), 300); }
  });

  /* Navigation buttons */
  if (btnPrev) btnPrev.addEventListener('click', () => { if (active > 0) { hideInfoCard(); setTimeout(() => zoomToFlag(active - 1), 300); } });
  if (btnNext) btnNext.addEventListener('click', () => {
    if (active < 0) return;
    if (active < S.length - 1) {
      hideInfoCard();
      setTimeout(() => zoomToFlag(active + 1), 300);
    }
  });
  if (btnOvw) btnOvw.addEventListener('click', overviewCamera);

  /* Entrance animation */
  G.scale.setScalar(0);
  G.rotation.y = -0.4;
  gsap.to(G.scale, { x: 1, y: 1, z: 1, duration: 1.4, ease: 'elastic.out(1,0.65)', delay: 0.15 });
  gsap.to(G.rotation, { y: 0, duration: 1.4, ease: 'power3.out', delay: 0.15 });

  /* Resize */
  window.addEventListener('resize', onResize);

  /* ====== RENDER LOOP ====== */
  const ck = new THREE.Clock();

  (function anim() {
    requestAnimationFrame(anim);
    if (!overlay.classList.contains('active')) return;

    const et = ck.getElapsedTime();
    if (!zoomed) G.rotation.y += 0.0012;

    /* Flag flutter */
    flags.forEach((f, i) => {
      f.rotation.y = -0.3 + Math.sin(et * 2.8 + i * 1.4) * 0.15;
    });

    /* Hiker walking animation — legs swing */
    hikerData.forEach((h, i) => {
      const swing = Math.sin(et * 3.0 + i * 2.1) * 0.25;
      h.legL.rotation.x = swing;
      h.legR.rotation.x = -swing;
      h.group.position.y = h.baseY + Math.abs(Math.sin(et * 3.0 + i * 2.1)) * 0.03;
    });

    /* Hero animation based on state */
    heroAnimTime += 0.016;
    if (heroAnimState === 'walking') {
      const ws = Math.sin(et * 5.0) * 0.35;
      hLegL.rotation.x = ws;
      hLegR.rotation.x = -ws;
      hArmL.rotation.x = -ws * 0.6;
      hArmR.rotation.x = ws * 0.6;
      hero.position.y += Math.abs(Math.sin(et * 5.0)) * 0.004;
    } else if (heroAnimState === 'idle') {
      hLegL.rotation.x *= 0.9;
      hLegR.rotation.x *= 0.9;
      hArmL.rotation.x *= 0.9;
      hArmR.rotation.x *= 0.9;
      hArmL.rotation.z += (0.25 - hArmL.rotation.z) * 0.16;
      hArmR.rotation.z += (-0.25 - hArmR.rotation.z) * 0.16;
      /* Gentle breathing */
      hBody.scale.y = 1.0 + Math.sin(et * 1.5) * 0.02;
    } else if (heroAnimState === 'looking') {
      /* Looking at map — head turns side to side */
      hHead.rotation.y = Math.sin(et * 0.8) * 0.5;
      hArmL.rotation.x = -0.8; /* holding map out */
      hArmL.rotation.z = 0.3;
      hBody.scale.y = 1.0 + Math.sin(et * 1.2) * 0.015;
    } else if (heroAnimState === 'climbing') {
      /* Slower, heavier steps */
      const cs = Math.sin(et * 2.0) * 0.2;
      hLegL.rotation.x = cs;
      hLegR.rotation.x = -cs;
      hArmL.rotation.x = -cs * 0.4;
      hArmR.rotation.x = cs * 0.4;
      /* Heavy breathing — bigger body swell */
      hBody.scale.y = 1.0 + Math.sin(et * 2.5) * 0.04;
      hBody.scale.x = 1.0 + Math.sin(et * 2.5) * 0.015;
    }

    if (heroTrailheadMarks && heroTrailheadMarks.visible) {
      hero.updateWorldMatrix(true, false);
      heroTrailheadMarks.children.forEach(mark => {
        const phase = mark.userData.phase || 0;
        const floatAmp = mark.userData.floatAmp || 0.014;
        const rollAmp = mark.userData.rollAmp || 0.06;
        const baseScale = mark.userData.baseScale || 1;
        const pulseAmp = mark.userData.pulseAmp || 0;

        mark.position.y = mark.userData.baseY + Math.sin(et * 2.4 + phase) * floatAmp;
        mark.lookAt(cam.position);
        mark.rotateZ(Math.sin(et * 1.6 + phase) * rollAmp);
        if (pulseAmp > 0) {
          const s = baseScale * (1 + Math.sin(et * 2.1 + phase) * pulseAmp);
          mark.scale.setScalar(s);
        }
      });
    }

    /* Cloud drift */
    clouds.forEach((c, i) => {
      c.position.x += Math.sin(et * 0.15 + i * 1.5) * 0.002;
      c.position.z += Math.cos(et * 0.1 + i * 2.0) * 0.001;
    });

    /* Confetti animation */
    if (confettiParts.length > 0) {
      const cdt = 0.016;
      confettiParts = confettiParts.filter(p => {
        p.vy += p.gravity * cdt;
        p.mesh.position.x += p.vx * cdt;
        p.mesh.position.y += p.vy * cdt;
        p.mesh.position.z += p.vz * cdt;
        p.mesh.rotation.x += p.rotX * cdt;
        p.mesh.rotation.y += p.rotY * cdt;
        p.life -= cdt;
        if (p.life < 0.5) p.mesh.material.opacity = p.life / 0.5;
        return p.life > 0;
      });
      if (confettiParts.length === 0) clearConfetti();
    }

    oc.update();
    if (cam.position.y < 0.85) cam.position.y = 0.85;
    updateLabels();
    if (awarenessBubble && awarenessBubble.classList.contains('is-visible')) {
      positionAwarenessBubble();
    }
    R.render(scene, cam);
  })();
}

/* =============================================================
   CAMERA
   ============================================================= */
const OVP = new THREE.Vector3(10.5, 9.0, 19.5);
const OVT = new THREE.Vector3(0, 2.5, 0);

/* Confetti system for summit celebration */
function spawnConfetti(pos) {
  clearConfetti();
  confettiGroup = new THREE.Group();
  confettiParts = [];
  const colors = [0xe85040, 0xF8AF21, 0x3588d0, 0x4CAF50, 0xff69b4, 0xffffff];
  for (let j = 0; j < 80; j++) {
    const mat = new THREE.MeshBasicMaterial({
      color: colors[j % colors.length], side: THREE.DoubleSide,
      transparent: true, opacity: 1.0
    });
    const m = new THREE.Mesh(
      new THREE.PlaneGeometry(0.06 + Math.random() * 0.06, 0.04 + Math.random() * 0.04), mat);
    m.position.copy(pos);
    m.position.y += 1.0;
    confettiGroup.add(m);
    confettiParts.push({
      mesh: m,
      vx: (Math.random() - 0.5) * 4,
      vy: 3 + Math.random() * 5,
      vz: (Math.random() - 0.5) * 4,
      rotX: (Math.random() - 0.5) * 8,
      rotY: (Math.random() - 0.5) * 6,
      gravity: -5 - Math.random() * 3,
      life: 3.0 + Math.random() * 2.0
    });
  }
  G.add(confettiGroup);
}
function clearConfetti() {
  if (confettiGroup) {
    G.remove(confettiGroup);
    confettiGroup.traverse(c => {
      if (c.geometry) c.geometry.dispose();
      if (c.material) c.material.dispose();
    });
    confettiGroup = null;
  }
  confettiParts = [];
}

function zoomToFlag(i) {
  gsap.killTweensOf(cam.position);
  gsap.killTweensOf(oc.target);
  if (heroWalkTween) {
    heroWalkTween.kill();
    heroWalkTween = null;
  }
  if (hArmR) gsap.killTweensOf(hArmR.rotation);
  if (hArmL) gsap.killTweensOf(hArmL.rotation);
  active = i; zoomed = true;
  overlay.classList.add('zoomed');
  oc.enabled = false;

  const wp = new THREE.Vector3();
  pinHits[i].getWorldPosition(wp);

  /* Keep summit cloud layer subtle so the top checkpoint is not obstructed. */
  setCloudOpacity(i === SUMMIT_INDEX ? 0.22 : 0.85);

  /* Stage-specific camera positioning */
  const sc = stageCams[i];
  const radial = new THREE.Vector3(wp.x - MAIN_PEAK_CENTER.x, 0, wp.z - MAIN_PEAK_CENTER.z);
  if (radial.lengthSq() < 1e-6) radial.set(1, 0, 0);
  radial.normalize();
  const sideVec = new THREE.Vector3().crossVectors(new THREE.Vector3(0, 1, 0), radial).normalize();
  const cp = new THREE.Vector3(
    wp.x + radial.x * sc.dist + sideVec.x * sc.side,
    wp.y + sc.y,
    wp.z + radial.z * sc.dist + sideVec.z * sc.side
  );
  const lk = wp.clone(); lk.y += sc.lookUp;
  const isMobileView = window.matchMedia('(max-width: 768px)').matches;
  if (isMobileView) {
    /* Pull camera back/up on mobile so hero + flag remain visible above the bottom card. */
    cp.x += radial.x * 1.2;
    cp.z += radial.z * 1.2;
    cp.y += 0.55;
    lk.y += 0.35;
  }
  if (i === SUMMIT_INDEX) lk.x += 0.55;

  gsap.to(cam.position, { x: cp.x, y: cp.y, z: cp.z, duration: 1.4, ease: 'power2.inOut' });
  gsap.to(oc.target, { x: lk.x, y: lk.y, z: lk.z, duration: 1.4, ease: 'power2.inOut' });

  /* Move hero to this stage */
  heroStage = i;
  heroAnimTime = 0;
  hMapMesh.visible = false;
  heroFlagGroup.visible = false;
  if (heroTrailheadMarks) heroTrailheadMarks.visible = false;
  hStick.visible = false;
  clearConfetti();

  /* Animate hero walking ALONG the trail curve between stages */
  let startT = Number.isFinite(heroTrailT) ? heroTrailT : stageTValues[AWARENESS_INDEX];
  startT = Math.max(0, Math.min(1, startT));
  const endT = stageTValues[i];
  const walkObj = { t: startT };
  const stopPos = S[i].pos;

  heroAnimState = 'walking';
  heroWalkTween = gsap.to(walkObj, {
    t: endT, duration: 2.0, ease: 'power1.inOut',
    onUpdate() {
      if (!trailCurve) return;
      const pt = trailCurve.getPointAt(walkObj.t);
      hero.position.set(pt.x, pt.y + 0.20, pt.z);
      heroTrailT = walkObj.t;
      /* Face along trail tangent direction */
      const tang = trailCurve.getTangentAt(walkObj.t);
      const dir = endT >= startT ? 1 : -1;
      hero.rotation.y = Math.atan2(tang.x * dir, tang.z * dir);
    },
    onComplete() {
      heroWalkTween = null;
      heroTrailT = endT;
      /* Exact stop at stage flag marker */
      let finalX = stopPos.x;
      let finalY = stopPos.y + 0.20;
      let finalZ = stopPos.z;
      const endTang = trailCurve ? trailCurve.getTangentAt(endT) : null;
      if (i === SUMMIT_INDEX && endTang) {
        /* Summit: stop beside centered flag, not on top of it. */
        const side = new THREE.Vector3().crossVectors(endTang, new THREE.Vector3(0, 1, 0)).normalize();
        finalX -= side.x * 0.42;
        finalZ -= side.z * 0.42;
      }
      hero.position.set(finalX, finalY, finalZ);
      if (i === SUMMIT_INDEX) {
        /* Face hero directly toward camera for the flag-plant reveal shot */
        hero.rotation.y = Math.atan2(cam.position.x - finalX, cam.position.z - finalZ);
        hero.rotation.x = 0;
      } else if (endTang) {
        const dir = endT >= startT ? 1 : -1;
        hero.rotation.y = Math.atan2(endTang.x * dir, endTang.z * dir);
      }

      /* Stage-specific idle animation */
      if (i === AWARENESS_INDEX) {
        heroAnimState = 'idle';
        hero.rotation.x = 0;
        hStick.visible = false;
        heroFlagGroup.visible = false;
        if (hArmR && hArmL) {
          hArmR.rotation.x = 0;
          hArmR.rotation.z = -0.25;
          hArmL.rotation.x = 0;
          hArmL.rotation.z = 0.25;
        }
      } else if (i === TRAILHEAD_INDEX) {
        heroAnimState = 'idle'; // standing at trailhead
        hero.rotation.x = 0;
        hStick.visible = false;
        heroFlagGroup.visible = false;
        if (heroTrailheadMarks) heroTrailheadMarks.visible = true;
        if (hArmR && hArmL) {
          hArmR.rotation.x = 0;
          hArmR.rotation.z = -0.25;
          hArmL.rotation.x = 0;
          hArmL.rotation.z = 0.25;
        }
      } else if (i === BASECAMP_INDEX) {
        heroAnimState = 'looking'; // reading business plan at basecamp
        hero.rotation.x = 0;
        hero.rotation.y = Math.atan2(cam.position.x - finalX, cam.position.z - finalZ);
        hStick.visible = false;
        heroFlagGroup.visible = false;
        if (hArmR) {
          hArmR.rotation.x = 0;
          hArmR.rotation.z = -0.25;
        }
        hMapMesh.visible = true;
      } else if (i === ASCENT_INDEX) {
        heroAnimState = 'climbing'; // tired, climbing
        hero.rotation.x = 0.15; // leaning forward
        heroFlagGroup.visible = false;
        if (hArmR && hArmL) {
          hArmR.rotation.z = -0.25;
          hArmL.rotation.z = 0.25;
        }
      } else if (i === SUMMIT_INDEX) {
        heroAnimState = 'idle';
        hero.rotation.x = 0;
        hStick.visible = false;
        /* Show hero holding flag — raise right arm in plant pose */
        heroFlagGroup.visible = true;
        hArmR.rotation.z = -0.95;
        hArmR.rotation.x = -0.55;
        hArmL.rotation.z = 0.25;
        hArmL.rotation.x = 0;
        if (summitFlagGroup && !summitFlagPlanted) {
          summitFlagGroup.visible = true;
          summitFlagGroup.position.y -= 0.36;
          gsap.to(summitFlagGroup.position, {
            y: summitFlagGroup.position.y + 0.18,
            duration: 0.65,
            ease: 'power2.out'
          });
          summitFlagPlanted = true;
        }
        const baseY = finalY;
        /* Brief plant pose → bring arm down → jump + confetti */
        gsap.timeline()
          .to(hArmR.rotation, { z: -0.25, x: 0, duration: 0.38, ease: 'power2.in' }, 0.25)
          .to(hero.position, { y: baseY + 0.52, duration: 0.26, ease: 'power2.out' }, '+=0.08')
          .to(hero.position, { y: baseY, duration: 0.32, ease: 'bounce.out' });
        spawnConfetti(new THREE.Vector3(finalX, finalY, finalZ));
      }
    }
  });

  document.querySelectorAll('.alt3d-dot').forEach((d, j) => d.classList.toggle('active', j === i));
  if (i === AWARENESS_INDEX) {
    showAwarenessBubble();
  } else {
    hideAwarenessBubble();
  }
  showInfoCard(i);
}

function overviewCamera() {
  gsap.killTweensOf(cam.position);
  gsap.killTweensOf(oc.target);
  zoomed = false; active = -1;
  overlay.classList.remove('zoomed');
  setCloudOpacity(0.85);

  gsap.to(cam.position, { x: OVP.x, y: OVP.y, z: OVP.z, duration: 1.2, ease: 'power2.inOut', onComplete() { oc.enabled = true; } });
  gsap.to(oc.target, { x: OVT.x, y: OVT.y, z: OVT.z, duration: 1.2, ease: 'power2.inOut' });

  /* Reset hero to trailhead */
  heroAnimState = 'idle';
  hMapMesh.visible = false;
  heroFlagGroup.visible = false;
  if (heroTrailheadMarks) heroTrailheadMarks.visible = false;
  hStick.visible = false;
  hero.rotation.x = 0;
  hideAwarenessBubble();
  clearConfetti();
  const startPoint = trailCurve ? trailCurve.getPointAt(stageTValues[AWARENESS_INDEX]) : S[AWARENESS_INDEX].pos.clone();
  gsap.to(hero.position, { x: startPoint.x, y: startPoint.y + 0.20, z: startPoint.z, duration: 1.0, ease: 'power2.inOut' });
  gsap.to(hero.rotation, { y: -0.8, duration: 0.6, ease: 'power2.out' });
  heroTrailT = stageTValues[AWARENESS_INDEX];

  document.querySelectorAll('.alt3d-dot').forEach(d => d.classList.remove('active'));
  if (btnOvw) btnOvw.textContent = '\u00d7 Overview';
  hideInfoCard();
  hideMiniInfo();
  closeStepModal();
}

/* =============================================================
   INFO CARD
   ============================================================= */
const infoCardPosClasses = [
  'alt3d-info-pos-left',
  'alt3d-info-pos-right',
  'alt3d-info-pos-left',
  'alt3d-info-pos-lower-right',
  'alt3d-info-pos-left'
];

function isMobileViewport() {
  return window.matchMedia('(max-width: 768px)').matches;
}

function getCardOffsetByStage(i) {
  if (isMobileViewport()) return 28;
  const cls = infoCardPosClasses[i] || 'alt3d-info-pos-right';
  return cls.includes('left') ? -52 : 52;
}

function applyInfoCardPositionClass(i) {
  infoCard.classList.remove(...infoCardPosClasses);
  infoCard.classList.add(infoCardPosClasses[i] || 'alt3d-info-pos-right');
}

function showInfoCard(i) {
  const s = S[i];
  applyInfoCardPositionClass(i);
  ciNum.textContent   = 'Stage ' + s.id;
  ciName.textContent  = s.n;
  ciPhase.textContent = s.p;
  ciDur.textContent   = s.d;
  const milestones = Array.isArray(s.m)
    ? `<ul class="ci-list">${s.m.map(item => `<li>${item}</li>`).join('')}</ul>`
    : '';
  ciDesc.innerHTML = `
    <p>${s.x}</p>
    ${milestones}
    ${i === SUMMIT_INDEX ? '<p class="ci-impact">Through the ALTITUDE Program, ASOG TBI gives startups a clear pathway from idea to impact.</p>' : ''}
  `;
  btnPrev.disabled = i === 0;
  btnNext.disabled = i === S.length - 1;
  btnOvw.textContent = '\u00d7 Overview';
  const enterOffset = getCardOffsetByStage(i);
  gsap.set(infoCard, { x: enterOffset, y: isMobileViewport() ? 0 : 8, opacity: 0 });
  gsap.to(infoCard, { opacity: 1, x: 0, y: 0, duration: 0.52, delay: 0.42, ease: 'power3.out',
    onStart() { infoCard.classList.add('vis'); } });
}
function hideInfoCard() {
  const leaveOffset = active >= 0 ? getCardOffsetByStage(active) : (isMobileViewport() ? 28 : 52);
  gsap.to(infoCard, { opacity: 0, x: leaveOffset, y: isMobileViewport() ? 0 : 8, duration: 0.24, ease: 'power2.in',
    onComplete() { infoCard.classList.remove('vis'); } });
}

/* =============================================================
   LABELS
   ============================================================= */
const _v = new THREE.Vector3();
function updateLabels() {
  S.forEach((s, i) => {
    if (!s.el) return;
    pinHits[i].getWorldPosition(_v);
    _v.y += 1.0;
    const p = _v.clone().project(cam);
    if (p.z >= 1) { s.el.style.opacity = '0'; return; }
    s.el.style.left = ((p.x * .5 + .5) * window.innerWidth) + 'px';
    s.el.style.top  = ((-p.y * .5 + .5) * window.innerHeight) + 'px';
    s.el.style.transform = 'translate(-50%,-100%)';
    s.el.style.opacity = zoomed ? '0' : '1';
  });
}
/* =============================================================
   RESIZE
   ============================================================= */
function onResize() {
  if (!sceneReady) return;
  cam.aspect = window.innerWidth / window.innerHeight;
  cam.updateProjectionMatrix();
  R.setSize(window.innerWidth, window.innerHeight);
  syncOverlayScrollState();
}
