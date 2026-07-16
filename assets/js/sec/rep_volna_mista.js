(() => {
  document.addEventListener('change', (event) => {
    const checkbox = event.target.closest('form[data-rep-volna-mista-filter] input[name="types[]"]');
    if (!checkbox) return;
    const form = checkbox.form;
    if (!form) return;
    if (typeof form.requestSubmit === 'function') form.requestSubmit();
    else form.submit();
  });

  document.addEventListener('change', (event) => {
    const radio = event.target.closest('[data-rep-volna-mista-contact-picker] input[name="kontakt_lide_id"]');
    if (!radio) return;

    const picker = radio.closest('[data-rep-volna-mista-contact-picker]');
    const label = picker ? picker.querySelector('[data-rep-volna-mista-contact-label]') : null;
    if (label) {
      label.textContent = radio.getAttribute('data-contact-label') || 'bez přiřazené osoby';
    }
  });

  document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');
    if (!form) return;
    const message = form.getAttribute('data-confirm') || 'Opravdu provést akci?';
    if (!window.confirm(message)) event.preventDefault();
  });
})();
