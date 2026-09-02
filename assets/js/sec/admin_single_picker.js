(() => {
  const SELECTOR = 'select.js-admin-single-picker:not([multiple])';
  const READY_ATTRIBUTE = 'data-admin-single-picker-ready';
  const pickerBySelect = new WeakMap();
  let modal = null;
  let modalInstance = null;
  let activePicker = null;
  let returnFocus = null;

  function normalizeText(value) {
    const text = String(value || '').toLocaleLowerCase('cs');

    if (typeof text.normalize !== 'function') return text;

    return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  }

  function safeIconClasses(value) {
    return String(value || '')
      .split(/\s+/)
      .filter((item) => /^[a-z0-9_-]+$/i.test(item));
  }

  function selectedOption(select) {
    return select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;
  }

  function optionData(option, select) {
    const isEmpty = option.value === '';
    const configuredEmptyLabel = select.dataset.pickerEmptyLabel || '';

    return {
      value: option.value,
      title: isEmpty && configuredEmptyLabel ? configuredEmptyLabel : option.text.trim(),
      subtext: option.dataset.pickerSubtext || '',
      iconClasses: safeIconClasses(option.dataset.pickerIcon || ''),
      disabled: option.disabled || Boolean(option.closest('optgroup')?.disabled),
      selected: option.selected,
      searchText: normalizeText(`${option.text} ${option.dataset.pickerSubtext || ''}`),
    };
  }

  function createSelectedContent(data, emptyLabel) {
    const fragment = document.createDocumentFragment();
    const content = document.createElement('span');
    content.className = 'admin-single-picker__selection';

    const main = document.createElement('span');
    main.className = 'admin-single-picker__selection-main';

    if (data?.iconClasses.length) {
      const icon = document.createElement('i');
      icon.classList.add(...data.iconClasses, 'admin-single-picker__selection-icon');
      icon.setAttribute('aria-hidden', 'true');
      main.appendChild(icon);
    }

    const title = document.createElement('span');
    title.className = 'admin-single-picker__selection-title';
    title.textContent = data?.title || emptyLabel;
    main.appendChild(title);
    content.appendChild(main);

    if (data?.subtext) {
      const subtext = document.createElement('small');
      subtext.className = 'admin-single-picker__selection-subtext';
      subtext.textContent = data.subtext;
      content.appendChild(subtext);
    }

    const chevron = document.createElement('i');
    chevron.className = 'bi bi-chevron-down admin-single-picker__chevron';
    chevron.setAttribute('aria-hidden', 'true');

    fragment.append(content, chevron);
    return fragment;
  }

  function ensureModal() {
    if (modal?.isConnected) return modal;

    modal = document.createElement('div');
    modal.className = 'modal fade admin-single-picker-modal';
    modal.id = 'adminSinglePickerModal';
    modal.tabIndex = -1;
    modal.setAttribute('aria-labelledby', 'adminSinglePickerModalTitle');
    modal.setAttribute('aria-describedby', 'adminSinglePickerModalDescription');
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h5 class="modal-title" id="adminSinglePickerModalTitle"></h5>
              <p class="admin-single-picker-modal__description mb-0" id="adminSinglePickerModalDescription"></p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
          </div>
          <div class="modal-body">
            <label class="visually-hidden" for="adminSinglePickerSearch">Hledat v položkách</label>
            <div class="input-group input-group-sm admin-single-picker-modal__search-wrap">
              <span class="input-group-text" aria-hidden="true"><i class="bi bi-search"></i></span>
              <input type="search" class="form-control" id="adminSinglePickerSearch" autocomplete="off">
            </div>
            <div class="list-group admin-single-picker-modal__list" id="adminSinglePickerList"></div>
            <div class="admin-single-picker-modal__empty d-none" id="adminSinglePickerEmpty">Nebyla nalezena žádná položka.</div>
          </div>
          <div class="modal-footer py-2">
            <span class="small text-muted me-auto">Enter vybere první nalezenou položku.</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Zavřít</button>
          </div>
        </div>
      </div>`;
    document.body.appendChild(modal);

    const search = modal.querySelector('#adminSinglePickerSearch');
    search.addEventListener('input', filterModalOptions);
    search.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        const first = modal.querySelector('.admin-single-picker-option:not(.d-none):not(:disabled)');
        first?.click();
      }

      if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        modalInstance?.hide();
      }
    });

    modal.addEventListener('shown.bs.modal', () => {
      const backdrops = document.querySelectorAll('.modal-backdrop');
      backdrops[backdrops.length - 1]?.classList.add('admin-single-picker-backdrop');
      search.focus();
      search.select();
    });

    modal.addEventListener('hidden.bs.modal', () => {
      if (activePicker) activePicker.trigger.setAttribute('aria-expanded', 'false');
      activePicker = null;
      document.querySelectorAll('.admin-single-picker-backdrop').forEach((backdrop) => backdrop.remove());

      if (document.querySelector('.modal.show')) {
        document.body.classList.add('modal-open');
      }

      if (returnFocus?.isConnected && !returnFocus.disabled) returnFocus.focus();
      returnFocus = null;
    });

    modalInstance = window.bootstrap?.Modal
      ? window.bootstrap.Modal.getOrCreateInstance(modal, { backdrop: true, focus: true, keyboard: true })
      : null;

    return modal;
  }

  function filterModalOptions() {
    if (!modal) return;

    const query = normalizeText(modal.querySelector('#adminSinglePickerSearch').value);
    let visible = 0;

    modal.querySelectorAll('.admin-single-picker-option').forEach((button) => {
      const matches = query === '' || button.dataset.pickerSearch.includes(query);
      button.classList.toggle('d-none', !matches);
      if (matches) visible += 1;
    });

    modal.querySelector('#adminSinglePickerEmpty').classList.toggle('d-none', visible !== 0);
  }

  function chooseOption(button) {
    if (!activePicker || button.disabled) return;

    const { select } = activePicker;
    const oldValue = select.value;
    select.value = button.dataset.pickerValue;
    activePicker.refresh();

    if (select.value !== oldValue) {
      select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    modalInstance?.hide();
  }

  function renderModalOptions(picker) {
    const { select } = picker;
    const list = modal.querySelector('#adminSinglePickerList');
    const fragment = document.createDocumentFragment();

    Array.from(select.options).forEach((option) => {
      const data = optionData(option, select);
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'list-group-item list-group-item-action admin-single-picker-option';
      button.dataset.pickerValue = data.value;
      button.dataset.pickerSearch = data.searchText;
      button.disabled = data.disabled;

      if (data.selected) {
        button.classList.add('active');
        button.setAttribute('aria-current', 'true');
      }

      const content = document.createElement('span');
      content.className = 'admin-single-picker-option__content';
      const main = document.createElement('span');
      main.className = 'admin-single-picker-option__main';

      if (data.iconClasses.length) {
        const icon = document.createElement('i');
        icon.classList.add(...data.iconClasses, 'admin-single-picker-option__icon');
        icon.setAttribute('aria-hidden', 'true');
        main.appendChild(icon);
      }

      const title = document.createElement('span');
      title.className = 'admin-single-picker-option__title';
      title.textContent = data.title;
      main.appendChild(title);
      content.appendChild(main);

      if (data.subtext) {
        const subtext = document.createElement('small');
        subtext.className = 'admin-single-picker-option__subtext';
        subtext.textContent = data.subtext;
        content.appendChild(subtext);
      }

      const check = document.createElement('i');
      check.className = data.selected
        ? 'bi bi-check-circle-fill admin-single-picker-option__check'
        : 'bi bi-circle admin-single-picker-option__check';
      check.setAttribute('aria-hidden', 'true');

      button.append(content, check);
      button.addEventListener('click', () => chooseOption(button));
      fragment.appendChild(button);
    });

    list.replaceChildren(fragment);
    modal.querySelector('#adminSinglePickerEmpty').classList.toggle('d-none', select.options.length !== 0);
  }

  function openPicker(picker) {
    if (picker.select.disabled || !window.bootstrap?.Modal) return;

    ensureModal();
    activePicker = picker;
    returnFocus = picker.trigger;
    picker.trigger.setAttribute('aria-expanded', 'true');

    modal.querySelector('#adminSinglePickerModalTitle').textContent =
      picker.select.dataset.pickerTitle || 'Vybrat položku';
    const description = modal.querySelector('#adminSinglePickerModalDescription');
    description.textContent = picker.select.dataset.pickerDescription || '';
    description.classList.toggle('d-none', description.textContent === '');

    const search = modal.querySelector('#adminSinglePickerSearch');
    search.placeholder = picker.select.dataset.pickerSearchPlaceholder || 'Hledat…';
    search.value = '';
    renderModalOptions(picker);
    modalInstance.show();
  }

  function initializeSelect(select) {
    if (!window.bootstrap?.Modal || select.hasAttribute(READY_ATTRIBUTE) || select.multiple) return;

    const wrapper = document.createElement('div');
    wrapper.className = 'admin-single-picker';
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'btn btn-outline-secondary admin-single-picker__trigger';
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-controls', 'adminSinglePickerModal');

    const picker = {
      select,
      trigger,
      refresh() {
        const option = selectedOption(select);
        const data = option ? optionData(option, select) : null;
        const emptyLabel = select.dataset.pickerEmptyLabel || 'Nevybráno';
        trigger.replaceChildren(createSelectedContent(data, emptyLabel));
        trigger.disabled = select.disabled;
        trigger.classList.toggle('is-disabled', select.disabled);
        trigger.classList.toggle('is-invalid', !select.validity.valid);
        trigger.setAttribute('aria-required', select.required ? 'true' : 'false');
        trigger.setAttribute('aria-label', `${select.dataset.pickerTitle || 'Vybrat položku'}: ${data?.title || emptyLabel}`);
      },
      open() {
        openPicker(picker);
      },
    };

    pickerBySelect.set(select, picker);
    wrapper.appendChild(trigger);
    select.insertAdjacentElement('afterend', wrapper);
    select.classList.add('admin-single-picker__source');
    select.setAttribute(READY_ATTRIBUTE, 'true');
    select.tabIndex = -1;

    trigger.addEventListener('click', picker.open);
    select.addEventListener('change', picker.refresh);
    select.addEventListener('invalid', (event) => {
      event.preventDefault();
      picker.refresh();
      trigger.focus();
    });
    select.form?.addEventListener('reset', () => window.setTimeout(picker.refresh, 0));

    const observer = new MutationObserver(picker.refresh);
    observer.observe(select, {
      attributes: true,
      childList: true,
      subtree: true,
      characterData: true,
    });

    picker.refresh();
  }

  function initializeAll(root = document) {
    if (root.matches?.(SELECTOR)) initializeSelect(root);
    root.querySelectorAll?.(SELECTOR).forEach(initializeSelect);
  }

  initializeAll();

  const documentObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) initializeAll(node);
      });
    });
  });
  documentObserver.observe(document.body, { childList: true, subtree: true });

  window.QantoAdminSinglePicker = {
    refresh(select) {
      pickerBySelect.get(select)?.refresh();
    },
    refreshAll() {
      initializeAll();
      document.querySelectorAll(SELECTOR).forEach((select) => pickerBySelect.get(select)?.refresh());
    },
    open(select) {
      pickerBySelect.get(select)?.open();
    },
  };
})();
