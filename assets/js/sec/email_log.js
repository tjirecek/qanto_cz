document.addEventListener('click', (event) => {
  const trigger = event.target.closest('.email-log-detail-btn');
  if (!trigger) return;

  event.preventDefault();

  const body = document.getElementById('emailLogDetailModalBody');
  const modalEl = document.getElementById('emailLogDetailModal');
  if (!body || !modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;

  body.textContent = trigger.getAttribute('data-detail') || '';
  bootstrap.Modal.getOrCreateInstance(modalEl).show();
});
