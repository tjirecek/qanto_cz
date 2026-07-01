(() => {
  function fieldValue(id) {
    const editor = window.tinymce?.get(id);
    if (editor) {
      return editor.getContent();
    }

    const el = document.getElementById(id);
    return el ? el.value : '';
  }

  function setField(id, value) {
    const editor = window.tinymce?.get(id);
    if (editor) {
      editor.setContent(value || '');
      editor.save();
      return;
    }

    const el = document.getElementById(id);
    if (el) {
      el.value = value || '';
    }
  }

  function setStatus(button, message, isError = false) {
    const target = button?.dataset?.translateStatusTarget || '';
    const status = target ? document.querySelector(target) : null;
    if (!status) return;

    status.textContent = message;
    status.classList.toggle('text-danger', isError);
    status.classList.toggle('text-success', !isError && message !== '');
  }

  async function translate(button, formData) {
    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Překládám';
    setStatus(button, '');

    try {
      const response = await fetch('/secure/functions/ajax/stattexty_translate.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
      });
      const json = await response.json();

      if (!response.ok || !json.ok) {
        throw new Error(json.error || `Překlad selhal (${response.status}).`);
      }

      return json.data || {};
    } catch (error) {
      setStatus(button, error.message || 'Překlad selhal.', true);
      return null;
    } finally {
      button.disabled = false;
      button.innerHTML = originalHtml;
    }
  }

  document.querySelectorAll('[data-stattexty-translate="stattext"]').forEach((button) => {
    button.addEventListener('click', async () => {
      const id = button.dataset.stattextId || '';
      if (!id) {
        setStatus(button, 'Chybí ID statického textu.', true);
        return;
      }

      if ((fieldValue('nazev').trim() !== '' || fieldValue('editor').trim() !== '') &&
        !window.confirm('EN pole už obsahují text. Opravdu je přepsat překladem z CZ?')) {
        return;
      }

      const formData = new FormData();
      formData.append('type', 'stattext');
      formData.append('id', id);

      const data = await translate(button, formData);
      if (!data) return;

      setField('nazev', data.nazev || '');
      setField('editor', data.text || '');
      setStatus(button, 'Překlad předvyplněn, zkontroluj a ulož.');
    });
  });

  document.querySelectorAll('[data-stattexty-translate="statvyraz"]').forEach((button) => {
    button.addEventListener('click', async () => {
      const cz = fieldValue('cz');
      if (cz.trim() === '') {
        setStatus(button, 'CZ výraz je prázdný.', true);
        return;
      }

      if (fieldValue('en').trim() !== '' &&
        !window.confirm('EN výraz už obsahuje text. Opravdu ho přepsat překladem aktuální CZ hodnoty?')) {
        return;
      }

      const formData = new FormData();
      formData.append('type', 'statvyraz');
      formData.append('cz', cz);

      const data = await translate(button, formData);
      if (!data) return;

      setField('en', data.en || '');
      setStatus(button, 'Překlad předvyplněn z aktuální CZ hodnoty, zkontroluj a ulož.');
    });
  });
})();
