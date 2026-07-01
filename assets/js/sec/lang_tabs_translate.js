(() => {
  function cssEscape(value) {
    if (window.CSS?.escape) {
      return window.CSS.escape(value);
    }

    return String(value).replace(/["\\]/g, '\\$&');
  }

  function fieldValue(el) {
    if (!el) return '';
    const editor = window.tinymce?.get(el.id || '');
    if (editor) {
      return editor.getContent();
    }

    return el.value || '';
  }

  function setField(el, value) {
    if (!el) return;
    const editor = window.tinymce?.get(el.id || '');
    if (editor) {
      editor.setContent(value || '');
      editor.save();
      return;
    }

    el.value = value || '';
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function setStatus(button, message, isError = false) {
    const selector = button.dataset.translateStatusTarget || '';
    const status = selector ? document.querySelector(selector) : null;
    if (!status) return;

    status.textContent = message;
    status.classList.toggle('text-danger', isError);
    status.classList.toggle('text-success', !isError && message !== '');
  }

  function collectItems(scope) {
    const items = [];
    scope.querySelectorAll('[data-translate-source]').forEach((source) => {
      const key = source.dataset.translateSource || '';
      const target = scope.querySelector(`[data-translate-target="${cssEscape(key)}"]`);
      if (!key || !target) return;

      items.push({
        key,
        text: fieldValue(source),
        format: source.dataset.translateFormat || 'text',
      });
    });

    return items;
  }

  function targetHasContent(scope) {
    return Array.from(scope.querySelectorAll('[data-translate-target]'))
      .some((target) => fieldValue(target).trim() !== '');
  }

  document.querySelectorAll('[data-admin-translate="cs-en"]').forEach((button) => {
    button.addEventListener('click', async () => {
      const scopeSelector = button.dataset.translateScope || 'form';
      const scope = button.closest(scopeSelector) || button.closest('form') || document;
      const items = collectItems(scope).filter((item) => item.text.trim() !== '');

      if (items.length === 0) {
        setStatus(button, 'CZ pole jsou prázdná.', true);
        return;
      }

      if (targetHasContent(scope) &&
        !window.confirm('EN pole už obsahují text. Opravdu je přepsat překladem aktuální CZ hodnoty?')) {
        return;
      }

      const originalHtml = button.innerHTML;
      button.disabled = true;
      button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Překládám';
      setStatus(button, '');

      try {
        const formData = new FormData();
        formData.append('items', JSON.stringify(items));

        const response = await fetch('/secure/functions/ajax/admin_translate.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
        });
        const json = await response.json();

        if (!response.ok || !json.ok) {
          throw new Error(json.error || `Překlad selhal (${response.status}).`);
        }

        const data = json.data || {};
        Object.entries(data).forEach(([key, value]) => {
          const target = scope.querySelector(`[data-translate-target="${cssEscape(key)}"]`);
          setField(target, value);
        });

        setStatus(button, 'Překlad předvyplněn z aktuálních CZ hodnot, zkontroluj a ulož.');
      } catch (error) {
        setStatus(button, error.message || 'Překlad selhal.', true);
      } finally {
        button.disabled = false;
        button.innerHTML = originalHtml;
      }
    });
  });
})();
