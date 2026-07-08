(function () {
  'use strict';

  var registry = {};
  var currentDestroy = null;
  var isLoading = false;
  var initialized = false;
  var currentPage = getMainPage();
  // Phase 1 only enables pages with idempotent behavior; complex panels opt in after their scripts are migrated.
  var ajaxEnabledPages = {
    dashboard: true,
    settings: true
  };

  function getMain() {
    return document.querySelector('[data-admin-main]');
  }

  function getMainPage(root) {
    var main = root || getMain();
    return main ? (main.getAttribute('data-admin-page') || '') : '';
  }

  function normalizePath(pathname) {
    return String(pathname || '').replace(/\/+$/, '') || '/';
  }

  function pageFromUrl(url) {
    var path = normalizePath(url.pathname);
    if (/(^|\/)admin\/settings$/.test(path)) return 'settings';
    if (/(^|\/)admin$/.test(path)) return 'dashboard';
    return '';
  }

  function isSafeShellUrl(url) {
    if (url.origin !== window.location.origin) return false;
    return !!ajaxEnabledPages[pageFromUrl(url)];
  }

  function shouldInterceptLink(event, link) {
    if (!link || event.defaultPrevented) return false;
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
    if (link.target && link.target !== '_self') return false;
    if (link.hasAttribute('download') || link.getAttribute('data-admin-shell') === 'off') return false;

    var href = link.getAttribute('href') || '';
    if (!href || href.charAt(0) === '#') return false;

    var url;
    try {
      url = new URL(href, window.location.href);
    } catch (error) {
      return false;
    }

    if (!isSafeShellUrl(url)) return false;
    return normalizePath(url.pathname) !== normalizePath(window.location.pathname) || url.search !== window.location.search;
  }

  function setLoading(isActive) {
    var main = getMain();
    if (!main) return;
    main.classList.toggle('is-admin-shell-loading', isActive);
    main.setAttribute('aria-busy', isActive ? 'true' : 'false');
  }

  function runDestroy() {
    if (typeof currentDestroy !== 'function') return;
    try {
      currentDestroy();
    } catch (error) {
      console.warn('[AdminShell] Page cleanup failed.', error);
    }
    currentDestroy = null;
  }

  function runInit(page, root) {
    currentPage = page || getMainPage(root);
    var entry = registry[currentPage];
    if (!entry || typeof entry.init !== 'function') {
      currentDestroy = null;
      return;
    }

    try {
      var result = entry.init(root || getMain());
      currentDestroy = typeof result === 'function'
        ? result
        : (typeof entry.destroy === 'function' ? entry.destroy : null);
    } catch (error) {
      console.warn('[AdminShell] Page init failed.', error);
      currentDestroy = null;
    }
  }

  function syncTopbar(doc) {
    var nextTitle = doc.querySelector('title');
    if (nextTitle) {
      document.title = nextTitle.textContent;
    }

    var nextHeading = doc.querySelector('.bar h1');
    var currentHeading = document.querySelector('.bar h1');
    if (nextHeading && currentHeading) {
      currentHeading.innerHTML = nextHeading.innerHTML;
    }

    var nextDate = doc.querySelector('.bar-date');
    var currentDate = document.querySelector('.bar-date');
    if (nextDate && currentDate) {
      currentDate.textContent = nextDate.textContent;
    }
  }

  function syncNavigation(doc) {
    var currentLinks = document.querySelectorAll('.side-nav a[href]');
    currentLinks.forEach(function (link) {
      var currentHref = link.href;
      var nextLink = Array.prototype.find.call(doc.querySelectorAll('.side-nav a[href]'), function (candidate) {
        return candidate.href === currentHref;
      });

      if (!nextLink) {
        link.classList.remove('on');
        return;
      }

      link.classList.toggle('on', nextLink.classList.contains('on'));
    });
  }

  function focusMain(main) {
    if (!main) return;
    window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
    main.focus({ preventScroll: true });
  }

  function replaceMain(doc) {
    var nextMain = doc.querySelector('[data-admin-main]');
    var currentMain = getMain();
    if (!nextMain || !currentMain) {
      throw new Error('Missing admin main region.');
    }

    runDestroy();
    currentMain.replaceWith(nextMain);

    if (window.AdminCustomSelect && typeof window.AdminCustomSelect.init === 'function') {
      window.AdminCustomSelect.init(nextMain);
    }

    syncTopbar(doc);
    syncNavigation(doc);
    runInit(getMainPage(nextMain), nextMain);
    focusMain(nextMain);
  }

  function fallback(url) {
    window.location.href = url;
  }

  function load(url, options) {
    options = options || {};
    if (isLoading) return Promise.resolve(false);
    isLoading = true;
    setLoading(true);

    return fetch(url, {
      method: 'GET',
      cache: 'no-store',
      credentials: 'same-origin',
      headers: {
        'Accept': 'text/html',
        'X-Requested-With': 'XMLHttpRequest',
        'X-Admin-Shell': '1'
      }
    })
      .then(function (response) {
        var responseUrl = new URL(response.url, window.location.href);
        if (!response.ok || (response.redirected && !isSafeShellUrl(responseUrl))) {
          throw new Error('Admin shell request failed.');
        }
        return response.text();
      })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        replaceMain(doc);

        if (!options.replaceHistory) {
          history.pushState({ adminShell: true }, '', url);
        } else {
          history.replaceState({ adminShell: true }, '', url);
        }

        return true;
      })
      .catch(function (error) {
        console.warn('[AdminShell] Falling back to full navigation.', error);
        fallback(url);
        return false;
      })
      .finally(function () {
        isLoading = false;
        setLoading(false);
      });
  }

  function onDocumentClick(event) {
    var link = event.target.closest && event.target.closest('a[href]');
    if (!shouldInterceptLink(event, link)) return;
    event.preventDefault();
    load(link.href);
  }

  function onPopState() {
    var url = window.location.href;
    var parsed = new URL(url);
    if (!isSafeShellUrl(parsed)) {
      fallback(url);
      return;
    }
    load(url, { replaceHistory: true });
  }

  function register(page, entry) {
    if (!page || !entry) return;
    registry[page] = entry;
    if (initialized && page === currentPage) {
      runDestroy();
      runInit(page, getMain());
    }
  }

  function init() {
    if (initialized) return;
    initialized = true;
    currentPage = getMainPage();
    history.replaceState({ adminShell: true }, '', window.location.href);
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('popstate', onPopState);
    runInit(currentPage, getMain());
  }

  window.AdminShell = window.AdminShell || {};
  window.AdminShell.register = register;
  window.AdminShell.load = load;
  window.AdminShell.refresh = function () {
    return load(window.location.href, { replaceHistory: true });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
