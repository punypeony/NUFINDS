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

document.addEventListener('DOMContentLoaded', function () {
  const registerForm = document.querySelector('.register-form');
  const popupOverlay = document.getElementById('popup-overlay');
  const popupOk = document.getElementById('popup-ok');

  if (!registerForm) return;

  const homeUrl = registerForm.dataset.homeUrl || 'student/home.html';

  registerForm.addEventListener('submit', async function (event) {
    event.preventDefault();

    const password = registerForm.querySelector('[name="StudentPassword"]').value;
    const confirmPassword = registerForm.querySelector('[name="ConfirmPassword"]').value;

    if (password !== confirmPassword) {
      showPopup('error', 'Passwords do not match.');
      popupOk.onclick = () => popupOverlay.classList.add('hidden');
      return;
    }

    try {
      const response = await fetch(registerForm.action, {
        method: 'POST',
        body: new FormData(registerForm),
        credentials: 'same-origin',
      });
      const result = await response.json();

      if (result.status === 'success') {
        showPopup('success', result.message || 'Account created. Redirecting...');
        popupOk.onclick = function () {
          popupOverlay.classList.add('hidden');
          window.location.href = homeUrl;
        };
      } else {
        showPopup('error', result.message || 'Registration failed. Please try again.');
        popupOk.onclick = () => popupOverlay.classList.add('hidden');
      }
    } catch (error) {
      showPopup('error', 'Unable to submit registration. Please try again.');
      popupOk.onclick = () => popupOverlay.classList.add('hidden');
    }
  });
});
