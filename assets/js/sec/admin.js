(() => {
  const body = document.body;
  if (!body) return;

  const dataset = body.dataset;
  const basePath = (dataset.adminJsBase || '/assets/js/sec/').replace(/\/?$/, '/');
  const version = dataset.adminJsVersion || '';
  const route = [dataset.adminSection, dataset.adminPage, dataset.adminSecPage]
    .filter(Boolean)
    .join('-');
  const projectModules = window.QANTO_ADMIN_PROJECT_MODULES || {};
  const projectRouteModules = projectModules.routeModules && typeof projectModules.routeModules === 'object'
    ? projectModules.routeModules
    : {};
  const projectSelectorModules = Array.isArray(projectModules.selectorModules)
    ? projectModules.selectorModules
    : [];

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
    '09-02-05': ['cron_log.js'],
    '09-02-09': ['email_log.js'],
    ...projectRouteModules,
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
      selector: '.js-admin-single-picker',
      file: 'admin_single_picker.js',
    },
    {
      selector: '.cron-log-message-btn, #cronLogMessageModal',
      file: 'cron_log.js',
    },
    {
      selector: '.email-log-detail-btn, #emailLogDetailModal',
      file: 'email_log.js',
    },
    ...projectSelectorModules,
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
