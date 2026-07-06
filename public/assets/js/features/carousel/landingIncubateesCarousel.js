(function () {
  var reduceMotion = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function updateFocus(root, items, itemCenters, currentOffset) {
    if (!items.length) return;

    if (reduceMotion) {
      items.forEach(function (item) {
        item.style.setProperty('--inc-focus', '1');
      });
      return;
    }

    var center = root.clientWidth / 2;
    var focusRange = Math.max(160, root.clientWidth * 0.32);

    items.forEach(function (item, index) {
      var itemCenter = (itemCenters[index] || 0) - currentOffset;
      var distance = Math.abs(center - itemCenter);
      var normalized = 1 - clamp(distance / focusRange, 0, 1);
      var eased = normalized * normalized * (3 - 2 * normalized);

      item.style.setProperty('--inc-focus', eased.toFixed(3));
    });
  }

  function initCarousel(root) {
    var track = root.querySelector('.inc-track');
    var items = Array.prototype.slice.call(root.querySelectorAll('.inc-logo-item'));
    var frame = 0;
    var isRunning = false;
    var startTime = 0;
    var offset = 0;
    var loopWidth = 0;
    var itemCenters = [];
    var isVisible = false;
    var isPausedByHover = false;

    function measureLoop() {
      loopWidth = track ? Math.max(0, track.scrollWidth / 2) : 0;
      itemCenters = items.map(function (item) {
        return item.offsetLeft + item.offsetWidth / 2;
      });
    }

    function tick(now) {
      if (!isRunning) return;
      now = Number.isFinite(now) ? now : performance.now();
      if (!startTime) {
        startTime = now;
      }

      if (track && loopWidth > 0) {
        var speed = loopWidth / 30;
        offset = ((now - startTime) / 1000 * speed) % loopWidth;
        track.style.transform = 'translate3d(' + -offset + 'px, 0, 0)';
      }

      updateFocus(root, items, itemCenters, offset);
      frame = window.requestAnimationFrame(tick);
    }

    function start() {
      if (isRunning) return;
      isRunning = true;
      startTime = performance.now() - (loopWidth > 0 ? (offset / (loopWidth / 30)) * 1000 : 0);
      tick();
    }

    function stop() {
      isRunning = false;
      if (frame) {
        window.cancelAnimationFrame(frame);
        frame = 0;
      }
    }

    function syncRunningState() {
      if (isVisible && !isPausedByHover) {
        start();
      } else {
        stop();
      }
    }

    if (!items.length) {
      return;
    }

    if (reduceMotion) {
      updateFocus(root, items, itemCenters, offset);
      return;
    }

    if (!track) {
      return;
    }

    root.classList.add('is-enhanced');
    measureLoop();
    updateFocus(root, items, itemCenters, offset);

    if ('IntersectionObserver' in window) {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          isVisible = entry.isIntersecting;
          syncRunningState();
        });
      }, { threshold: 0.05 });

      observer.observe(root);
    } else {
      isVisible = true;
      syncRunningState();
    }

    root.addEventListener('pointerenter', function () {
      isPausedByHover = true;
      syncRunningState();
      updateFocus(root, items, itemCenters, offset);
    });

    root.addEventListener('pointerleave', function () {
      isPausedByHover = false;
      syncRunningState();
    });

    window.addEventListener('resize', function () {
      measureLoop();
      updateFocus(root, items, itemCenters, offset);
    }, { passive: true });
  }

  function init() {
    Array.prototype.slice
      .call(document.querySelectorAll('[data-incubatee-carousel]'))
      .forEach(initCarousel);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
