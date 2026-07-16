(() => {
  const modalEl = document.querySelector('[data-oz-pobocka-modal]');
  if (!modalEl) return;

  const idInput = document.querySelector('[data-oz-pobocka-id]');
  const displayInput = document.querySelector('[data-oz-pobocka-display]');
  const searchInput = modalEl.querySelector('[data-oz-pobocka-search]');
  const emptyState = modalEl.querySelector('[data-oz-pobocka-empty]');
  const rows = Array.from(modalEl.querySelectorAll('[data-oz-pobocka-row]'));

  if (!idInput || !displayInput) return;

  function normalize(value) {
    return (value || '')
      .toString()
      .toLocaleLowerCase('cs-CZ')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
  }

  function filterRows() {
    const needle = normalize(searchInput ? searchInput.value : '');
    let visibleRows = 0;

    rows.forEach((row) => {
      const matches = needle === '' || normalize(row.textContent).includes(needle);
      row.classList.toggle('d-none', !matches);
      if (matches) visibleRows += 1;
    });

    if (emptyState) {
      emptyState.classList.toggle('d-none', visibleRows !== 0);
    }
  }

  function closeModal() {
    if (window.bootstrap && window.bootstrap.Modal) {
      const instance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
      instance.hide();
    }
  }

  function selectPobocka(button) {
    const id = button.dataset.pobockaId || '';
    const label = button.dataset.pobockaLabel || '';

    idInput.value = id;
    displayInput.value = label;
    displayInput.classList.remove('is-invalid');
    closeModal();
  }

  modalEl.addEventListener('click', (event) => {
    const button = event.target.closest('[data-oz-pobocka-choice]');
    if (button) {
      selectPobocka(button);
      return;
    }

    const row = event.target.closest('[data-oz-pobocka-row]');
    const rowButton = row ? row.querySelector('[data-oz-pobocka-choice]') : null;
    if (rowButton) {
      selectPobocka(rowButton);
    }
  });

  if (searchInput) {
    searchInput.addEventListener('input', filterRows);
    modalEl.addEventListener('shown.bs.modal', () => {
      searchInput.focus();
      searchInput.select();
      filterRows();
    });
  }
})();
