(function () {
  const body = document.body;
  const apiUrl = body.dataset.usersApi;
  const csrfToken = body.dataset.csrfToken || '';
  if (!apiUrl) return;

  const modal = document.getElementById('user-notify-modal');
  const presetSelect = document.getElementById('user-notify-preset');
  const titleInput = document.getElementById('user-notify-title');
  const messageInput = document.getElementById('user-notify-message');
  const targetMeta = document.querySelector('.user-notify-target-meta');
  const notifyStatus = document.getElementById('user-notify-status');
  const sendBtn = document.getElementById('user-notify-send');

  let presets = {};
  let notifyStudentNumber = '';

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

  function rowPayload(row) {
    const emailInput = row.querySelector('input[name="StudentEmail"]');
    const deptSelect = row.querySelector('select[name="CollegeDepartment"]');
    return {
      studentNumber: row.dataset.studentNumber,
      email: emailInput ? emailInput.value.trim() : '',
      department: deptSelect ? deptSelect.value : '',
    };
  }

  function setNotifyStatus(text, isError) {
    if (!notifyStatus) return;
    notifyStatus.textContent = text || '';
    notifyStatus.style.color = isError ? '#b00020' : '#1d6b3a';
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
    if (!p || !titleInput || !messageInput || id === 'custom') return;
    titleInput.value = p.title || '';
    messageInput.value = p.message || '';
  }

  function openNotifyModal(row) {
    if (!modal) return;
    notifyStudentNumber = row.dataset.studentNumber || '';
    const emailInput = row.querySelector('input[name="StudentEmail"]');
    const email = emailInput ? emailInput.value.trim() : '';
    if (targetMeta) {
      targetMeta.textContent = notifyStudentNumber + (email ? ' · ' + email : '');
    }
    if (titleInput) titleInput.value = '';
    if (messageInput) messageInput.value = '';
    if (presetSelect) presetSelect.value = 'custom';
    setNotifyStatus('', false);
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeNotifyModal() {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    notifyStudentNumber = '';
  }

  document.querySelectorAll('.user-row .btn-save').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const row = btn.closest('.user-row');
      post('update', rowPayload(row)).then(function (result) {
        alert(result.message || result.status);
        if (result.status === 'success') window.location.reload();
      });
    });
  });

  document.querySelectorAll('.user-row .btn-deactivate').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (btn.disabled) return;
      const row = btn.closest('.user-row');
      if (!confirm('Deactivate this account? The student will not be able to sign in.')) return;
      post('deactivate', { studentNumber: row.dataset.studentNumber }).then(function (result) {
        alert(result.message || result.status);
        if (result.status === 'success') window.location.reload();
      });
    });
  });

  document.querySelectorAll('.user-row .btn-reactivate').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (btn.disabled) return;
      const row = btn.closest('.user-row');
      post('reactivate', { studentNumber: row.dataset.studentNumber }).then(function (result) {
        alert(result.message || result.status);
        if (result.status === 'success') window.location.reload();
      });
    });
  });

  document.querySelectorAll('.user-row .btn-notify').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openNotifyModal(btn.closest('.user-row'));
    });
  });

  if (presetSelect) {
    presetSelect.addEventListener('change', function () {
      applyPreset(presetSelect.value);
    });
  }

  if (sendBtn) {
    sendBtn.addEventListener('click', function () {
      setNotifyStatus('Sending…', false);
      post('notify', {
        studentNumber: notifyStudentNumber,
        title: titleInput ? titleInput.value.trim() : '',
        message: messageInput ? messageInput.value.trim() : '',
      }).then(function (data) {
        if (data.status === 'success') {
          setNotifyStatus(data.message || 'Sent.', false);
        } else {
          setNotifyStatus(data.message || 'Could not send.', true);
        }
      }).catch(function () {
        setNotifyStatus('Network error. Try again.', true);
      });
    });
  }

  document.querySelectorAll('.user-notify-close').forEach(function (btn) {
    btn.addEventListener('click', closeNotifyModal);
  });

  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeNotifyModal();
    });
  }

  loadPresets();
})();
