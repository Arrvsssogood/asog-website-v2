(function () {
  'use strict';

  function init(root) {
    var scope = root && root.querySelector ? root : document;
    var modal = scope.querySelector('#featureOrderModal');
    var openBtn = scope.querySelector('#featuredOrderBtn');
    var list = scope.querySelector('#featureOrderList');
    if (!modal || !openBtn || !list) return null;

    var controller = new AbortController();
    var signal = controller.signal;
    var dragging = null;

    function openModal() {
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    openBtn.addEventListener('click', openModal, { signal: signal });
    modal.addEventListener('click', function (event) {
      if (event.target.matches('[data-close-modal="true"]')) {
        closeModal();
      }
    }, { signal: signal });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) {
        closeModal();
      }
    }, { signal: signal });

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

    return function () {
      closeModal();
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
