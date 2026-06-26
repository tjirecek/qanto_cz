// sec_datatables.js (universal)
$(function () {
  if (!$.fn.DataTable) return;

  const STATE_VERSION = 'v7';

  const stripDiacritics = (s) => (s ?? '')
      .toString()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');

  const escapeRegex = (s) => (s ?? '').toString().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

  // diakritika-insensitive vyhledávání (global i column)
  $.fn.dataTable.ext.type.search.string = function (data) {
    const text = $('<div>').html(data ?? '').text();
    return stripDiacritics(text).toLowerCase();
  };

  const debounce = (fn, wait) => {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
  };

  function buildColumnDefs($table) {
    const columnDefs = [{ targets: 'no-sort', orderable: false }];

    $table.find('thead tr:eq(0) th').each(function (index) {
      const th = $(this);

      // date sort/display (dd.mm.yyyy)
      if (th.attr('data-type') === 'date') {
        columnDefs.push({
          targets: index,
          orderable: true,
          render: function (data, type) {
            if (!data) return data;

            const m = String(data).trim().match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/);
            if (!m) return data;

            const dd = m[1].padStart(2, '0');
            const mm = m[2].padStart(2, '0');
            const yyyy = m[3];

            if (type === 'sort' || type === 'type') return `${yyyy}${mm}${dd}`;
            if (type === 'display' || type === 'filter') return `${dd}.${mm}.${yyyy}`;
            return data;
          }
        });
      }

      // optional: bool render (0/1) if you add class="bool01" to <th>
      if (th.hasClass('bool01')) {
        columnDefs.push({
          targets: index,
          className: 'text-center',
          render: function (data) {
            return parseInt(data, 10) === 1
                ? '<span class="text-success fw-bold">ANO</span>'
                : '<span class="text-muted">NE</span>';
          }
        });
      }
    });

    return columnDefs;
  }

  function moveFooterFiltersToHeader($table) {
    if ($table.find('tfoot tr').length) {
      $table.find('thead').append($table.find('tfoot tr'));
      $table.find('tfoot').remove();
    }
  }

  function clearAllFilters(api) {
    // DT search
    api.search('');
    api.columns().every(function () { this.search(''); });
    api.draw();

    // UI: vyčistit druhý řádek hlavičky
    const head = api.table().header();
    const $head = $(head);
    if ($head.find('tr').length >= 2) {
      $head.find('tr:eq(1) th').each(function () {
        const input = this.querySelector('input');
        if (input) {
          input.value = '';
          input.removeAttribute('list');
        }

        const select = this.querySelector('select');
        if (select) select.value = '';

        const clear = this.querySelector('.dt-filter-clear');
        if (clear) clear.style.display = 'none';
      });
    }

    // global search input (DT2 i DT1)
    const wrap = api.table().container();
    $(wrap).find('.dt-search input[type=search], .dataTables_filter input[type=search]').val('');
  }

  function stateStorageKey(settings, $table) {
    const explicitKey = ($table.data('state-key') || '').toString().trim();
    return 'DataTables_' + (explicitKey || settings.sInstance);
  }

  function unescapeExactSearchValue(searchValue) {
    const value = (searchValue || '').toString();
    if (value.length < 2 || value[0] !== '^' || value[value.length - 1] !== '$') {
      return value;
    }

    return value
        .slice(1, -1)
        .replace(/\\([.*+?^${}()|[\]\\])/g, '$1');
  }

  function syncHeaderFiltersFromState(api) {
    const $head = $(api.table().header());
    if ($head.find('tr').length < 2) return;

    api.columns().every(function () {
      const column = this;
      const colIdx = column.index();
      const searchValue = (column.search() || '').toString();
      if (!searchValue) return;

      const headerTh = $head.find('tr:eq(0) th').eq(colIdx);
      const filterCell = $head.find('tr:eq(1) th').eq(colIdx).get(0);
      if (!filterCell || headerTh.hasClass('no-filter')) return;

      const input = filterCell.querySelector('input');
      if (input) {
        input.value = searchValue;
        const clear = filterCell.querySelector('.dt-filter-clear');
        if (clear) clear.style.display = 'block';
        return;
      }

      const select = filterCell.querySelector('select');
      if (select) {
        select.value = unescapeExactSearchValue(searchValue);
        const clear = filterCell.querySelector('.dt-filter-clear');
        if (clear) clear.style.display = select.value ? 'block' : 'none';
      }
    });
  }

  // ✅ DataTables feature: "R" = globální Vyčistit filtry (dom: ...R...)
  (function registerClearFiltersFeature() {
    if ($.fn.dataTable.ext.feature._qantoClearFiltersRegistered) return;
    $.fn.dataTable.ext.feature._qantoClearFiltersRegistered = true;

    $.fn.dataTable.ext.feature.push({
      cFeature: 'R',
      fnInit: function (settings) {
        const api = new $.fn.dataTable.Api(settings);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-outline-secondary dt-clear-all';
        btn.innerHTML = '<i class="bi bi-x-circle me-1"></i> Vyčistit filtry';

        btn.addEventListener('click', function () {
          clearAllFilters(api);
        });

        const wrap = document.createElement('div');
        wrap.className = 'dt-clear-wrap text-center';
        wrap.appendChild(btn);

        return wrap;
      }
    });
  })();

  function initHeaderFilters(api, $table) {
    const isServerSide = api.settings()[0].oFeatures.bServerSide;
    const $head = $(api.table().header());
    if ($head.find('tr').length < 2) return;

    api.columns().every(function () {
      const column = this;
      const colIdx = column.index();

      const headerTh = $head.find('tr:eq(0) th').eq(colIdx);
      const filterCell = $head.find('tr:eq(1) th').eq(colIdx).get(0);
      if (!filterCell) return;

      if (headerTh.hasClass('no-filter')) {
        filterCell.replaceChildren();
        return;
      }

      // INPUT (fulltext) + křížek
      if (headerTh.hasClass('text-filter')) {
        const wrap = document.createElement('div');
        wrap.className = 'dt-filter-wrap';

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control form-control-sm';
        input.placeholder = 'Hledat…';

        const clear = document.createElement('span');
        clear.className = 'dt-filter-clear';
        clear.innerHTML = '&times;';
        clear.style.display = 'none';

        wrap.appendChild(input);
        wrap.appendChild(clear);
        filterCell.replaceChildren(wrap);

        let dl = null;
        let listId = null;

        // autocomplete jen pro client-side (na server-side nemáme celý dataset)
        if (!isServerSide && headerTh.hasClass('autocomplete')) {
          listId = 'dl_' + ($table.attr('id') || 'dt') + '_' + colIdx;
          dl = document.createElement('datalist');
          dl.id = listId;
          document.body.appendChild(dl);
        }

        const fillDatalist = () => {
          if (!dl) return;

          dl.replaceChildren();
          const q = (input.value || '').trim();
          if (q.length < 3) return;

          const seen = new Set();
          let count = 0;
          const LIMIT = 50;

          const qnorm = stripDiacritics(q).toLowerCase();

          column.data().each(function (d) {
            const txt = $('<div>').html(d ?? '').text().trim();
            if (!txt) return;

            const norm = stripDiacritics(txt).toLowerCase();
            if (!norm.includes(qnorm)) return;

            if (seen.has(norm)) return;
            seen.add(norm);

            dl.appendChild(new Option(txt, txt));
            if (++count >= LIMIT) return false;
          });
        };

        const doSearch = debounce(() => {
          const val = (input.value || '').trim();
          clear.style.display = val.length ? 'block' : 'none';

          if (dl) {
            if (val.length >= 3) {
              input.setAttribute('list', listId);
              fillDatalist();
            } else {
              input.removeAttribute('list');
              dl.replaceChildren();
            }
          }

          if (val.length === 0) {
            column.search('', { regex: false, smart: true, caseInsensitive: true }).draw();
            return;
          }
          if (val.length < 3) return;

          column.search(val, { regex: false, smart: true, caseInsensitive: true }).draw();
        }, 200);

        input.addEventListener('keyup', doSearch);
        input.addEventListener('change', doSearch);
        input.addEventListener('focus', () => {
          const v = (input.value || '').trim();
          if (dl && v.length < 3) input.removeAttribute('list');
        });

        clear.addEventListener('click', () => {
          input.value = '';
          clear.style.display = 'none';

          if (dl) {
            input.removeAttribute('list');
            dl.replaceChildren();
          }

          column.search('', { regex: false, smart: true, caseInsensitive: true }).draw();
        });

        return;
      }

      // Server-side: SELECT (exact match) nedává smysl (nemáme kompletní dataset)
      if (isServerSide) {
        filterCell.replaceChildren();
        return;
      }

      // SELECT (exact match) + křížek
      const wrap = document.createElement('div');
      wrap.className = 'dt-filter-wrap';

      const select = document.createElement('select');
      select.className = 'form-select form-select-sm';
      select.add(new Option(''));

      const clear = document.createElement('span');
      clear.className = 'dt-filter-clear';
      clear.innerHTML = '&times;';
      clear.style.display = 'none';

      wrap.appendChild(select);
      wrap.appendChild(clear);
      filterCell.replaceChildren(wrap);

      select.addEventListener('change', function () {
        const v = this.value || '';
        clear.style.display = v ? 'block' : 'none';

        if (!v) {
          column.search('', { regex: false, smart: false }).draw();
          return;
        }
        column.search('^' + escapeRegex(v) + '$', { regex: true, smart: false }).draw();
      });

      clear.addEventListener('click', function () {
        select.value = '';
        clear.style.display = 'none';
        column.search('', { regex: false, smart: false }).draw();
      });

      column.data().unique().sort().each(function (d) {
        const txt = $('<div>').html(d ?? '').text();
        select.add(new Option(txt, txt));
      });
    });
  }

  function initOneTable($table) {
    if ($.fn.DataTable.isDataTable($table[0])) return;

    moveFooterFiltersToHeader($table);

    const columnDefs = buildColumnDefs($table);

    // --- server-side řízené atributy ---
    const ajaxUrl = $table.data('ajax');
    const isServerSide = String($table.data('server-side') || '') === '1' || !!ajaxUrl;
    const keepStateFilters = String($table.data('state-keep-filters') || '') === '1';

    const order = $table.data('order') || [[0, 'desc']];
    const pageLength = $table.data('page-length') || 100;

    const dtCfg = {
      stateSave: true,

      stateSaveCallback: function (settings, data) {
        data._version = STATE_VERSION;
        localStorage.setItem(stateStorageKey(settings, $table), JSON.stringify(data));
      },

      stateLoadCallback: function (settings) {
        const v = localStorage.getItem(stateStorageKey(settings, $table));
        if (!v) return null;

        let state;
        try { state = JSON.parse(v); } catch (e) { return null; }

        if (state._version !== STATE_VERSION) return null;

        if (!keepStateFilters) {
          // Většina secure výpisů má po návratu začínat s čistými filtry.
          state.search = { search: '', smart: true, regex: false };
          if (state.columns) {
            state.columns.forEach(col => col.search = { search: '', smart: true, regex: false });
          }
          if (state.searchBuilder) state.searchBuilder = {};
        }
        return state;
      },

      orderCellsTop: true,
      columnDefs,
      order,
      pageLength,

      // ✅ prostřední sloupec je feature "R" (Vyčistit filtry)
      dom:
          "<'row align-items-center g-2 mb-2'<'col-12 col-lg-3'l><'col-12 col-lg-3 text-lg-center'R><'col-12 col-lg-6'f>>" +
          "<'row'<'col-12'tr>>" +
          "<'row align-items-center g-2 mt-2'<'col-md-5'i><'col-md-7'p>>",

      lengthMenu: [[25, 50, 100, 500, -1], [25, 50, 100, 500, "Vše"]],
      lengthChange: true,
      language: window.QANTO_DT_LANG || {},

      initComplete: function () {
        const api = this.api();
        initHeaderFilters(api, $table);
        if (keepStateFilters) {
          syncHeaderFiltersFromState(api);
        }

        // bootstrapize horní panel (DT2 i DT1)
        const $wrap = $table.closest('.dataTables_wrapper');
        $wrap.find('.dt-length select, .dataTables_length select')
            .addClass('form-select form-select-sm d-inline-block w-auto');
        $wrap.find('.dt-search input[type=search], .dataTables_filter input[type=search]')
            .addClass('form-control form-control-sm')
            .css('max-width', '240px');
      }
    };

    if (isServerSide) {
      if (!ajaxUrl) {
        console.warn('DataTable server-side: chybí data-ajax URL pro tabulku', $table.attr('id'));
      } else {
        dtCfg.processing = true;
        dtCfg.serverSide = true;
        dtCfg.searchDelay = 250;
        dtCfg.ajax = { url: ajaxUrl, type: 'GET' };
      }
    }

    $table.DataTable(dtCfg);
  }

  // init všechny tabulky
  $('table.js-datatable, table#dataTable, table#DataTable').each(function () {
    initOneTable($(this));
  });
});
