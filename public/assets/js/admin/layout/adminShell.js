(function () {
  'use strict';

  var registry = {};
  var currentDestroy = null;
  var isLoading = false;
  var initialized = false;
  var currentPage = getMainPage();
  // Only pages with idempotent initializers are enabled; complex drag/review panels stay on full navigation.
  var ajaxEnabledPages = {
    dashboard: true,
    faqs: true,
    posts: true,
    incubatees: true,
    organization: true,
    applications: true,
    messages: true,
    admins: true,
    settings: true
  };
  var statusTimer = null;

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
    if (/(^|\/)admin\/faqs$/.test(path)) return 'faqs';
    if (/(^|\/)admin\/posts$/.test(path)) return 'posts';
    if (/(^|\/)admin\/incubatees$/.test(path)) return 'incubatees';
    if (/(^|\/)admin\/organization$/.test(path)) return 'organization';
    if (/(^|\/)admin\/applications$/.test(path)) return 'applications';
    if (/(^|\/)admin\/messages$/.test(path)) return 'messages';
    if (/(^|\/)admin\/accounts$/.test(path)) return 'admins';
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
    if (/\/(modal|create|edit|callback|logout|download)(?:\/|\?|$)/.test(href)) return false;
    if (/(token=|\/revalidate\/|\/gmail-api\/|\/google\/)/.test(href)) return false;

    var url;
    try {
      url = new URL(href, window.location.href);
    } catch (error) {
      return false;
    }

    if (!isSafeShellUrl(url)) return false;
    return normalizePath(url.pathname) !== normalizePath(window.location.pathname) || url.search !== window.location.search;
  }

  function listen(root, target, eventName, selectorOrHandler, handler, options) {
    var controller = root && root.__adminShellController;
    var listener;

    if (!target || !eventName) {
      return function () {};
    }

    if (typeof selectorOrHandler === 'function') {
      listener = selectorOrHandler;
    } else {
      listener = function (event) {
        var matched = event.target && event.target.closest ? event.target.closest(selectorOrHandler) : null;
        if (!matched || (target !== document && target !== window && !target.contains(matched))) return;
        handler.call(matched, event, matched);
      };
    }

    var finalOptions = options || {};
    if (controller && typeof AbortController !== 'undefined') {
      finalOptions = Object.assign({}, finalOptions, { signal: controller.signal });
    }

    target.addEventListener(eventName, listener, finalOptions);

    return function () {
      target.removeEventListener(eventName, listener, finalOptions);
    };
  }

  function setLoading(isActive) {
    var main = getMain();
    if (!main) return;
    document.body.classList.toggle('is-admin-shell-loading', isActive);
    main.classList.toggle('is-admin-shell-loading', isActive);
    main.setAttribute('aria-busy', isActive ? 'true' : 'false');
  }

  function ensurePageStyles(doc) {
    var nextMain = doc.querySelector('[data-admin-main]');
    if (!nextMain) return Promise.resolve();

    var links = Array.prototype.slice.call(nextMain.querySelectorAll('link[rel="stylesheet"][href]'));
    var hrefs = links.map(function (link) {
      return link.href;
    });
    if (!links.length) {
      document.querySelectorAll('link[data-admin-page-style]').forEach(function (link) {
        link.remove();
      });
      return Promise.resolve();
    }

    links.forEach(function (link) {
      link.remove();
    });

    var waits = hrefs.map(function (href) {
      var existing = Array.prototype.find.call(document.querySelectorAll('link[rel="stylesheet"][href]'), function (candidate) {
        return candidate.href === href;
      });

      if (existing) return Promise.resolve();

      return new Promise(function (resolve) {
        var settled = false;
        function done() {
          if (settled) return;
          settled = true;
          resolve();
        }

        var clone = document.createElement('link');
        clone.rel = 'stylesheet';
        clone.href = href;
        clone.setAttribute('data-admin-page-style', '');
        clone.onload = done;
        clone.onerror = done;
        document.head.appendChild(clone);
        window.setTimeout(done, 1200);
      });
    });

    return Promise.all(waits).then(function () {
      document.querySelectorAll('link[data-admin-page-style]').forEach(function (link) {
        if (hrefs.indexOf(link.href) === -1) {
          link.remove();
        }
      });
    });
  }

  function prepareSkeleton(root) {
    if (!root) return;

    var surfaceSelector = [
      '.pill',
      '.card',
      '.app-card',
      '.settings-card',
      '.settings-health-card',
      '.settings-control-row',
      '.settings-toggle-row',
      '.settings-status-row',
      '.settings-notice',
      '.faq-admin-toolbar',
      '.faq-admin-panel',
      '.faq-admin-item',
      '.faq-admin-empty',
      '.posts-admin-toolbar',
      '.accounts-admin-toolbar',
      '.app-filter-bar',
      '.msg-filter-bar',
      '.tbl-wrap',
      '.posts-tbl',
      '.inbox-wrap',
      '.reader',
      '.msg-row',
      '.post-card',
      '.post-row',
      '.app-row',
      '.line-row',
      '.empty-card',
      '.empty-row',
      '.tbl-pagination',
      '.pagination',
      '.grid-stats > *',
      '.stat-row > *',
      'form:not(.admin-delete-confirm-modal form):not([data-admin-delete-confirm])'
    ].join(',');

    var skipSelector = [
      '[hidden]',
      '[aria-hidden="true"]',
      'script',
      'style',
      'template',
      'link',
      '.toast',
      '.toast-wrap',
      '.admin-modal',
      '.modal',
      '.admin-delete-confirm-modal',
      '.csel-menu',
      '.admin-notifications-menu'
    ].join(',');

    root.classList.add('is-admin-shell-loading');
    root.setAttribute('aria-busy', 'true');

    root.querySelectorAll(surfaceSelector).forEach(function (node) {
      if (!node || node.matches(skipSelector) || node.closest(skipSelector)) return;
      node.setAttribute('data-admin-shell-skeleton-surface', '');
    });

    root.querySelectorAll('h1,h2,h3,h4,p,small,span,strong,label,a,button,input,select,textarea,img,svg,td,th,.btn,.badge,.status-badge,.side-count').forEach(function (node) {
      if (!node || node.matches(skipSelector) || node.closest(skipSelector)) return;
      if (node.closest('[data-admin-shell-skeleton-surface]')) {
        node.setAttribute('data-admin-shell-skeleton-item', '');
      }
    });
  }

  function runDestroy() {
    var main = getMain();
    if (main && main.__adminShellController) {
      main.__adminShellController.abort();
      main.__adminShellController = null;
    }

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
    if (root && typeof AbortController !== 'undefined') {
      root.__adminShellController = new AbortController();
    }
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

  function clearSkeleton(root) {
    if (!root) return;
    root.querySelectorAll('[data-admin-shell-skeleton-surface],[data-admin-shell-skeleton-item]').forEach(function (node) {
      node.removeAttribute('data-admin-shell-skeleton-surface');
      node.removeAttribute('data-admin-shell-skeleton-item');
    });
  }

  function waitForVisibleImages(root) {
    if (!root) return Promise.resolve();

    var images = Array.prototype.slice.call(root.querySelectorAll('img')).filter(function (img) {
      var rect = img.getBoundingClientRect();
      return rect.bottom >= -160 && rect.top <= window.innerHeight + 220;
    });

    if (!images.length) return Promise.resolve();

    var waits = images.map(function (img) {
      if (img.complete && img.naturalWidth > 0) return Promise.resolve();

      return new Promise(function (resolve) {
        var settled = false;
        function done() {
          if (settled) return;
          settled = true;
          img.removeEventListener('load', done);
          img.removeEventListener('error', done);
          resolve();
        }

        img.addEventListener('load', done, { once: true });
        img.addEventListener('error', done, { once: true });
        window.setTimeout(done, 900);
      });
    });

    return Promise.all(waits).then(function () {});
  }

  function finishSkeleton(root) {
    return new Promise(function (resolve) {
      window.setTimeout(function () {
        document.body.classList.remove('is-admin-shell-loading');
        if (root) {
          root.classList.remove('is-admin-shell-loading');
          root.setAttribute('aria-busy', 'false');
          clearSkeleton(root);
          root.classList.add('is-admin-shell-revealing');
        }

        window.setTimeout(function () {
          if (root) {
            root.classList.remove('is-admin-shell-revealing');
          }
          resolve();
        }, 220);
      }, 170);
    });
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

    nextMain.classList.add('is-admin-shell-entering');
    syncTopbar(doc);
    syncNavigation(doc);
    runInit(getMainPage(nextMain), nextMain);
    focusMain(nextMain);
    requestAnimationFrame(function () {
      nextMain.classList.remove('is-admin-shell-entering');
    });

    return nextMain;
  }

  function fallback(url) {
    window.location.href = url;
  }

  function updateHistory(url, replace) {
    if (!url) return;
    if (replace) {
      history.replaceState({ adminShell: true }, '', url);
    } else {
      history.pushState({ adminShell: true }, '', url);
    }
  }

  function load(url, options) {
    options = options || {};
    if (isLoading) return Promise.resolve(false);
    isLoading = true;
    setLoading(true);
    var swappedMain = null;

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
        return ensurePageStyles(doc).then(function () {
          var nextMain = doc.querySelector('[data-admin-main]');
          prepareSkeleton(nextMain);
          swappedMain = replaceMain(doc);

          if (!options.replaceHistory) {
            updateHistory(url, false);
          } else {
            updateHistory(url, true);
          }

          return waitForVisibleImages(swappedMain)
            .then(function () {
              return finishSkeleton(swappedMain);
            })
            .then(function () {
              return true;
            });
        });
      })
      .catch(function (error) {
        console.warn('[AdminShell] Falling back to full navigation.', error);
        fallback(url);
        return false;
      })
      .finally(function () {
        isLoading = false;
        if (!swappedMain) {
          setLoading(false);
        }
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

  function setBadge(selector, count) {
    var link = document.querySelector(selector);
    if (!link) return;

    var badge = link.querySelector('[data-admin-sidebar-count]');
    if (count <= 0) {
      if (badge) badge.remove();
      return;
    }

    if (!badge) {
      badge = document.createElement('span');
      badge.setAttribute('data-admin-sidebar-count', '');
      badge.className = 'side-count';
      link.appendChild(badge);
    }

    badge.textContent = count > 9 ? '9+' : String(count);
  }

  function syncAllowedNavigation(allowed) {
    if (!Array.isArray(allowed) || !allowed.length) return;
    var allowedSet = {};
    allowed.forEach(function (key) {
      allowedSet[key] = true;
    });

    document.querySelectorAll('[data-admin-nav-key]').forEach(function (link) {
      var key = link.getAttribute('data-admin-nav-key');
      link.hidden = !!key && !allowedSet[key];
    });

    if (currentPage && !allowedSet[currentPage]) {
      var dashboardLink = document.querySelector('[data-admin-nav-key="dashboard"]');
      load(dashboardLink ? dashboardLink.href : '/admin');
    }
  }

  function refreshSidebarStatus() {
    var statusUrl = document.body.getAttribute('data-admin-sidebar-status-url') || '/admin/sidebar/status';
    var loginUrl = document.body.getAttribute('data-admin-login-url') || '/asog-admin';
    return fetch(statusUrl, {
      method: 'GET',
      cache: 'no-store',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (response) {
        if (response.status === 401 || response.status === 403) {
          window.location.href = loginUrl;
          return null;
        }
        if (!response.ok) return null;
        return response.json();
      })
      .then(function (data) {
        if (!data || data.ok === false) return;

        var nameNode = document.querySelector('[data-admin-sidebar-name]');
        var emailNode = document.querySelector('[data-admin-sidebar-email]');
        var roleNode = document.querySelector('[data-admin-sidebar-role]');
        if (nameNode) nameNode.textContent = data.user && data.user.name ? data.user.name : 'Admin';
        if (emailNode) emailNode.textContent = data.user && data.user.email ? data.user.email : '';
        if (roleNode) {
          roleNode.textContent = data.user && data.user.roleLabel ? data.user.roleLabel : 'User';
          roleNode.className = 'side-role-label side-role-label--' + (data.user && data.user.role ? data.user.role : 'user');
        }

        setBadge('[data-admin-nav-key="messages"]', data.counts ? Number(data.counts.unreadMessages || 0) : 0);
        var notificationCount = data.counts ? Number(data.counts.unreadNotifications || 0) : 0;
        var notificationBadge = document.querySelector('[data-admin-notifications-count]');
        var notificationTrigger = document.querySelector('[data-admin-notifications-trigger]');
        if (notificationTrigger && notificationCount > 0 && !notificationBadge) {
          notificationBadge = document.createElement('span');
          notificationBadge.className = 'admin-notifications-count';
          notificationBadge.setAttribute('data-admin-notifications-count', '');
          notificationTrigger.appendChild(notificationBadge);
        }
        if (notificationBadge) {
          if (notificationCount <= 0) {
            notificationBadge.remove();
          } else {
            notificationBadge.textContent = notificationCount > 9 ? '9+' : String(notificationCount);
          }
        }
        var unreadLabel = document.querySelector('[data-admin-notifications-unread-label]');
        if (unreadLabel) unreadLabel.textContent = notificationCount + ' unread';
        if (data.nav && data.nav.allowed) {
          syncAllowedNavigation(data.nav.allowed);
        }
      })
      .catch(function () {});
  }

  function startSidebarPolling() {
    refreshSidebarStatus();
    statusTimer = window.setInterval(refreshSidebarStatus, 60000);
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) refreshSidebarStatus();
    });
  }

  function init() {
    if (initialized) return;
    initialized = true;
    currentPage = getMainPage();
    history.replaceState({ adminShell: true }, '', window.location.href);
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('popstate', onPopState);
    runInit(currentPage, getMain());
    startSidebarPolling();
  }

  window.AdminShell = window.AdminShell || {};
  window.AdminShell.register = register;
  window.AdminShell.load = load;
  window.AdminShell.on = listen;
  window.AdminShell.updateHistory = updateHistory;
  window.AdminShell.refresh = function () {
    return load(window.location.href, { replaceHistory: true });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
