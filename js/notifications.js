(function () {
  const menu = document.getElementById('notificationsMenu');
  if (!menu) return;

  const apiUrl = document.body.dataset.notificationsApi;
  const csrfToken = document.body.dataset.csrfToken || '';
  if (!apiUrl) return;

  const toggle = document.getElementById('notificationsToggle');
  const panel = document.getElementById('notificationsPanel');
  const listEl = document.getElementById('notificationsList');
  const emptyEl = document.getElementById('notificationsEmpty');
  const badge = document.getElementById('notificationsBadge');
  const markAllBtn = document.getElementById('notificationsMarkAll');
  const toast = document.getElementById('notificationToast');
  const toastTitle = document.getElementById('notificationToastTitle');
  const toastMessage = document.getElementById('notificationToastMessage');

  const POLL_MS = 12000;
  let latestId = 0;
  let pollTimer = null;
  let toastTimer = null;

  function setBadge(count) {
    if (!badge) return;
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : String(count);
      badge.classList.remove('hidden');
    } else {
      badge.classList.add('hidden');
    }
  }

  function formatTime(iso) {
    if (!iso) return '';
    const d = new Date(iso.replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleString(undefined, {
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
    });
  }

  function renderList(items) {
    if (!listEl || !emptyEl) return;
    listEl.innerHTML = '';
    if (!items || items.length === 0) {
      emptyEl.classList.remove('hidden');
      return;
    }
    emptyEl.classList.add('hidden');
    items.forEach(function (item) {
      const li = document.createElement('li');
      li.className = 'notification-item' + (item.isRead ? '' : ' is-unread');
      li.dataset.id = String(item.id);
      li.innerHTML =
        '<div class="notification-item-title"></div>' +
        '<div class="notification-item-message"></div>' +
        '<div class="notification-item-time"></div>';
      li.querySelector('.notification-item-title').textContent = item.title;
      li.querySelector('.notification-item-message').textContent = item.message;
      li.querySelector('.notification-item-time').textContent = formatTime(item.createdAt);
      li.addEventListener('click', function () {
        markRead(item.id, li);
      });
      listEl.appendChild(li);
    });
  }

  function showToast(title, message) {
    if (!toast || !toastTitle || !toastMessage) return;
    toastTitle.textContent = title;
    toastMessage.textContent = message;
    toast.classList.remove('hidden');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () {
      toast.classList.add('hidden');
    }, 6000);
  }

  function apiGet(url) {
    return fetch(url, { credentials: 'same-origin' }).then(function (r) {
      return r.json();
    });
  }

  function apiPost(action, data) {
    return fetch(apiUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
      },
      body: JSON.stringify(Object.assign({ action: action, csrf_token: csrfToken }, data || {})),
    }).then(function (r) {
      return r.json();
    });
  }

  function loadList() {
    return apiGet(apiUrl + '?action=list').then(function (data) {
      if (data.status !== 'success') return;
      renderList(data.notifications || []);
      setBadge(data.unreadCount || 0);
      if (data.notifications && data.notifications.length) {
        latestId = Math.max.apply(
          null,
          data.notifications.map(function (n) {
            return n.id;
          })
        );
      }
    });
  }

  function poll() {
    apiGet(apiUrl + '?action=poll&since_id=' + encodeURIComponent(String(latestId)))
      .then(function (data) {
        if (data.status !== 'success') return;
        setBadge(data.unreadCount || 0);
        if (typeof data.latestId === 'number' && data.latestId > latestId) {
          latestId = data.latestId;
        }
        const incoming = data.new || [];
        if (incoming.length === 0) return;
        incoming.forEach(function (n) {
          showToast(n.title, n.message);
        });
        if (panel && !panel.classList.contains('hidden')) {
          loadList();
        }
      })
      .catch(function () {});
  }

  function markRead(id, rowEl) {
    apiPost('mark_read', { id: id }).then(function (data) {
      if (data.status === 'success') {
        if (rowEl) {
          rowEl.classList.remove('is-unread');
        }
        setBadge(data.unreadCount || 0);
      }
    });
  }

  if (markAllBtn) {
    markAllBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      apiPost('mark_all_read').then(function (data) {
        if (data.status === 'success') {
          setBadge(0);
          loadList();
        }
      });
    });
  }

  if (toggle && panel) {
    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      panel.classList.toggle('hidden');
      const isOpen = !panel.classList.contains('hidden');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      if (isOpen) {
        loadList();
        document.getElementById('profileDropdown')?.classList.remove('open');
      }
    });

    panel.addEventListener('click', function (e) {
      e.stopPropagation();
    });
  }

  document.addEventListener('click', function () {
    if (panel) panel.classList.add('hidden');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  });

  loadList().then(function () {
    poll();
    pollTimer = setInterval(poll, POLL_MS);
  });

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') {
      poll();
    }
  });
})();
