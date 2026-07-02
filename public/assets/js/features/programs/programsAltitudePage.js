(function () {
  var root = document.getElementById('altitudeExperienceRoot');
  if (!root) return;

  var landingPage = document.getElementById('altitudeLandingPage');
  var programPage = document.getElementById('altitudeProgramPage');
  var enterBtn = document.getElementById('altitudeEnterProgram');
  var backBtn = document.getElementById('altitudeBackToLanding');
  var readMoreBtn = document.getElementById('altitudeProgramReadMore');
  var programModal = document.getElementById('altitudeProgramModal');
  var programModalClose = document.getElementById('altitudeProgramModalClose');

  var isLoaded = false;
  var isImporting = false;
  var showProgramPage = async function () {
    if (isLoaded) {
      var card = document.getElementById('altitudeExploreCard');
      if (card) card.click();
      return;
    }
    if (isImporting) return;
    isImporting = true;

    if (enterBtn) {
      enterBtn.textContent = 'Loading 3D Experience...';
      enterBtn.disabled = true;
    }

    try {
      var module = await import(window.altitude3DScriptUrl || '/assets/js/altitude/main.js');
      isLoaded = true;
      if (enterBtn) {
        enterBtn.textContent = 'Explore the Program';
        enterBtn.disabled = false;
      }
      module.openWildernessZoom();
    } catch (err) {
      console.error('Failed to load 3D experience:', err);
      if (enterBtn) {
        enterBtn.textContent = 'Failed to load. Click to retry';
        enterBtn.disabled = false;
      }
      isImporting = false;
    }
  };

  var hideProgramModal = function () {
    if (!programModal) return;
    programModal.hidden = true;
    document.body.style.overflow = '';
  };

  var showLandingPage = function () {
    hideProgramModal();
    root.querySelectorAll('[data-stage-toggle]').forEach(function (btn) {
      var panelId = btn.getAttribute('aria-controls');
      var panel = panelId ? document.getElementById(panelId) : null;
      var card = btn.closest('[data-stage-card]');
      btn.setAttribute('aria-expanded', 'false');
      if (panel) panel.hidden = true;
      if (card) card.classList.remove('is-open');
    });

    programPage.classList.remove('is-active');
    programPage.hidden = true;
    landingPage.classList.add('is-active');
  };

  var showProgramModal = function () {
    if (!programModal) return;
    programModal.hidden = false;
    document.body.style.overflow = 'hidden';
  };

  if (enterBtn) enterBtn.addEventListener('click', showProgramPage);
  if (backBtn) backBtn.addEventListener('click', showLandingPage);

  root.querySelectorAll('[data-stage-toggle]').forEach(function (btn) {
    var panelId = btn.getAttribute('aria-controls');
    var panel = panelId ? document.getElementById(panelId) : null;
    var card = btn.closest('[data-stage-card]');
    if (!panel || !card) return;

    btn.addEventListener('click', function () {
      var expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      panel.hidden = expanded;
      card.classList.toggle('is-open', !expanded);
    });
  });

  var revealFromHash = function (hashValue, smooth) {
    var targetHash = (hashValue || '').replace('#', '');
    var validTargets = ['altitude-program', 'altitude-3d', 'trailhead', 'basecamp', 'ascent', 'summit-launch'];
    if (validTargets.indexOf(targetHash) === -1) return;

    // ALTITUDE TOC link should open landing first, then user enters interactive.
    showLandingPage();

    var anchor = document.getElementById('altitude-program');
    if (anchor) {
      anchor.scrollIntoView({
        behavior: smooth ? 'smooth' : 'auto',
        block: 'start'
      });
    }
  };

  if (readMoreBtn) readMoreBtn.addEventListener('click', showProgramModal);
  if (programModalClose) programModalClose.addEventListener('click', hideProgramModal);

  if (programModal) {
    programModal.addEventListener('click', function (event) {
      if (event.target === programModal) hideProgramModal();
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && programModal && !programModal.hidden) {
      hideProgramModal();
    }
  });

  revealFromHash(window.location.hash, false);
  window.addEventListener('hashchange', function () {
    revealFromHash(window.location.hash, true);
  });

  var overlay3d = document.getElementById('alt3dOverlay');
  if (overlay3d && typeof MutationObserver !== 'undefined') {
    var wasActive = overlay3d.classList.contains('active');
    var observer = new MutationObserver(function () {
      var isActive = overlay3d.classList.contains('active');
      if (wasActive && !isActive) {
        showLandingPage();
      }
      wasActive = isActive;
    });

    observer.observe(overlay3d, {
      attributes: true,
      attributeFilter: ['class']
    });
  }
})();
