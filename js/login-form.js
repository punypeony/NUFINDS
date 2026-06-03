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
          window.location.href = result.role === 'admin' ? adminHomeUrl : studentHomeUrl;
        };
      } else {
        showPopup('error', result.message || 'Login failed. Please try again.');
        popupOk.onclick = () => popupOverlay.classList.add('hidden');
      }
    } catch (error) {
      showPopup('error', 'Unable to submit login. Please try again.');
      popupOk.onclick = () => popupOverlay.classList.add('hidden');
    }
  });
});
