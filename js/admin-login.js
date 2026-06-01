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
  const loginForm = document.querySelector('.admin-login-form');
  const popupOverlay = document.getElementById('popup-overlay');
  const popupOk = document.getElementById('popup-ok');

  if (!loginForm) return;

  const verifyUrl = loginForm.dataset.verifyUrl || '../database/php/verify/verify_matches.php';

  loginForm.addEventListener('submit', async function (event) {
    event.preventDefault();
    try {
      const response = await fetch(loginForm.action, {
        method: 'POST',
        body: new FormData(loginForm),
      });
      const result = await response.json();

      if (result.status === 'success') {
        showPopup('success', result.message || 'Login successful. Redirecting...');
        popupOk.onclick = function () {
          popupOverlay.classList.add('hidden');
          window.location.href = verifyUrl;
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
