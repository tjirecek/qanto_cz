(() => {
  const body = document.body;
  if (!body) return;

  const dataset = body.dataset;
  const basePath = (dataset.adminJsBase || '/assets/js/sec/').replace(/\/?$/, '/');
  const version = dataset.adminJsVersion || '';
  const route = [dataset.adminSection, dataset.adminPage, dataset.adminSecPage]
    .filter(Boolean)
    .join('-');

  const routeModules = {
    '01-01-02': ['lang_tabs_translate.js'],
    '01-02-02': ['lang_tabs_translate.js'],
    '01-02-03': ['lang_tabs_translate.js'],
    '01-03-01': ['galerie.js'],
    '01-03-02': ['galerie.js'],
    '01-03-03': ['galerie.js'],
    '01-03-04': ['galerie.js'],
    '01-03-05': ['galerie.js'],
    '01-03-06': ['galerie.js'],
    '01-04-06': ['kontakty_lide.js'],
    '01-04-07': ['kontakty_lide.js'],
    '02-01-01': ['rep_volna_mista.js'],
    '02-01-02': ['rep_volna_mista.js'],
    '02-01-03': ['rep_volna_mista.js'],
    '02-01-04': ['rep_volna_mista.js'],
    '02-01-05': ['rep_volna_mista.js'],
    '02-01-06': ['rep_volna_mista.js'],
    '02-01-07': ['rep_volna_mista.js'],
    '02-02-01': ['rep_akce.js'],
    '02-02-02': ['rep_akce.js'],
    '02-02-03': ['rep_akce.js'],
    '02-02-04': ['rep_akce.js'],
    '02-02-05': ['rep_akce.js'],
    '02-03-01': ['rep_bannery.js'],
    '02-03-02': ['rep_bannery.js'],
    '02-09-01': ['rep_zavoz_map.js'],
    '02-09-03': ['rep_zavoz_map.js'],
    '02-04-01': ['rep_brigadnici.js'],
    '02-04-02': ['rep_brigadnici.js'],
    '02-05-01': ['rep_ples.js'],
    '02-07-01': ['rep_tenis_qcup.js'],
    '09-02-05': ['cron_log.js'],
    '09-02-09': ['email_log.js'],
  };

  const selectorModules = [
    {
      selector: '#galeriePhotoGrid, #galeriePhotoEditModal, #galeriePhotoLightboxModal',
      file: 'galerie.js',
    },
    {
      selector: '[data-admin-translate]',
      file: 'lang_tabs_translate.js',
    },
    {
      selector: '[data-admin-filter-dropdown]',
      file: 'admin_filters.js',
    },
    {
      selector: '[data-oz-pobocka-modal]',
      file: 'oz_pobocka_modal.js',
    },
    {
      selector: '[data-rep-zavoz-map]',
      file: 'rep_zavoz_map.js',
    },
    {
      selector: '[data-rep-zavoz-okres-open]',
      file: 'rep_zavoz_map.js',
    },
    {
      selector: '[data-rep-brigadnici]',
      file: 'rep_brigadnici.js',
    },
    {
      selector: '[data-rep-volna-mista]',
      file: 'rep_volna_mista.js',
    },
    {
      selector: '[data-rep-akce]',
      file: 'rep_akce.js',
    },
    {
      selector: '[data-rep-bannery]',
      file: 'rep_bannery.js',
    },
    {
      selector: '[data-rep-ples]',
      file: 'rep_ples.js',
    },
    {
      selector: '[data-rep-tenis-qcup]',
      file: 'rep_tenis_qcup.js',
    },
    {
      selector: '.cron-log-message-btn, #cronLogMessageModal',
      file: 'cron_log.js',
    },
    {
      selector: '.email-log-detail-btn, #emailLogDetailModal',
      file: 'email_log.js',
    },
  ];

  const modules = new Set(routeModules[route] || []);

  selectorModules.forEach((item) => {
    if (document.querySelector(item.selector)) {
      modules.add(item.file);
    }
  });

  function moduleUrl(file) {
    const separator = file.includes('?') ? '&' : '?';
    return `${basePath}${file}${version ? `${separator}v=${encodeURIComponent(version)}` : ''}`;
  }

  function loadModule(file) {
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = moduleUrl(file);
      script.async = false;
      script.onload = resolve;
      script.onerror = () => reject(new Error(`Admin JS module failed: ${file}`));
      document.body.appendChild(script);
    });
  }

  modules.forEach((file) => {
    loadModule(file).catch((error) => {
      console.warn(error.message);
    });
  });
})();
