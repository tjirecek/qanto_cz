(() => {
  let currentOriginal = '';
  let currentFile = '';
  let dragWasUsed = false;
  let suppressClickUntil = 0;

  const grid = document.getElementById('galeriePhotoGrid');
  const orderInput = document.getElementById('galeriePhotoOrderInput');
  const orderSubmit = document.getElementById('galeriePhotoOrderSubmit');

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('a[data-confirm], button[data-confirm]');
    if (!trigger) return;

    const message = trigger.getAttribute('data-confirm') || 'Opravdu provest akci?';
    if (!window.confirm(message)) {
      event.preventDefault();
      event.stopPropagation();
    }
  });

  document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');
    if (!form) return;

    const message = form.getAttribute('data-confirm') || 'Opravdu provest akci?';
    if (!window.confirm(message)) {
      event.preventDefault();
      event.stopPropagation();
    }
  });

  function updateOrderState() {
    if (!grid || !orderInput || !orderSubmit) return;

    const ids = Array.from(grid.querySelectorAll('.galerie-photo-thumb'))
      .map((item) => item.getAttribute('data-photo-id'))
      .filter(Boolean);

    orderInput.value = ids.join(',');
    orderSubmit.classList.remove('d-none');

    grid.querySelectorAll('.galerie-photo-thumb').forEach((item, index) => {
      const badge = item.querySelector('.galerie-photo-order');
      if (badge) badge.textContent = String(index + 1);
    });
  }

  if (grid?.getAttribute('data-sortable') === '1') {
    let draggedItem = null;
    let autoScrollFrame = 0;
    let autoScrollSpeed = 0;

    function stopAutoScroll() {
      autoScrollSpeed = 0;
      if (autoScrollFrame) {
        window.cancelAnimationFrame(autoScrollFrame);
        autoScrollFrame = 0;
      }
    }

    function autoScrollStep() {
      if (autoScrollSpeed !== 0) {
        window.scrollBy(0, autoScrollSpeed);
        autoScrollFrame = window.requestAnimationFrame(autoScrollStep);
      } else {
        autoScrollFrame = 0;
      }
    }

    function updateAutoScroll(clientY) {
      const edge = 110;
      const maxSpeed = 28;
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
      let speed = 0;

      if (clientY < edge) {
        speed = -Math.ceil(((edge - clientY) / edge) * maxSpeed);
      } else if (clientY > viewportHeight - edge) {
        speed = Math.ceil(((clientY - (viewportHeight - edge)) / edge) * maxSpeed);
      }

      autoScrollSpeed = speed;
      if (autoScrollSpeed !== 0 && !autoScrollFrame) {
        autoScrollFrame = window.requestAnimationFrame(autoScrollStep);
      }
      if (autoScrollSpeed === 0) {
        stopAutoScroll();
      }
    }

    grid.addEventListener('dragstart', (event) => {
      const item = event.target.closest('.galerie-photo-thumb');
      if (!item) return;

      draggedItem = item;
      dragWasUsed = true;
      item.classList.add('is-dragging');
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/plain', item.getAttribute('data-photo-id') || '');
    });

    document.addEventListener('dragover', (event) => {
      if (!draggedItem) return;
      updateAutoScroll(event.clientY);
    });

    grid.addEventListener('dragend', () => {
      stopAutoScroll();
      if (draggedItem) draggedItem.classList.remove('is-dragging');
      grid.querySelectorAll('.is-drag-over').forEach((item) => item.classList.remove('is-drag-over'));
      draggedItem = null;
      if (dragWasUsed) {
        suppressClickUntil = Date.now() + 250;
        updateOrderState();
      }

      window.setTimeout(() => {
        dragWasUsed = false;
      }, 250);
    });

    grid.addEventListener('dragover', (event) => {
      if (!draggedItem) return;

      updateAutoScroll(event.clientY);

      const target = event.target.closest('.galerie-photo-thumb');
      if (!target || target === draggedItem) {
        event.preventDefault();
        return;
      }

      event.preventDefault();
      event.dataTransfer.dropEffect = 'move';
      target.classList.add('is-drag-over');

      const targetRect = target.getBoundingClientRect();
      const insertAfter = event.clientY > targetRect.top + targetRect.height / 2
        || (Math.abs(event.clientY - (targetRect.top + targetRect.height / 2)) < targetRect.height / 3
          && event.clientX > targetRect.left + targetRect.width / 2);

      grid.insertBefore(draggedItem, insertAfter ? target.nextSibling : target);
    });

    grid.addEventListener('dragleave', (event) => {
      const target = event.target.closest('.galerie-photo-thumb');
      if (target) target.classList.remove('is-drag-over');
    });

    grid.addEventListener('drop', (event) => {
      if (!draggedItem) return;
      stopAutoScroll();
      event.preventDefault();
      grid.querySelectorAll('.is-drag-over').forEach((item) => item.classList.remove('is-drag-over'));
      updateOrderState();
    });

    grid.addEventListener('click', (event) => {
      if (Date.now() > suppressClickUntil) return;
      event.preventDefault();
      event.stopPropagation();
    }, true);
  }

  document.getElementById('galeriePhotoEditModal')?.addEventListener('show.bs.modal', (event) => {
    const button = event.relatedTarget;
    if (!button) return;

    const setValue = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.value = value ?? '';
    };

    const photoId = button.getAttribute('data-photo-id') || '';
    const file = button.getAttribute('data-photo-file') || '';
    const thumb = button.getAttribute('data-photo-thumb') || '';
    const original = button.getAttribute('data-photo-original') || '';
    currentOriginal = original;
    currentFile = file;

    setValue('galeriePhotoModalPhotoId', photoId);
    setValue('galeriePhotoModalDeletePhotoId', photoId);
    setValue('galeriePhotoModalOrder', button.getAttribute('data-photo-order') || '');
    setValue('galeriePhotoModalCz', button.getAttribute('data-photo-cz') || '');
    setValue('galeriePhotoModalEn', button.getAttribute('data-photo-en') || '');

    const validInput = document.getElementById('galeriePhotoModalValid');
    if (validInput) validInput.checked = (button.getAttribute('data-photo-valid') || '1') === '1';

    const auditEl = document.getElementById('galeriePhotoModalAudit');
    if (auditEl) {
      const tsI = button.getAttribute('data-photo-ts-i') || '';
      const userI = button.getAttribute('data-photo-user-i') || '';
      const tsU = button.getAttribute('data-photo-ts-u') || '';
      const userU = button.getAttribute('data-photo-user-u') || '';
      auditEl.textContent = `Založeno: ${tsI}; Založil: ${userI}; Upraveno: ${tsU}; Upravil: ${userU}`;
    }

    const preview = document.getElementById('galeriePhotoModalPreview');
    if (preview) {
      preview.src = thumb;
      preview.alt = file;
    }

    const fileEl = document.getElementById('galeriePhotoModalFile');
    if (fileEl) fileEl.textContent = file;

    const originalButton = document.getElementById('galeriePhotoModalOriginal');
    if (originalButton) originalButton.disabled = original === '';
  });

  document.getElementById('galeriePhotoLightboxModal')?.addEventListener('show.bs.modal', () => {
    const image = document.getElementById('galeriePhotoLightboxImage');
    if (image) {
      image.src = currentOriginal;
      image.alt = currentFile;
    }

    const title = document.getElementById('galeriePhotoLightboxTitle');
    if (title) title.textContent = currentFile || 'Originál fotografie';
  });

  document.getElementById('galeriePhotoLightboxModal')?.addEventListener('hidden.bs.modal', () => {
    const image = document.getElementById('galeriePhotoLightboxImage');
    if (image) image.src = '';
  });
})();
