(function () {
  'use strict';

  const config = window.PLAN_IMPORT_CONFIG || {};
  const modal = document.getElementById('plan-import-modal');
  const form = document.getElementById('form-importar-plan');
  if (!modal || !form) return;

  const assignment = document.getElementById('plan-import-asignacion');
  const year = document.getElementById('plan-import-anio');
  const file = document.getElementById('plan-import-pdf');
  const message = document.getElementById('plan-import-message');
  const submit = document.getElementById('plan-import-submit');
  let assignmentsLoaded = false;

  function showMessage(text, isError) {
    message.textContent = text || '';
    message.className = 'sm:col-span-2 ' + (isError ? 'app-message-error' : 'app-message-success');
  }

  async function jsonResponse(response) {
    const data = await response.json().catch(function () { return null; });
    if (!response.ok || !data || data.success !== true) {
      throw new Error((data && (data.message || data.error)) || 'No se pudo completar la solicitud.');
    }
    return data;
  }

  async function loadAssignments() {
    if (assignmentsLoaded) return;
    assignment.disabled = true;
    try {
      const response = await fetch(config.apiAssignments, { credentials: 'same-origin' });
      const payload = await jsonResponse(response);
      const rows = Array.isArray(payload.data) ? payload.data : [];
      assignment.replaceChildren(new Option('Seleccione una asignación', ''));
      rows.forEach(function (row) {
        const label = row.descripcion || [row.materia, row.grado, row.aula, row.profesor].filter(Boolean).join(' · ');
        const option = new Option(label || ('Asignación ' + row.id_asignacion), String(row.id_asignacion));
        option.dataset.year = String(row.anio_lectivo || '');
        assignment.appendChild(option);
      });
      assignmentsLoaded = true;
      if (!rows.length) showMessage('No tiene asignaciones disponibles para importar.', true);
    } catch (error) {
      assignment.replaceChildren(new Option('No se pudieron cargar las asignaciones', ''));
      showMessage(error.message, true);
    } finally {
      assignment.disabled = false;
    }
  }

  function openModal() {
    modal.classList.remove('hidden');
    document.body.classList.add('modal-open');
    showMessage('', false);
    loadAssignments().then(function () { assignment.focus(); });
  }

  function closeModal() {
    if (submit.disabled) return;
    modal.classList.add('hidden');
    document.body.classList.remove('modal-open');
  }

  document.querySelectorAll('[data-open-plan-import]').forEach(function (button) {
    button.addEventListener('click', openModal);
  });
  document.querySelectorAll('[data-close-plan-import]').forEach(function (button) {
    button.addEventListener('click', closeModal);
  });
  modal.addEventListener('click', function (event) {
    if (event.target === modal) closeModal();
  });
  assignment.addEventListener('change', function () {
    const selected = assignment.options[assignment.selectedIndex];
    if (selected && selected.dataset.year) year.value = selected.dataset.year;
  });

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    showMessage('', false);
    if (!form.reportValidity()) return;
    const selectedFile = file.files && file.files[0];
    if (!selectedFile || !/\.pdf$/i.test(selectedFile.name)) {
      showMessage('Seleccione un archivo con extensión PDF.', true);
      return;
    }
    if (selectedFile.size > 10 * 1024 * 1024) {
      showMessage('El archivo supera el límite de 10 MB.', true);
      return;
    }

    submit.disabled = true;
    submit.textContent = 'Analizando...';
    showMessage('Analizando el PDF. Esto puede tardar unos segundos.', false);
    try {
      const body = new FormData(form);
      body.set('csrf_token', config.csrf);
      const response = await fetch(config.apiUpload, {
        method: 'POST', credentials: 'same-origin', body: body,
        headers: { 'X-CSRF-Token': config.csrf }
      });
      const payload = await jsonResponse(response);
      const token = payload.data && payload.data.token;
      if (!token) throw new Error('El servidor no devolvió el token de preview.');
      window.location.assign(config.preview + '?token=' + encodeURIComponent(token));
    } catch (error) {
      showMessage(error.message, true);
      submit.disabled = false;
      submit.textContent = 'Analizar plan';
    }
  });
})();
