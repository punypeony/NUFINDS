function showPopup(type, message) {
  if (!message) return;
  const overlay = document.getElementById('popup-overlay');
  const title = document.getElementById('popup-title');
  const content = document.getElementById('popup-content');
  title.textContent = type === 'success' ? 'Success' : 'Error';
  content.classList.toggle('success', type === 'success');
  content.classList.toggle('error', type !== 'success');
  document.getElementById('popup-message').textContent = message;
  overlay.classList.remove('hidden');
}

function setFormLocked(form, locked) {
  const submitBtn = form.querySelector('.submit-btn');
  form.querySelectorAll('input').forEach((input) => {
    input.disabled = locked;
  });
  if (submitBtn) {
    submitBtn.disabled = locked;
  }
}

function applyLoginLockout(form, seconds) {
  let remaining = seconds;
  setFormLocked(form, true);

  const submitBtn = form.querySelector('.submit-btn');
  const defaultLabel = submitBtn ? submitBtn.textContent : 'Submit';

  const tick = () => {
    if (submitBtn) {
      submitBtn.textContent = `Locked (${remaining}s)`;
    }
    if (remaining <= 0) {
      setFormLocked(form, false);
      if (submitBtn) {
        submitBtn.textContent = defaultLabel;
      }
      return;
    }
    remaining -= 1;
    setTimeout(tick, 1000);
  };

  tick();
}

function setLoginMode(isAdmin) {
  const loginType = document.getElementById('login-type');
  const studentGroup = document.getElementById('student-password-group');
  const studentInput = document.getElementById('student-password');
  const adminGroup = document.getElementById('admin-password-group');
  const adminInput = document.getElementById('admin-password');
  const heading = document.getElementById('login-heading');
  const subtitle = document.getElementById('login-subtitle');
  const footer = document.getElementById('login-footer');

  if (!loginType) return;

  loginType.value = isAdmin ? 'admin' : 'student';

  if (isAdmin) {
    studentGroup.classList.add('hidden');
    studentGroup.setAttribute('hidden', '');
    studentInput.removeAttribute('required');
    studentInput.value = '';
    adminGroup.classList.remove('hidden');
    adminGroup.removeAttribute('hidden');
    adminInput.setAttribute('required', 'required');
    heading.textContent = 'Admin Login';
    subtitle.textContent = 'Enter your admin email and password.';
    footer.textContent = 'Staff verification access only.';
    return;
  }

  studentGroup.classList.remove('hidden');
  studentGroup.removeAttribute('hidden');
  studentInput.setAttribute('required', 'required');
  adminGroup.classList.add('hidden');
  adminGroup.setAttribute('hidden', '');
  adminInput.removeAttribute('required');
  adminInput.value = '';
  heading.textContent = 'Welcome!';
  subtitle.textContent = 'Enter your email and password.';
  footer.textContent = 'For Nationalians use only.';
}

async function redirectIfLoggedIn(form) {
  const checkUrl = form.dataset.sessionCheckUrl;
  if (!checkUrl) return;

  try {
    const response = await fetch(checkUrl, { credentials: 'same-origin', cache: 'no-store' });
    const data = await response.json();
    if (!data.logged_in) return;

    const homeUrl = data.role === 'admin'
      ? (form.dataset.adminHomeUrl || 'admin/home.html')
      : (form.dataset.studentHomeUrl || 'student/home.html');
    window.location.replace(homeUrl);
  } catch (error) {
    // Ignore network errors; login form stays available.
  }
}

async function checkAdminEmail(email, checkUrl) {
  if (!email || !email.includes('@')) {
    setLoginMode(false);
    return;
  }

  try {
    const response = await fetch(`${checkUrl}?email=${encodeURIComponent(email)}`);
    const data = await response.json();
    setLoginMode(Boolean(data.is_admin));
  } catch (error) {
    setLoginMode(false);
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const loginForm = document.querySelector('.login-form');
  const popupOverlay = document.getElementById('popup-overlay');
  const popupOk = document.getElementById('popup-ok');
  const emailInput = document.getElementById('login-email');

  if (!loginForm) return;

  setLoginMode(false);
  initFormCsrf(loginForm);
  redirectIfLoggedIn(loginForm);
  window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
      redirectIfLoggedIn(loginForm);
    }
  });

  const studentHomeUrl = loginForm.dataset.studentHomeUrl || 'student/home.html';
  const adminHomeUrl = loginForm.dataset.adminHomeUrl || 'admin/home.html';
  const checkUrl = loginForm.dataset.checkEmailUrl || '../database/php/auth/check_admin_email.php';

  let emailTimer = null;
  if (emailInput) {
    const runCheck = () => checkAdminEmail(emailInput.value.trim(), checkUrl);
    emailInput.addEventListener('blur', runCheck);
    emailInput.addEventListener('input', function () {
      clearTimeout(emailTimer);
      emailTimer = setTimeout(runCheck, 400);
    });
  }

  loginForm.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (loginForm.querySelector('.submit-btn')?.disabled) return;

    try {
      const response = await fetch(loginForm.action, {
        method: 'POST',
        body: new FormData(loginForm),
        credentials: 'same-origin',
      });
      const result = await response.json();

      if (result.status === 'success') {
        showPopup('success', result.message || 'Login successful. Redirecting...');
        popupOk.onclick = function () {
          popupOverlay.classList.add('hidden');
          window.location.replace(result.role === 'admin' ? adminHomeUrl : studentHomeUrl);
        };
      } else {
        let message = result.message || 'Login failed. Please try again.';
        if (result.attempts_remaining != null) {
          message += ` ${result.attempts_remaining} attempt(s) remaining.`;
        }
        showPopup('error', message);
        popupOk.onclick = () => popupOverlay.classList.add('hidden');
        if (result.locked && result.retry_after) {
          applyLoginLockout(loginForm, result.retry_after);
        }
      }
    } catch (error) {
      showPopup('error', 'Unable to submit login. Please try again.');
      popupOk.onclick = () => popupOverlay.classList.add('hidden');
    }
  });
});
