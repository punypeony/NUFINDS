let verifiedCard = null;

function showPopup(type, message) {
  if (!message) return;
  const popup = document.getElementById('message-popup');
  const content = document.getElementById('message-content');
  const title = document.getElementById('popup-title');
  const msg = document.getElementById('popup-message');
  content.classList.remove('success', 'error');
  content.classList.add(type === 'success' ? 'success' : 'error');
  title.textContent = type === 'success' ? 'Match Verified' : 'Error';
  msg.textContent = message;
  popup.classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', function () {
  const params = new URLSearchParams(window.location.search);
  const error = params.get('error');
  if (error) showPopup('error', decodeURIComponent(error));

  document.getElementById('popup-ok').addEventListener('click', function () {
    document.getElementById('message-popup').classList.add('hidden');
    history.replaceState(null, '', window.location.pathname);

    if (verifiedCard) {
      verifiedCard.classList.add('removing');
      setTimeout(() => {
        verifiedCard.remove();
        verifiedCard = null;
        const remaining = document.querySelectorAll('#cards-container .verify-card');
        if (remaining.length === 0) {
          window.location.reload();
        }
      }, 400);
    }
  });

  document.querySelectorAll('.verify-form').forEach((form) => {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const result = await response.json();
        if (result.status === 'success') {
          verifiedCard = form.closest('.verify-card');
          showPopup('success', result.message || 'Match has been verified successfully.');
        } else {
          showPopup('error', result.message || 'Unable to verify the match.');
        }
      } catch (err) {
        showPopup('error', 'Unable to verify the match. Please try again.');
      }
    });
  });
});
