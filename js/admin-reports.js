(function () {
  const body = document.body;
  const apiUrl = body.dataset.reportsApi;
  const reportType = body.dataset.reportType;

  if (!apiUrl || !reportType) return;

  function rowData(row) {
    const data = { id: row.dataset.id };
    row.querySelectorAll('input[name], select[name], textarea[name]').forEach((el) => {
      data[el.name] = el.value;
    });
    return data;
  }

  async function post(action, data) {
    const response = await fetch(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ action, ...data }),
    });
    return response.json();
  }

  document.querySelectorAll('.editable-row .btn-save').forEach((btn) => {
    btn.addEventListener('click', async function () {
      const row = btn.closest('.editable-row');
      const data = rowData(row);
      let action = 'update_lost';
      if (reportType === 'found') action = 'update_found';
      if (reportType === 'history') action = 'update_history';

      const result = await post(action, data);
      alert(result.message || result.status);
      if (result.status === 'success') window.location.reload();
    });
  });

  document.querySelectorAll('.editable-row .btn-delete').forEach((btn) => {
    btn.addEventListener('click', async function () {
      if (!confirm('Delete this record permanently?')) return;
      const row = btn.closest('.editable-row');
      let action = 'delete_lost';
      if (reportType === 'found') action = 'delete_found';
      if (reportType === 'history') action = 'delete_history';

      const result = await post(action, { id: row.dataset.id });
      alert(result.message || result.status);
      if (result.status === 'success') window.location.reload();
    });
  });
})();
