async function fetchCsrfToken(url) {
  const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
  const data = await response.json();
  return data.csrf_token || '';
}

function ensureCsrfField(form, token) {
  if (!form || !token) return;
  let input = form.querySelector('input[name="csrf_token"]');
  if (!input) {
    input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'csrf_token';
    form.appendChild(input);
  }
  input.value = token;
}

async function initFormCsrf(form) {
  const url = form.dataset.csrfUrl;
  if (!url) return;
  try {
    const token = await fetchCsrfToken(url);
    ensureCsrfField(form, token);
  } catch (error) {
    // Form stays without token; server will reject submit.
  }
}
