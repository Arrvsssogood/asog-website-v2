(function () {
  var root = document.querySelector('[data-admin-notifications]');
  if (!root) return;

  var trigger = root.querySelector('[data-admin-notifications-trigger]');
  var menu = root.querySelector('[data-admin-notifications-menu]');
  var countEl = root.querySelector('[data-admin-notifications-count]');
  var unreadLabel = root.querySelector('[data-admin-notifications-unread-label]');
  var readAllBtn = root.querySelector('[data-admin-notifications-read-all]');

  function setOpen(isOpen) {
    root.classList.toggle('is-open', isOpen);
    if (trigger) {
      trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
  }

  function setUnreadCount(count) {
    if (unreadLabel) {
      unreadLabel.textContent = count + ' unread';
    }

    if (!countEl) return;

    if (count <= 0) {
      countEl.remove();
      countEl = null;
      return;
    }

    countEl.textContent = count > 9 ? '9+' : String(count);
  }

  function markItemRead(item) {
    if (!item || item.classList.contains('is-read')) return;

    item.classList.remove('is-unread');
    item.classList.add('is-read');
  }

  function sendReadRequest(url) {
    if (!url) return Promise.resolve(null);

    return fetch(url, {
      method: 'PUT',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    }).then(function (response) {
      if (!response.ok) return null;
      return response.json();
    }).catch(function () {
      return null;
    });
  }

  if (trigger) {
    trigger.addEventListener('click', function () {
      setOpen(!root.classList.contains('is-open'));
    });
  }

  if (menu) {
    menu.addEventListener('click', function (event) {
      event.stopPropagation();
    });
  }

  root.querySelectorAll('[data-admin-notification-item]').forEach(function (item) {
    item.addEventListener('click', function (event) {
      var href = item.getAttribute('href');
      var url = item.getAttribute('data-read-url');
      event.preventDefault();
      markItemRead(item);
      sendReadRequest(url).then(function (data) {
        if (data && typeof data.unreadCount === 'number') {
          setUnreadCount(data.unreadCount);
        }
      }).finally(function () {
        if (href) {
          window.location.href = href;
        }
      });
    });
  });

  if (readAllBtn) {
    readAllBtn.addEventListener('click', function () {
      var url = readAllBtn.getAttribute('data-read-url');
      root.querySelectorAll('[data-admin-notification-item]').forEach(markItemRead);
      setUnreadCount(0);
      sendReadRequest(url);
    });
  }

  document.addEventListener('click', function (event) {
    if (!root.contains(event.target)) {
      setOpen(false);
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      setOpen(false);
    }
  });
})();
