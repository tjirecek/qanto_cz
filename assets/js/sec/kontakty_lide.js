(() => {
  document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');
    if (!form) return;

    const message = form.getAttribute('data-confirm') || 'Opravdu provést akci?';
    if (!window.confirm(message)) {
      event.preventDefault();
    }
  });
})();
