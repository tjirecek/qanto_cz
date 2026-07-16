(() => {
  const dropdowns = document.querySelectorAll('[data-admin-filter-dropdown]');

  if (!dropdowns.length) {
    return;
  }

  const normalize = (value) => String(value || '').toLocaleLowerCase('cs-CZ').trim();

  dropdowns.forEach((dropdown) => {
    const search = dropdown.querySelector('[data-admin-filter-search]');
    const options = dropdown.querySelector('[data-admin-filter-options]');

    if (!search || !options) {
      return;
    }

    let empty = options.querySelector('[data-admin-filter-empty]');
    if (!empty) {
      empty = document.createElement('div');
      empty.className = 'admin-filter-empty d-none';
      empty.dataset.adminFilterEmpty = '1';
      empty.textContent = 'Žádné položky nenalezeny.';
      options.appendChild(empty);
    }

    const items = Array.from(options.querySelectorAll('[data-admin-filter-item]'));

    const applySearch = () => {
      const query = normalize(search.value);
      let visibleCount = 0;

      items.forEach((item) => {
        const haystack = normalize(item.dataset.adminFilterText || item.textContent);
        const isVisible = query === '' || haystack.includes(query);

        item.classList.toggle('d-none', !isVisible);
        if (isVisible) {
          visibleCount += 1;
        }
      });

      empty.classList.toggle('d-none', visibleCount > 0);
    };

    search.addEventListener('input', applySearch);

    dropdown.addEventListener('shown.bs.dropdown', () => {
      search.focus();
      search.select();
      applySearch();
    });
  });
})();
