(function () {
  var tabs = document.querySelectorAll('.ib-tab');
  var cards = document.querySelectorAll('#ibStack .ib-card');
  var stack = document.getElementById('ibStack');
  var coming = document.getElementById('ibComingSoon');
  var csLabel = document.getElementById('ibCSLabel');
  var countLabel = document.getElementById('ibCountLabel');

  if (!tabs.length) return;

  function getTargetCardFromHash() {
    var hash = window.location.hash;
    if (!hash || hash.length < 2) return null;

    try {
      return document.getElementById(decodeURIComponent(hash.slice(1)));
    } catch (error) {
      return document.getElementById(hash.slice(1));
    }
  }

  function revealCardFromHash() {
    var target = getTargetCardFromHash();
    if (!target) return;

    if (window.history && window.history.replaceState) {
      window.history.replaceState(null, '', window.location.pathname + window.location.search);
    }

    var cohort = target.dataset.cohort;
    if (!cohort) return;

    var activeTab = null;
    tabs.forEach(function (tab) {
      if (!activeTab && tab.dataset.cohort === cohort) {
        activeTab = tab;
      }
    });

    if (activeTab) {
      activeTab.click();
    }

    function openWhenVisible(attempt) {
      if (!window.__ibShowcaseReady) {
        function onReady() {
           window.removeEventListener('ib:ready', onReady);
           openWhenVisible(attempt);
        }
        window.addEventListener('ib:ready', onReady);
        return;
      }

      var hidden = target.offsetParent === null || window.getComputedStyle(target).display === 'none';
      if (hidden) {
        if (attempt < 30) {
          window.requestAnimationFrame(function () {
            openWhenVisible(attempt + 1);
          });
        }
        return;
      }

      window.requestAnimationFrame(function () {
        target.click();
      });
    }

    openWhenVisible(0);
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (item) {
        item.classList.remove('is-active');
      });
      tab.classList.add('is-active');

      var cohort = tab.dataset.cohort;
      var visible = 0;

      cards.forEach(function (card) {
        if (card.dataset.cohort === cohort) {
          card.style.display = '';
          visible++;
        } else {
          card.style.display = 'none';
        }
      });

      if (visible === 0) {
        if (stack) stack.style.display = 'none';
        if (coming) coming.style.display = '';
        if (csLabel) csLabel.textContent = cohort;
        if (countLabel) countLabel.textContent = 'Interested in joining ASOG TBI?';
        return;
      }

      if (stack) stack.style.display = '';
      if (coming) coming.style.display = 'none';
      if (countLabel) {
        countLabel.textContent =
          visible +
          ' incubatee' +
          (visible !== 1 ? 's' : '') +
          ' in ' +
          cohort;
      }

      if (typeof gsap !== 'undefined') {
        gsap.from('#ibStack .ib-card:not([style*="display: none"])', {
          opacity: 0,
          y: 25,
          scale: 0.94,
          duration: 0.35,
          stagger: 0.05,
          ease: 'power2.out'
        });
      }
    });
  });

  revealCardFromHash();
  window.addEventListener('hashchange', revealCardFromHash);
})();
