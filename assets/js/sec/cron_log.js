document.addEventListener('click', (event) => {
  const trigger = event.target.closest('.cron-log-message-btn');
  if (!trigger) return;

  event.preventDefault();

  const body = document.getElementById('cronLogMessageModalBody');
  const modalEl = document.getElementById('cronLogMessageModal');
  if (!body || !modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;

  body.textContent = trigger.getAttribute('data-message') || '';
  bootstrap.Modal.getOrCreateInstance(modalEl).show();
});
