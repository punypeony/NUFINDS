(function () {
  const form = document.getElementById('notify-form');
  if (!form) return;

  const apiUrl = form.dataset.apiUrl;
  const csrfToken = form.dataset.csrfToken || '';
  const presetSelect = document.getElementById('notify-preset');
  const titleInput = document.getElementById('notify-title');
  const messageInput = document.getElementById('notify-message');
  const targetWrap = document.getElementById('notify-target-wrap');
  const targetInput = document.getElementById('notify-target');
  const recentList = document.getElementById('notify-recent-list');
  const statusEl = document.getElementById('notify-status');

  let presets = {};

  function setStatus(text, isError) {
    if (!statusEl) return;
    statusEl.textContent = text || '';
    statusEl.style.color = isError ? '#b00020' : '#1d6b3a';
  }

  function post(action, data) {
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

  function loadPresets() {
    return post('presets').then(function (data) {
      if (data.status !== 'success' || !presetSelect) return;
      presetSelect.innerHTML = '';
      presets = {};
      (data.presets || []).forEach(function (p) {
        presets[p.id] = p;
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.label;
        presetSelect.appendChild(opt);
      });
    });
  }

  function applyPreset(id) {
    const p = presets[id];
    if (!p || !titleInput || !messageInput) return;
    if (id === 'custom') return;
    titleInput.value = p.title || '';
    messageInput.value = p.message || '';
  }

  function audience() {
    const checked = form.querySelector('input[name="audience"]:checked');
    return checked ? checked.value : 'one';
  }

  function toggleTarget() {
    if (!targetWrap) return;
    targetWrap.classList.toggle('hidden', audience() !== 'one');
  }

  function loadRecent() {
    return post('recent').then(function (data) {
      if (!recentList || data.status !== 'success') return;
      recentList.innerHTML = '';
      const items = data.items || [];
      if (items.length === 0) {
        recentList.innerHTML = '<p class="notify-recent-meta">No notifications sent yet.</p>';
        return;
      }
      items.forEach(function (item) {
        const div = document.createElement('div');
        div.className = 'notify-recent-item';
        div.innerHTML =
          '<strong></strong><p></p><div class="notify-recent-meta"></div>';
        div.querySelector('strong').textContent = item.title;
        div.querySelector('p').textContent = item.message;
        const when = item.createdAt ? new Date(item.createdAt.replace(' ', 'T')).toLocaleString() : '';
        div.querySelector('.notify-recent-meta').textContent =
          'To ' + item.studentNumber + ' (' + item.studentEmail + ') · ' + when;
        recentList.appendChild(div);
      });
    });
  }

  form.querySelectorAll('input[name="audience"]').forEach(function (radio) {
    radio.addEventListener('change', toggleTarget);
  });

  if (presetSelect) {
    presetSelect.addEventListener('change', function () {
      applyPreset(presetSelect.value);
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    setStatus('Sending…', false);
    post('send', {
      audience: audience(),
      target: targetInput ? targetInput.value.trim() : '',
      title: titleInput ? titleInput.value.trim() : '',
      message: messageInput ? messageInput.value.trim() : '',
    }).then(function (data) {
      if (data.status === 'success') {
        setStatus(data.message || 'Sent.', false);
        if (messageInput && presetSelect && presetSelect.value === 'custom') {
          messageInput.value = '';
        }
        loadRecent();
      } else {
        setStatus(data.message || 'Could not send.', true);
      }
    }).catch(function () {
      setStatus('Network error. Try again.', true);
    });
  });

  toggleTarget();
  loadPresets().then(loadRecent);
})();
