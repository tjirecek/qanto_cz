(() => {
  const button = document.getElementById('newsTranslateFromCz');
  if (!button) return;

  const status = document.querySelector('.news-translate-status');

  function setStatus(message, isError = false) {
    if (!status) return;
    status.textContent = message;
    status.classList.toggle('text-danger', isError);
    status.classList.toggle('text-success', !isError && message !== '');
  }

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

  function hasTargetContent() {
    return ['nazev', 'perex', 'text', 'seo_title', 'seo_description']
      .some((id) => fieldValue(id).trim() !== '');
  }

  button.addEventListener('click', async () => {
    const newsId = button.getAttribute('data-news-id') || '';
    if (!newsId) {
      setStatus('Chybí ID novinky.', true);
      return;
    }

    if (hasTargetContent() && !window.confirm('EN pole už obsahují text. Opravdu je přepsat překladem z CZ?')) {
      return;
    }

    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Překládám';
    setStatus('');

    try {
      const formData = new FormData();
      formData.append('news_id', newsId);

      const response = await fetch('/secure/functions/ajax/news_translate.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
      });
      const json = await response.json();

      if (!response.ok || !json.ok) {
        throw new Error(json.error || `Překlad selhal (${response.status}).`);
      }

      const data = json.data || {};
      setField('nazev', data.nazev || '');
      setField('perex', data.perex || '');
      setField('text', data.text || '');
      setField('seo_title', data.seo_title || '');
      setField('seo_description', data.seo_description || '');
      setStatus('Překlad předvyplněn, zkontroluj a ulož.');
    } catch (error) {
      setStatus(error.message || 'Překlad selhal.', true);
    } finally {
      button.disabled = false;
      button.innerHTML = originalHtml;
    }
  });
})();
