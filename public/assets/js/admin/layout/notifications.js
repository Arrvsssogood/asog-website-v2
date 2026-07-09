(function () {
  var root = document.querySelector('[data-admin-notifications]');
  if (!root) return;

  var trigger = root.querySelector('[data-admin-notifications-trigger]');
  var menu = root.querySelector('[data-admin-notifications-menu]');
  var list = root.querySelector('.admin-notifications-list');
  var countEl = root.querySelector('[data-admin-notifications-count]');
  var unreadLabel = root.querySelector('[data-admin-notifications-unread-label]');
  var readAllBtn = root.querySelector('[data-admin-notifications-read-all]');

  function refreshRefs() {
    countEl = root.querySelector('[data-admin-notifications-count]');
    unreadLabel = root.querySelector('[data-admin-notifications-unread-label]');
    readAllBtn = root.querySelector('[data-admin-notifications-read-all]');
    list = root.querySelector('.admin-notifications-list');
  }

  function setOpen(isOpen) {
    root.classList.toggle('is-open', isOpen);
    if (trigger) {
      trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
  }

  function setUnreadCount(count) {
    refreshRefs();

    if (unreadLabel) {
      unreadLabel.textContent = count + ' unread';
    }

    if (trigger && count > 0 && !countEl) {
      countEl = document.createElement('span');
      countEl.className = 'admin-notifications-count';
      countEl.setAttribute('data-admin-notifications-count', '');
      trigger.appendChild(countEl);
    }

    if (!countEl) return;

    if (count <= 0) {
      countEl.remove();
      countEl = null;
      return;
    }

    countEl.textContent = count > 9 ? '9+' : String(count);
  }

  function ensureReadAllButton(hasItems) {
    refreshRefs();
    var head = root.querySelector('.admin-notifications-head');
    var readAllUrl = root.getAttribute('data-read-all-url');

    if (!hasItems || !head || !readAllUrl) {
      if (readAllBtn) readAllBtn.remove();
      readAllBtn = null;
      return;
    }

    if (!readAllBtn) {
      readAllBtn = document.createElement('button');
      readAllBtn.type = 'button';
      readAllBtn.setAttribute('data-admin-notifications-read-all', '');
      readAllBtn.textContent = 'Mark all read';
      head.appendChild(readAllBtn);
    }

    readAllBtn.setAttribute('data-read-url', readAllUrl);
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

  function createNotificationItem(notification) {
    var item = document.createElement('a');
    var type = String(notification.type || 'system_update').replace(/[^a-z0-9_-]/gi, '');
    var isRead = !!notification.isRead;

    item.href = notification.link || '#';
    item.className = 'admin-notification-item ' + (isRead ? 'is-read' : 'is-unread');
    item.setAttribute('data-admin-notification-item', '');
    item.setAttribute('data-read-url', notification.readUrl || '');

    var dot = document.createElement('span');
    dot.className = 'admin-notification-dot type-' + type;

    var copy = document.createElement('span');
    copy.className = 'admin-notification-copy';

    var title = document.createElement('strong');
    title.textContent = notification.title || 'Notification';
    copy.appendChild(title);

    if (notification.body) {
      var body = document.createElement('span');
      body.textContent = notification.body;
      copy.appendChild(body);
    }

    if (notification.timeLabel) {
      var time = document.createElement('small');
      time.textContent = notification.timeLabel;
      copy.appendChild(time);
    }

    item.appendChild(dot);
    item.appendChild(copy);

    return item;
  }

  function renderNotifications(payload) {
    refreshRefs();
    if (!list || !payload) return;

    var items = Array.isArray(payload.items) ? payload.items : [];
    list.textContent = '';

    if (!items.length) {
      var empty = document.createElement('div');
      empty.className = 'admin-notifications-empty';
      empty.textContent = 'No notifications yet.';
      list.appendChild(empty);
    } else {
      items.forEach(function (notification) {
        list.appendChild(createNotificationItem(notification || {}));
      });
    }

    ensureReadAllButton(items.length > 0);
    setUnreadCount(Number(payload.unreadCount || 0));
  }

  if (trigger) {
    trigger.addEventListener('click', function () {
      setOpen(!root.classList.contains('is-open'));
    });
  }

  function handleNotificationAction(event) {
    var item = event.target.closest('[data-admin-notification-item]');
    var readAll = event.target.closest('[data-admin-notifications-read-all]');

    if (item && root.contains(item)) {
      var href = item.getAttribute('href');
      var url = item.getAttribute('data-read-url');
      event.preventDefault();
      markItemRead(item);
      sendReadRequest(url).then(function (data) {
        if (data && typeof data.unreadCount === 'number') {
          setUnreadCount(data.unreadCount);
        }
      }).finally(function () {
        setOpen(false);
        if (href) {
          window.location.href = href;
        }
      });
      return;
    }

    if (readAll && root.contains(readAll)) {
      var readAllUrl = readAll.getAttribute('data-read-url');
      event.preventDefault();
      root.querySelectorAll('[data-admin-notification-item]').forEach(markItemRead);
      setUnreadCount(0);
      sendReadRequest(readAllUrl).then(function (data) {
        if (data && typeof data.unreadCount === 'number') {
          setUnreadCount(data.unreadCount);
        }
      });
    }
  }

  if (menu) {
    menu.addEventListener('click', function (event) {
      handleNotificationAction(event);
      event.stopPropagation();
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

  window.AdminNotifications = window.AdminNotifications || {};
  window.AdminNotifications.render = renderNotifications;
})();
