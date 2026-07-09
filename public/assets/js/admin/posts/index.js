(function () {
  'use strict';

  function init(root) {
    var scope = root && root.querySelector ? root : document;
    var controller = new AbortController();
    var signal = controller.signal;
    var isFetchingPage = false;

    function openFeatureOrderModal() {
      var modal = document.querySelector('#featureOrderModal');
      if (!modal) return;
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeFeatureOrderModal() {
      var modal = document.querySelector('#featureOrderModal');
      if (!modal) return;
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    function bindFeatureOrder() {
      var modal = document.querySelector('#featureOrderModal');
      var list = document.querySelector('#featureOrderList');
      if (!modal || !list) return;

      var dragging = null;

      list.addEventListener('dragstart', function (event) {
        var item = event.target.closest('.feature-order-item');
        if (!item) return;
        dragging = item;
        item.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
      }, { signal: signal });

      list.addEventListener('dragend', function () {
        if (dragging) {
          dragging.classList.remove('is-dragging');
        }
        dragging = null;
      }, { signal: signal });

      list.addEventListener('dragover', function (event) {
        if (!dragging) return;
        event.preventDefault();
        var over = event.target.closest('.feature-order-item');
        if (!over || over === dragging) return;

        var rect = over.getBoundingClientRect();
        var before = event.clientY < rect.top + rect.height / 2;
        list.insertBefore(dragging, before ? over : over.nextSibling);
      }, { signal: signal });
    }

    function bindFilters() {
      var form = document.querySelector('#filterForm');
      if (form && form.dataset.bound !== '1') {
        form.dataset.bound = '1';
        form.addEventListener('submit', function (event) {
          event.preventDefault();
          var params = new URLSearchParams(new FormData(form));
          params.delete('page');
          loadPage(form.action + '?' + params.toString());
        }, { signal: signal });
      }

      ['statusFilterSelect', 'categoryFilterSelect'].forEach(function (id) {
        var select = document.getElementById(id);
        if (select && select.dataset.bound !== '1') {
          select.dataset.bound = '1';
          select.addEventListener('change', function () {
            var currentForm = document.querySelector('#filterForm');
            if (currentForm) {
              currentForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            }
          }, { signal: signal });
        }
      });

      if (window.AdminCustomSelect && typeof window.AdminCustomSelect.init === 'function') {
        window.AdminCustomSelect.init(document.querySelector('.posts-admin-filter') || document);
      }
    }

    function ensurePostsStyles(doc) {
      var needed = ['adminPosts.css', 'adminCustomSelect.css'];
      var currentLinks = Array.prototype.slice.call(document.querySelectorAll('link[rel="stylesheet"][href]'));
      var waits = [];

      function waitForLink(link) {
        return new Promise(function (resolve) {
          if (!link) {
            resolve();
            return;
          }

          try {
            if (link.sheet) {
              resolve();
              return;
            }
          } catch (error) {}

          var settled = false;
          function done() {
            if (settled) return;
            settled = true;
            link.removeEventListener('load', done);
            link.removeEventListener('error', done);
            resolve();
          }

          link.addEventListener('load', done);
          link.addEventListener('error', done);
          window.setTimeout(done, 900);
        });
      }

      doc.querySelectorAll('link[rel="stylesheet"][href]').forEach(function (link) {
        var href = link.getAttribute('href') || '';
        var isNeeded = needed.some(function (fileName) {
          return href.indexOf(fileName) !== -1 || link.href.indexOf(fileName) !== -1;
        });

        if (!isNeeded) return;

        var exists = currentLinks.some(function (current) {
          return current.href === link.href || current.getAttribute('href') === href;
        });

        if (exists) {
          waits.push(waitForLink(currentLinks.find(function (current) {
            return current.href === link.href || current.getAttribute('href') === href;
          })));
          return;
        }

        waits.push(new Promise(function (resolve) {
          var settled = false;
          function done() {
            if (settled) return;
            settled = true;
            resolve();
          }

          var clone = link.cloneNode(true);
          clone.onload = done;
          clone.onerror = done;
          document.head.appendChild(clone);
          window.setTimeout(done, 900);
        }));
      });

      return Promise.all(waits);
    }

    function swapNode(doc, selector) {
      var next = doc.querySelector(selector);
      var current = document.querySelector(selector);
      if (next && current) {
        current.parentNode.replaceChild(next, current);
      }
    }

    function waitForRowImages(root) {
      if (!root) return Promise.resolve();
      var images = Array.from(root.querySelectorAll('.posts-tbl tbody img'));
      if (!images.length) return Promise.resolve();

      return Promise.all(images.map(function (img) {
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
          window.setTimeout(done, 1200);
        });
      })).then(function () {});
    }

    function loadPage(url, options) {
      options = options || {};
      if (isFetchingPage) return Promise.resolve();
      isFetchingPage = true;

      var content = document.querySelector('.posts-admin-content');
      var tableBody = document.querySelector('.posts-tbl tbody');
      if (options.contentOnly && tableBody) {
        tableBody.classList.add('is-posts-page-loading');
      } else if (content) {
        content.style.transition = 'opacity 0.15s ease';
        content.style.opacity = '0.5';
        content.style.pointerEvents = 'none';
      }

      return fetch(url, { cache: 'no-store', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (response) { return response.text(); })
        .then(function (htmlText) {
          isFetchingPage = false;
          var parser = new DOMParser();
          var doc = parser.parseFromString(htmlText, 'text/html');

          return ensurePostsStyles(doc).then(function () {
            var nextContent = doc.querySelector('.posts-admin-content');
            var nextTableBody = nextContent ? nextContent.querySelector('.posts-tbl tbody') : null;
            if (options.contentOnly && nextTableBody) {
              nextTableBody.classList.add('is-posts-page-loading');
            }

            if (!options.contentOnly) {
              swapNode(doc, '.posts-admin-toolbar');
              swapNode(doc, '.posts-admin-filter');
              swapNode(doc, '#featureOrderModal');
            }
            swapNode(doc, '.posts-admin-content');

            if (!options.skipHistory) {
              if (window.AdminShell && typeof window.AdminShell.updateHistory === 'function') {
                window.AdminShell.updateHistory(url);
              } else {
                history.pushState(null, '', url);
              }
            }

            if (!options.contentOnly) {
              bindFeatureOrder();
              bindFilters();
            }

            if (options.contentOnly) {
              var swappedContent = document.querySelector('.posts-admin-content');
              var swappedTableBody = swappedContent ? swappedContent.querySelector('.posts-tbl tbody') : null;
              return waitForRowImages(swappedContent).then(function () {
                if (swappedTableBody) {
                  swappedTableBody.classList.remove('is-posts-page-loading');
                }
              });
            }
          });
        })
        .catch(function () {
          isFetchingPage = false;
          if (tableBody) {
            tableBody.classList.remove('is-posts-page-loading');
          }
          if (content) {
            content.style.opacity = '1';
            content.style.pointerEvents = 'auto';
          }
        });
    }

    bindFeatureOrder();
    bindFilters();

    document.addEventListener('click', function (event) {
      if (event.target.closest('#featuredOrderBtn')) {
        event.preventDefault();
        openFeatureOrderModal();
        return;
      }

      if (event.target.closest('#featureOrderModal [data-close-modal="true"]')) {
        event.preventDefault();
        closeFeatureOrderModal();
        return;
      }

      var pageLink = event.target.closest('.posts-admin-content .pag-btn:not(.pag-disabled), .posts-admin-content th.sortable a, .posts-admin-filter .app-btn-clear');
      if (!pageLink) return;
      var href = pageLink.getAttribute('href');
      if (!href || href === '#') return;
      event.preventDefault();
      loadPage(href, { contentOnly: !!pageLink.closest('.tbl-pagination') });
    }, { signal: signal, capture: true });

    document.addEventListener('keydown', function (event) {
      var modal = document.querySelector('#featureOrderModal');
      if (event.key === 'Escape' && modal && modal.classList.contains('is-open')) {
        closeFeatureOrderModal();
      }
    }, { signal: signal });

    return function () {
      document.body.style.overflow = '';
      controller.abort();
    };
  }

  if (window.AdminShell && typeof window.AdminShell.register === 'function') {
    window.AdminShell.register('posts', { init: init });
  } else if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { init(document); });
  } else {
    init(document);
  }
})();
