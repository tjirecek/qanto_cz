(() => {
  document.addEventListener('change', (event) => {
    const checkbox = event.target.closest('form[data-rep-tenis-qcup-filter] input[name="years[]"]');
    if (!checkbox) return;

    const form = checkbox.form;
    if (!form) return;

    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
    } else {
      form.submit();
    }
  });

  document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');
    if (!form) return;

    const message = form.getAttribute('data-confirm') || 'Opravdu provést akci?';
    if (!window.confirm(message)) {
      event.preventDefault();
    }
  });
})();
