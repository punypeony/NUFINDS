(function () {
  const profileToggle = document.getElementById('profileToggle');
  const profileDropdown = document.getElementById('profileDropdown');
  const logoutBtn = document.getElementById('logoutBtn');

  if (profileToggle && profileDropdown) {
    profileToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      profileDropdown.classList.toggle('open');
    });
    document.addEventListener('click', function () {
      profileDropdown.classList.remove('open');
    });
  }

  if (logoutBtn && logoutBtn.dataset.logoutUrl) {
    logoutBtn.addEventListener('click', function () {
      window.location.href = logoutBtn.dataset.logoutUrl;
    });
  }
})();
