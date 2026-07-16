(() => {
  const containers = document.querySelectorAll('[data-rep-zavoz-map]');
  const statusButtons = document.querySelectorAll('[data-rep-zavoz-status-open]');
  const okresButtons = document.querySelectorAll('[data-rep-zavoz-okres-open]');
  const tableFilterButtons = document.querySelectorAll('[data-rep-zavoz-table-filter]');
  if (!containers.length && !statusButtons.length && !okresButtons.length && !tableFilterButtons.length) return;

  const STATUS_CONFIG = {
    served: { label: 'Obsluhujeme', color: '#f28b82', stroke: '#b02a37' },
    not_served: { label: 'Neobsluhujeme', color: '#6c757d', stroke: '#343a40' },
    excluded: { label: 'Vyloučeno', color: '#0d6efd', stroke: '#084298' },
    review: { label: 'Ke kontrole', color: '#f0ad4e', stroke: '#8a6d1d' },
  };

  const body = document.body;
  const adminBasePath = body?.dataset?.adminJsBase || '/assets/js/sec/';
  const adminBase = new URL(adminBasePath, window.location.origin);
  const version = body?.dataset?.adminJsVersion || '';
  const leafletCss = new URL('../../lib/leaflet/leaflet.css', adminBase).toString();
  const leafletJs = new URL('../../lib/leaflet/leaflet.js', adminBase).toString();
  const mapViewStorageKey = `rep-zavoz-map-view:${window.location.pathname}:${window.location.search}`;
  const adminMapProvider = body?.dataset?.adminMapProvider || 'osm';
  const adminMapyApiKey = body?.dataset?.adminMapyApiKey || '';
  const adminMapyMapset = body?.dataset?.adminMapyMapset || 'basic';
  const adminMapyTileSize = body?.dataset?.adminMapyTileSize || '256@2x';

  function versioned(url) {
    if (!version) return url;
    const separator = url.includes('?') ? '&' : '?';
    return `${url}${separator}v=${encodeURIComponent(version)}`;
  }

  function ensureStylesheet(url) {
    return new Promise((resolve, reject) => {
      const existing = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
        .find((link) => link.href === url);
      if (existing) {
        resolve();
        return;
      }

      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = url;
      link.onload = resolve;
      link.onerror = () => reject(new Error(`Stylesheet failed: ${url}`));
      document.head.appendChild(link);
    });
  }

  function ensureScript(url) {
    if (window.L) return Promise.resolve(window.L);

    return new Promise((resolve, reject) => {
      const existing = Array.from(document.querySelectorAll('script[src]'))
        .find((script) => script.src === url);
      if (existing) {
        existing.addEventListener('load', () => {
          if (window.L) {
            resolve(window.L);
          } else {
            reject(new Error(`Leaflet unavailable after load: ${url}`));
          }
        }, { once: true });
        existing.addEventListener('error', () => reject(new Error(`Script failed: ${url}`)), { once: true });
        return;
      }

      const script = document.createElement('script');
      script.src = url;
      script.async = false;
      script.onload = () => {
        if (window.L) {
          resolve(window.L);
        } else {
          reject(new Error(`Leaflet unavailable after load: ${url}`));
        }
      };
      script.onerror = () => reject(new Error(`Script failed: ${url}`));
      document.body.appendChild(script);
    });
  }

  function normalizeStatus(status) {
    return STATUS_CONFIG[status] ? status : 'review';
  }

  function readStoredMapView() {
    try {
      const parsed = JSON.parse(window.sessionStorage.getItem(mapViewStorageKey) || '{}');
      window.sessionStorage.removeItem(mapViewStorageKey);

      if (!Number.isFinite(parsed.lat) || !Number.isFinite(parsed.lng) || !Number.isFinite(parsed.zoom)) {
        return null;
      }

      return {
        lat: parsed.lat,
        lng: parsed.lng,
        zoom: parsed.zoom,
        filter: String(parsed.filter || 'all'),
      };
    } catch (error) {
      return null;
    }
  }

  function saveMapView(container) {
    const map = container?.repZavozMap?.map;
    if (!map) return;

    const center = map.getCenter();
    try {
      window.sessionStorage.setItem(mapViewStorageKey, JSON.stringify({
        lat: center.lat,
        lng: center.lng,
        zoom: map.getZoom(),
        filter: currentFilter(),
      }));
    } catch (error) {
      // sessionStorage can be unavailable in restrictive browser modes.
    }
  }

  function parsePoints(container) {
    try {
      const parsed = JSON.parse(container.dataset.points || '[]');
      if (!Array.isArray(parsed)) return [];

      return parsed
        .map((point) => ({
          id: Number(point.id) || 0,
          zavozId: Number(point.zavozId) || 0,
          name: String(point.name || ''),
          okres: String(point.okres || ''),
          kraj: String(point.kraj || ''),
          psc: String(point.psc || ''),
          lat: Number(point.lat),
          lng: Number(point.lng),
          prodej: Number(point.prodej) || 0,
          status: normalizeStatus(String(point.status || 'not_served')),
        }))
        .filter((point) => point.id > 0 && Number.isFinite(point.lat) && Number.isFinite(point.lng));
    } catch (error) {
      return [];
    }
  }

  function parseAreas(container) {
    try {
      const parsed = JSON.parse(container.dataset.areas || '{"type":"FeatureCollection","features":[]}');
      const features = Array.isArray(parsed?.features) ? parsed.features : [];

      return features
        .map((feature) => {
          const properties = feature.properties || {};
          return {
            type: 'Feature',
            geometry: feature.geometry,
            properties: {
              id: Number(properties.id) || 0,
              zavozId: Number(properties.zavozId) || 0,
              name: String(properties.name || ''),
              okres: String(properties.okres || ''),
              kraj: String(properties.kraj || ''),
              psc: String(properties.psc || ''),
              prodej: Number(properties.prodej) || 0,
              status: normalizeStatus(String(properties.status || 'review')),
            },
          };
        })
        .filter((feature) => feature.properties.id > 0 && feature.geometry && feature.geometry.type);
    } catch (error) {
      return [];
    }
  }

  function currentFilter() {
    const active = document.querySelector('[data-rep-zavoz-map-filter].active');
    return active?.getAttribute('data-rep-zavoz-map-filter') || 'all';
  }

  function setActiveMapFilter(filter) {
    document.querySelectorAll('[data-rep-zavoz-map-filter]').forEach((item) => {
      item.classList.toggle('active', (item.getAttribute('data-rep-zavoz-map-filter') || 'all') === filter);
    });
  }

  function filterPoints(points, filter) {
    if (!filter || filter === 'all') return points;
    return points.filter((point) => point.status === filter);
  }

  function filterAreas(areas, filter) {
    if (!filter || filter === 'all') return areas;
    if (filter === 'not_served') return [];
    return areas.filter((feature) => feature.properties?.status === filter);
  }

  function escapeRegex(value) {
    return String(value || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function normalizeSearch(value) {
    return String(value || '')
      .toLocaleLowerCase('cs-CZ')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
  }

  function showEmpty(container, text) {
    container.replaceChildren();
    const empty = document.createElement('div');
    empty.className = 'alert alert-light border mb-0';
    empty.textContent = text;
    container.appendChild(empty);
  }

  function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = value;
    return element.innerHTML;
  }

  function markerRadius() {
    return 4;
  }

  function usesMapyTiles() {
    return adminMapProvider === 'mapy' && adminMapyApiKey !== '';
  }

  function createBaseTileLayer(L) {
    if (usesMapyTiles()) {
      const mapset = encodeURIComponent(adminMapyMapset || 'basic');
      const tileSize = encodeURIComponent(adminMapyTileSize || '256@2x');
      const apiKey = encodeURIComponent(adminMapyApiKey);

      return L.tileLayer(`https://api.mapy.com/v1/maptiles/${mapset}/${tileSize}/{z}/{x}/{y}?apikey=${apiKey}&lang=cs`, {
        maxZoom: 20,
        attribution: '&copy; <a href="https://mapy.com/" target="_blank" rel="noopener">Mapy.com</a> &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
      });
    }

    return L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; OpenStreetMap contributors',
    });
  }

  function addMapyLogo(L, map) {
    if (!usesMapyTiles()) return;

    const MapyLogoControl = L.Control.extend({
      options: {
        position: 'bottomleft',
      },
      onAdd: () => {
        const link = L.DomUtil.create('a', 'sec-rep-mapy-logo');
        link.href = 'https://mapy.com/';
        link.target = '_blank';
        link.rel = 'noopener';
        link.textContent = 'Mapy.com';
        link.setAttribute('aria-label', 'Mapy.com');
        return link;
      },
    });

    map.addControl(new MapyLogoControl());
  }

  function areaStyle(feature) {
    const status = normalizeStatus(String(feature.properties?.status || 'review'));
    const config = STATUS_CONFIG[status] || STATUS_CONFIG.review;

    return {
      color: config.stroke,
      weight: status === 'excluded' ? 2 : 1,
      opacity: 0.9,
      fillColor: config.color,
      fillOpacity: status === 'served' ? 0.18 : 0.24,
    };
  }

  function pointLabel(point) {
    const status = STATUS_CONFIG[point.status] || STATUS_CONFIG.review;
    const meta = [point.okres, point.kraj].filter(Boolean).join(' | ');
    return `<strong>${escapeHtml(point.name || 'Obec')}</strong><br>` +
      `${escapeHtml(status.label)}${meta ? `<br>${escapeHtml(meta)}` : ''}<br>` +
      `Prodej: ${point.prodej.toLocaleString('cs-CZ')}`;
  }

  function fillStatusModal(modal, data) {
    const title = modal.querySelector('[data-rep-zavoz-modal-title]');
    const meta = modal.querySelector('[data-rep-zavoz-modal-meta]');
    const sales = modal.querySelector('[data-rep-zavoz-modal-sales]');
    const obecId = modal.querySelector('[data-rep-zavoz-modal-obec-id]');
    const status = modal.querySelector('[data-rep-zavoz-modal-status]');

    if (title) title.textContent = data.name || 'Obec';
    if (meta) meta.textContent = data.meta || '';
    if (sales) sales.textContent = data.sales || '';
    if (obecId) obecId.value = String(data.id || '');
    if (status) status.value = normalizeStatus(data.status || 'review');
  }

  function openStatusModalBySelector(selector, data) {
    if (!selector || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;

    const modal = document.querySelector(selector);
    if (!modal) return;

    fillStatusModal(modal, data);
    bootstrap.Modal.getOrCreateInstance(modal).show();
  }

  function openStatusModal(container, point) {
    const statusConfig = STATUS_CONFIG[point.status] || STATUS_CONFIG.review;

    openStatusModalBySelector(container.getAttribute('data-edit-modal'), {
      id: point.id,
      name: point.name,
      meta: [point.okres, point.kraj, point.psc ? `PSČ ${point.psc}` : ''].filter(Boolean).join(' | '),
      sales: `Aktuální stav: ${statusConfig.label} | Prodej: ${point.prodej.toLocaleString('cs-CZ')}`,
      status: point.status,
    });
  }

  function openOkresModal(button) {
    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) return;

    const modal = document.querySelector('#repZavozOkresModal');
    if (!modal) return;

    const title = modal.querySelector('[data-rep-zavoz-okres-modal-title]');
    const meta = modal.querySelector('[data-rep-zavoz-okres-modal-meta]');
    const okresId = modal.querySelector('[data-rep-zavoz-okres-modal-id]');
    const oz = modal.querySelector('[data-rep-zavoz-okres-modal-oz]');
    const ozLabel = modal.querySelector('[data-rep-zavoz-okres-modal-oz-label]');
    const note = modal.querySelector('[data-rep-zavoz-okres-modal-note]');
    const search = modal.querySelector('[data-rep-zavoz-okres-oz-search]');
    const choices = Array.from(modal.querySelectorAll('[data-rep-zavoz-okres-oz-choice]'));
    const selectedOzId = button.getAttribute('data-obchodni-zastupce-id') || '';

    if (title) title.textContent = button.getAttribute('data-name') || 'Okres';
    if (meta) meta.textContent = button.getAttribute('data-meta') || '';
    if (okresId) okresId.value = button.getAttribute('data-okres-id') || '';
    if (oz) oz.value = selectedOzId;
    if (ozLabel) {
      const selectedChoice = choices.find((choice) => (choice.getAttribute('data-oz-id') || '') === selectedOzId);
      ozLabel.textContent = selectedChoice?.getAttribute('data-oz-label') || 'bez kontaktu';
    }
    if (note) note.value = button.getAttribute('data-note') || '';
    if (search) search.value = '';
    choices.forEach((choice) => {
      choice.classList.remove('d-none');
      choice.classList.toggle('table-active', (choice.getAttribute('data-oz-id') || '') === selectedOzId);
    });

    const modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
    modal.addEventListener('shown.bs.modal', () => {
      if (search) search.focus();
    }, { once: true });
    modalInstance.show();
  }

  function filterOkresOzChoices(modal) {
    const search = modal.querySelector('[data-rep-zavoz-okres-oz-search]');
    const empty = modal.querySelector('[data-rep-zavoz-okres-oz-empty]');
    const needle = normalizeSearch(search?.value || '');
    let visible = 0;

    modal.querySelectorAll('[data-rep-zavoz-okres-oz-choice]').forEach((choice) => {
      const matches = needle === '' || normalizeSearch(choice.textContent).includes(needle);
      choice.classList.toggle('d-none', !matches);
      if (matches) visible += 1;
    });

    if (empty) empty.classList.toggle('d-none', visible !== 0);
  }

  function selectOkresOzChoice(button) {
    const modal = button.closest('#repZavozOkresModal');
    if (!modal) return;

    const idInput = modal.querySelector('[data-rep-zavoz-okres-modal-oz]');
    const label = modal.querySelector('[data-rep-zavoz-okres-modal-oz-label]');
    const ozId = button.getAttribute('data-oz-id') || '';
    const ozLabel = button.getAttribute('data-oz-label') || 'bez kontaktu';

    if (idInput) idInput.value = ozId;
    if (label) label.textContent = ozLabel;

    modal.querySelectorAll('[data-rep-zavoz-okres-oz-choice]').forEach((choice) => {
      choice.classList.toggle('table-active', choice === button);
    });
  }

  function drawSvgFallback(container, points) {
    container.replaceChildren();

    const filteredPoints = filterPoints(points, currentFilter());
    if (!filteredPoints.length) {
      showEmpty(container, 'Pro vybraný filtr nejsou dostupné žádné obce se souřadnicemi.');
      return;
    }

    const width = 900;
    const height = 520;
    const padding = 28;
    const lats = filteredPoints.map((point) => point.lat);
    const lngs = filteredPoints.map((point) => point.lng);
    const minLat = Math.min(...lats);
    const maxLat = Math.max(...lats);
    const minLng = Math.min(...lngs);
    const maxLng = Math.max(...lngs);

    const x = (lng) => {
      if (maxLng === minLng) return width / 2;
      return padding + ((lng - minLng) / (maxLng - minLng)) * (width - padding * 2);
    };
    const y = (lat) => {
      if (maxLat === minLat) return height / 2;
      return padding + ((maxLat - lat) / (maxLat - minLat)) * (height - padding * 2);
    };

    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
    svg.setAttribute('role', 'img');
    svg.setAttribute('aria-label', 'Mapový náhled obcí');
    svg.classList.add('w-100', 'border', 'rounded', 'bg-light');

    filteredPoints.forEach((point) => {
      const config = STATUS_CONFIG[point.status] || STATUS_CONFIG.review;
      const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      circle.setAttribute('cx', String(x(point.lng)));
      circle.setAttribute('cy', String(y(point.lat)));
      circle.setAttribute('r', String(markerRadius()));
      circle.setAttribute('fill', config.color);
      circle.setAttribute('fill-opacity', point.status === 'not_served' ? '0.32' : '0.58');
      circle.setAttribute('stroke', config.stroke);
      circle.setAttribute('stroke-width', '1');
      circle.classList.add('cursor-pointer');
      circle.addEventListener('click', () => openStatusModal(container, point));

      const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
      title.textContent = `${point.name || 'Obec'} | ${config.label} | prodej ${point.prodej.toLocaleString('cs-CZ')}`;
      circle.appendChild(title);
      svg.appendChild(circle);
    });

    container.appendChild(svg);
  }

  function initLeafletMap(container, points) {
    const L = window.L;
    container.replaceChildren();

    const mapElement = document.createElement('div');
    mapElement.className = 'w-100 h-100';
    container.appendChild(mapElement);

    const map = L.map(mapElement, {
      preferCanvas: true,
      scrollWheelZoom: true,
    });

    createBaseTileLayer(L).addTo(map);
    addMapyLogo(L, map);

    container.repZavozMap = {
      map,
      areaLayer: L.layerGroup().addTo(map),
      pointLayer: L.layerGroup().addTo(map),
      points,
      areas: parseAreas(container),
    };

    const storedView = readStoredMapView();
    if (storedView) setActiveMapFilter(storedView.filter);

    renderLeafletAreas(container, currentFilter());
    renderLeafletPoints(container, currentFilter(), { fitBounds: !storedView });
    if (storedView) map.setView([storedView.lat, storedView.lng], storedView.zoom);
    setTimeout(() => map.invalidateSize(), 0);
  }

  function renderLeafletAreas(container, filter) {
    const state = container.repZavozMap;
    if (!state) return;

    const { areaLayer, areas } = state;
    const filteredAreas = filterAreas(areas, filter);
    areaLayer.clearLayers();

    if (!filteredAreas.length) return;

    window.L.geoJSON(filteredAreas, {
      style: areaStyle,
      onEachFeature: (feature, layer) => {
        const point = feature.properties || {};
        layer.bindPopup(pointLabel(point));
        layer.on('click', () => openStatusModal(container, point));
      },
    }).addTo(areaLayer);
  }

  function renderLeafletPoints(container, filter, options = {}) {
    const state = container.repZavozMap;
    if (!state) return;

    const { map, pointLayer, points } = state;
    const filteredPoints = filterPoints(points, filter);
    const bounds = [];
    pointLayer.clearLayers();

    filteredPoints.forEach((point) => {
      const config = STATUS_CONFIG[point.status] || STATUS_CONFIG.review;
      const marker = window.L.circleMarker([point.lat, point.lng], {
        radius: markerRadius(),
        color: config.stroke,
        weight: 1,
        fillColor: config.color,
        fillOpacity: point.status === 'not_served' ? 0.28 : 0.52,
      });

      marker.bindPopup(pointLabel(point));
      marker.on('click', () => openStatusModal(container, point));
      marker.addTo(pointLayer);
      bounds.push([point.lat, point.lng]);
    });

    if (!options.fitBounds) {
      return;
    }

    if (bounds.length === 1) {
      map.setView(bounds[0], 10);
    } else if (bounds.length > 1) {
      map.fitBounds(bounds, { padding: [24, 24] });
    }
  }

  statusButtons.forEach((button) => {
    button.addEventListener('click', () => {
      openStatusModalBySelector('#repZavozMapStatusModal', {
        id: button.getAttribute('data-obec-id') || '',
        name: button.getAttribute('data-name') || 'Obec',
        meta: button.getAttribute('data-meta') || '',
        sales: button.getAttribute('data-sales') || '',
        status: button.getAttribute('data-status') || 'review',
      });
    });
  });

  okresButtons.forEach((button) => {
    button.addEventListener('click', () => openOkresModal(button));
  });

  document.querySelectorAll('#repZavozOkresModal').forEach((modal) => {
    modal.addEventListener('click', (event) => {
      const button = event.target.closest('[data-rep-zavoz-okres-oz-choice]');
      if (button) selectOkresOzChoice(button);
    });

    const search = modal.querySelector('[data-rep-zavoz-okres-oz-search]');
    if (search) search.addEventListener('input', () => filterOkresOzChoices(modal));
  });

  document.querySelectorAll('#repZavozMapStatusModal form').forEach((form) => {
    form.addEventListener('submit', () => {
      const mapContainer = Array.from(containers).find((container) => container.repZavozMap);
      saveMapView(mapContainer);
    });
  });

  const pointsByContainer = new Map();
  if (containers.length) {
    containers.forEach((container) => {
      const points = parsePoints(container);
      pointsByContainer.set(container, points);

      if (!points.length) {
        showEmpty(container, 'Mapový náhled zatím nemá žádné obce se souřadnicemi.');
      } else {
        showEmpty(container, 'Načítám mapový podklad…');
      }
    });

    Promise.all([
      ensureStylesheet(versioned(leafletCss)),
      ensureScript(versioned(leafletJs)),
    ]).then(() => {
      containers.forEach((container) => {
        const points = pointsByContainer.get(container) || [];
        if (!points.length) return;

        try {
          initLeafletMap(container, points);
        } catch (error) {
          drawSvgFallback(container, points);
        }
      });
    }).catch(() => {
      containers.forEach((container) => {
        const points = pointsByContainer.get(container) || [];
        if (points.length) drawSvgFallback(container, points);
      });
    });
  }

  document.querySelectorAll('[data-rep-zavoz-map-filter]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-rep-zavoz-map-filter]').forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      const filter = button.getAttribute('data-rep-zavoz-map-filter') || 'all';

      containers.forEach((container) => {
        if (container.repZavozMap) {
          renderLeafletAreas(container, filter);
          renderLeafletPoints(container, filter);
          return;
        }

        const points = pointsByContainer.get(container) || [];
        if (points.length) drawSvgFallback(container, points);
      });
    });
  });

  tableFilterButtons.forEach((button) => {
    button.addEventListener('click', () => {
      tableFilterButtons.forEach((item) => item.classList.remove('active'));
      button.classList.add('active');

      const filter = button.getAttribute('data-rep-zavoz-table-filter') || 'all';
      const label = STATUS_CONFIG[filter]?.label || '';
      const table = document.getElementById('DataTableRepZavozObce');

      if (table && window.jQuery && window.jQuery.fn?.DataTable?.isDataTable(table)) {
        const api = window.jQuery(table).DataTable();
        const statusColumn = api.column(0);
        const search = filter === 'all' ? '' : `^${escapeRegex(label)}$`;

        statusColumn.search(search, { regex: filter !== 'all', smart: false });
        api.draw();

        const select = table.querySelector('thead tr:nth-child(2) th:first-child select');
        if (select) select.value = filter === 'all' ? '' : label;
        return;
      }

      if (containers.length) {
        document.querySelectorAll('[data-rep-zavoz-map-filter]').forEach((item) => {
          item.classList.toggle('active', (item.getAttribute('data-rep-zavoz-map-filter') || 'all') === filter);
        });
        containers.forEach((container) => {
          if (container.repZavozMap) {
            renderLeafletAreas(container, filter);
            renderLeafletPoints(container, filter);
          }
        });
      }
    });
  });
})();
