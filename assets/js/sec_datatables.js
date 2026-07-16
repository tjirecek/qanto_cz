// sec_datatables.js (universal)
(function () {
  /** @type {any} */
  const $ = window['jQuery'] || window['$'];

  if (!$ || !$.fn) return;
  if (!$.fn.DataTable) return;

  $(function () {
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
      /** @type {any[]} */
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

    function hasColumnFilters($table) {
      const attr = $table.attr('data-column-filters');
      return attr === undefined || attr !== '0';
    }

    function columnFilterPlacement($table) {
      const placement = $table.attr('data-column-filter-placement');
      return placement === 'footer' ? 'footer' : 'header';
    }

    /**
     * @param {any} api
     */
    function clearAllFilters(api) {
      // DT search
      api.search('');
      api.columns().every(function () { this.search(''); });
      api['draw']();

      // UI: vyčistit sloupcové filtry v hlavičce i patičce.
      const wrap = api.table().container();
      const header = api.table().header();
      const footer = api.table().footer();
      const filterCells = [];

      if (header) {
        filterCells.push(...$(header).find('tr:eq(1) th').toArray());
      }
      if (footer) {
        filterCells.push(...$(footer).find('tr:eq(0) th').toArray());
      }

      filterCells.forEach(function (cell) {
        const input = cell.querySelector('input');
        if (input) {
          input.value = '';
          input.removeAttribute('list');
        }

        const select = cell.querySelector('select');
        if (select) select.value = '';

        const clear = cell.querySelector('.dt-filter-clear');
        if (clear) clear.style.display = 'none';
      });

      // global search input (DT2 i DT1)
      $(wrap).find('.dt-search input[type=search], .dataTables_filter input[type=search]').each(function () {
        this.value = '';
      });
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

    /**
     * @param {any} api
     * @param {string} placement
     */
    function syncColumnFiltersFromState(api, placement) {
      const $head = $(api.table().header());
      const footer = api.table().footer();
      const $filterRow = placement === 'footer'
          ? (footer ? $(footer).find('tr:eq(0)') : $())
          : $head.find('tr:eq(1)');

      if (!$filterRow.length) return;

      api.columns().every(function () {
        /** @type {any} */
        const column = this;
        const colIdx = column.index();
        const searchValue = (column.search() || '').toString();
        if (!searchValue) return;

        const headerTh = $head.find('tr:eq(0) th').eq(colIdx);
        const filterCell = $filterRow.find('th').eq(colIdx).get(0);
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

    function createTextFilterControl() {
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

      return wrap;
    }

    function createSelectFilterControl() {
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

      return wrap;
    }

    function prepareColumnFilters($table, isServerSide, placement) {
      const $headerRow = $table.find('thead tr:eq(0)');
      const $filterRow = placement === 'footer'
          ? $table.find('tfoot tr:eq(0)')
          : $table.find('thead tr:eq(1)');

      if (!$headerRow.length || !$filterRow.length) return;

      $headerRow.children('th, td').each(function (index) {
        const headerTh = this;
        const filterCell = $filterRow.children('th, td').get(index);
        if (!filterCell) return;

        filterCell.replaceChildren();

        if (headerTh.classList.contains('no-filter')) return;

        if (headerTh.classList.contains('text-filter')) {
          filterCell.appendChild(createTextFilterControl());
          return;
        }

        if (headerTh.classList.contains('select-filter')) {
          if (!isServerSide) {
            filterCell.appendChild(createSelectFilterControl());
          }
          return;
        }

        if (!isServerSide) {
          filterCell.appendChild(createSelectFilterControl());
        }
      });
    }

    /**
     * @param {any} column
     * @param {string} value
     * @param {Object<string, any>} options
     */
    function searchColumnAndDraw(column, value, options) {
      column.search(value, options);
      column['draw']();
    }

    // ✅ DataTables feature: "R" = globální Vyčistit filtry (dom: ...R...)
    (function registerClearFiltersFeature() {
      const featureRegistry = $.fn.dataTable.ext['feature'];
      if (featureRegistry['_qantoClearFiltersRegistered']) return;
      featureRegistry['_qantoClearFiltersRegistered'] = true;

      /** @type {any} */
      const clearFiltersFeature = {};
      clearFiltersFeature['cFeature'] = 'R';
      clearFiltersFeature['fnInit'] = function (settings) {
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
      };

      featureRegistry.push(clearFiltersFeature);
    })();

    /**
     * @param {any} api
     * @param {any} $table
     * @param {string} placement
     */
    function initColumnFilters(api, $table, placement) {
      const isServerSide = api.settings()[0]['oFeatures']['bServerSide'];
      const $head = $(api.table().header());
      const footer = api.table().footer();
      const $filterRow = placement === 'footer'
          ? (footer ? $(footer).find('tr:eq(0)') : $())
          : $head.find('tr:eq(1)');

      if (!$filterRow.length) return;

      api.columns().every(function () {
        /** @type {any} */
        const column = this;
        const colIdx = column.index();

        const headerTh = $head.find('tr:eq(0) th').eq(colIdx);
        const filterCell = $filterRow.find('th').eq(colIdx).get(0);
        if (!filterCell) return;

        if (headerTh.hasClass('no-filter')) {
          filterCell.replaceChildren();
          return;
        }

        // INPUT (fulltext) + křížek
        if (headerTh.hasClass('text-filter')) {
          const input = filterCell.querySelector('input');
          const clear = filterCell.querySelector('.dt-filter-clear');
          if (!input || !clear) return;

          let dl = null;
          let listId = null;

          // autocomplete jen pro client-side (na server-side nemáme celý dataset)
          if (!isServerSide && headerTh.hasClass('dt-autocomplete')) {
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
              searchColumnAndDraw(column, '', { regex: false, smart: true, caseInsensitive: true });
              return;
            }
            if (val.length < 3) return;

            searchColumnAndDraw(column, val, { regex: false, smart: true, caseInsensitive: true });
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

            searchColumnAndDraw(column, '', { regex: false, smart: true, caseInsensitive: true });
          });

          return;
        }

        // Server-side: SELECT (exact match) nedává smysl (nemáme kompletní dataset)
        if (isServerSide) {
          filterCell.replaceChildren();
          return;
        }

        // SELECT (exact match) + křížek
        const select = filterCell.querySelector('select');
        const clear = filterCell.querySelector('.dt-filter-clear');
        if (!select || !clear) return;

        select.replaceChildren(new Option(''));

        select.addEventListener('change', function () {
          const v = this.value || '';
          clear.style.display = v ? 'block' : 'none';

          if (!v) {
            searchColumnAndDraw(column, '', { regex: false, smart: false });
            return;
          }
          searchColumnAndDraw(column, '^' + escapeRegex(v) + '$', { regex: true, smart: false });
        });

        clear.addEventListener('click', function () {
          select.value = '';
          clear.style.display = 'none';
          searchColumnAndDraw(column, '', { regex: false, smart: false });
        });

        column.data().unique().sort().each(function (d) {
          const txt = $('<div>').html(d ?? '').text();
          select.add(new Option(txt, txt));
        });
      });
    }

    function initOneTable($table) {
      if ($.fn.DataTable.isDataTable($table[0])) return;

      // --- server-side řízené atributy ---
      const ajaxUrl = $table.data('ajax');
      const isServerSide = String($table.data('server-side') || '') === '1' || !!ajaxUrl;
      const keepStateFilters = String($table.data('state-keep-filters') || '') === '1';
      const columnFiltersEnabled = hasColumnFilters($table);
      const filterPlacement = columnFilterPlacement($table);

      if (columnFiltersEnabled) {
        if (filterPlacement === 'header') {
          moveFooterFiltersToHeader($table);
        }
        prepareColumnFilters($table, isServerSide, filterPlacement);
      } else {
        $table.find('tfoot').remove();
      }

      const columnDefs = buildColumnDefs($table);

      const order = $table.data('order') || [[0, 'desc']];
      const pageLength = $table.data('page-length') || 100;

      /** @type {any} */
      const dtCfg = {
        stateSave: true,
        titleRow: 0,
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
        language: window.QANTO_DT_LANG || {}
      };

      dtCfg['stateSaveCallback'] = function (settings, data) {
        data._version = STATE_VERSION;
        localStorage.setItem(stateStorageKey(settings, $table), JSON.stringify(data));
      };

      dtCfg['stateLoadCallback'] = function (settings) {
        const v = localStorage.getItem(stateStorageKey(settings, $table));
        if (!v) return null;

        let state;
        try { state = JSON.parse(v); } catch (e) { return null; }

        if (state._version !== STATE_VERSION) return null;

        const currentColumnCount = $table.find('thead tr:eq(0) th').length;
        if (!Array.isArray(state.columns) || state.columns.length !== currentColumnCount) {
          localStorage.removeItem(stateStorageKey(settings, $table));
          return null;
        }

        if (!keepStateFilters) {
          // Většina secure výpisů má po návratu začínat s čistými filtry.
          state.search = { search: '', smart: true, regex: false };
          if (state.columns) {
            state.columns.forEach(col => col.search = { search: '', smart: true, regex: false });
          }
          if (state.searchBuilder) state.searchBuilder = {};
        }
        return state;
      };

      dtCfg['initComplete'] = function () {
        /** @type {any} */
        const dtContext = this;
        const api = dtContext['api']();
        if (columnFiltersEnabled) {
          initColumnFilters(api, $table, filterPlacement);
        }
        if (columnFiltersEnabled && keepStateFilters) {
          syncColumnFiltersFromState(api, filterPlacement);
        }

        // bootstrapize horní panel (DT2 i DT1)
        const $wrap = $table.closest('.dataTables_wrapper');
        $wrap.find('.dt-length select, .dataTables_length select')
            .addClass('form-select form-select-sm d-inline-block w-auto');
        $wrap.find('.dt-search input[type=search], .dataTables_filter input[type=search]')
            .addClass('form-control form-control-sm')
            .css('max-width', '240px');
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

    function initAllTables() {
      $('table.js-datatable, table#dataTable, table#DataTable').each(function () {
        initOneTable($(this));
      });
    }

    function initAllTablesWhenLayoutIsStable() {
      const hasTinyMceEditors = document.querySelector('textarea.js-tinymce') !== null;

      if (!hasTinyMceEditors || window.QANTO_TINYMCE_READY === true) {
        initAllTables();
        return;
      }

      let initialized = false;
      const runOnce = () => {
        if (initialized) return;
        initialized = true;
        initAllTables();
      };

      window.addEventListener('qanto:tinymce-ready', runOnce, { once: true });
      setTimeout(runOnce, 3000);
    }

    initAllTablesWhenLayoutIsStable();
  });
}());
