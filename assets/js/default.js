(() => {
  const header = document.querySelector('[data-site-header]');
  const scrollTop = document.querySelector('[data-scroll-top]');
  const adCarousels = document.querySelectorAll('[data-ad-carousel]');
  const flyerSections = document.querySelectorAll('[data-home-flyers]');
  const akceViewers = document.querySelectorAll('[data-akce-public-viewer]');
  const careerBranchMaps = document.querySelectorAll('[data-career-branch-map]');
  const marketsMaps = document.querySelectorAll('[data-markets-map]');
  const wholesaleMaps = document.querySelectorAll('[data-wholesale-map]');
  const wholesaleAvailabilityForms = document.querySelectorAll('[data-wholesale-availability]');
  const wholesaleBranchFilters = document.querySelectorAll('[data-wholesale-branch-filter]');
  const marketGalleries = document.querySelectorAll('[data-market-gallery]');
  const contactSelects = document.querySelectorAll('[data-contact-select]');
  const brigadaForms = document.querySelectorAll('[data-brigada-form]');
  const libBase = '/assets/lib/';

  const loadScript = (src, isReady) => new Promise((resolve, reject) => {
    if (isReady && isReady()) {
      resolve();
      return;
    }

    const existing = document.querySelector(`script[src="${src}"]`);
    if (existing) {
      existing.addEventListener('load', resolve, { once: true });
      existing.addEventListener('error', reject, { once: true });
      return;
    }

    const script = document.createElement('script');
    script.src = src;
    script.async = true;
    script.onload = resolve;
    script.onerror = () => reject(new Error(`Failed to load ${src}`));
    document.body.appendChild(script);
  });

  const loadStyle = (href) => {
    if (document.querySelector(`link[href="${href}"]`)) {
      return;
    }
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
  };

  const parseViewerPages = (viewer) => {
    try {
      const pages = JSON.parse(viewer.dataset.pages || '[]');
      return Array.isArray(pages) ? pages.filter((page) => page && page.src) : [];
    } catch (error) {
      console.warn('Invalid flyer viewer data.', error);
      return [];
    }
  };

  const setViewerButtonState = (viewer, currentPage, totalPages) => {
    viewer.querySelectorAll('[data-akce-viewer-action="first"], [data-akce-viewer-action="prev"]').forEach((button) => {
      button.disabled = currentPage <= 0;
    });
    viewer.querySelectorAll('[data-akce-viewer-action="next"], [data-akce-viewer-action="last"]').forEach((button) => {
      button.disabled = currentPage >= totalPages - 1;
    });
  };

  const updateViewerThumb = (viewer, pageIndex) => {
    viewer.querySelectorAll('[data-akce-viewer-thumb]').forEach((button) => {
      button.classList.toggle('is-active', Number(button.dataset.pageIndex) === pageIndex);
    });
  };

  const clampNumber = (value, min, max) => Math.max(min, Math.min(max, value));

  const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

  const normalizeFilterText = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();

  const parseCareerMapPoints = (container) => {
    try {
      const points = JSON.parse(container.dataset.points || '[]');
      return Array.isArray(points)
        ? points.filter((point) => Number.isFinite(Number(point.lat)) && Number.isFinite(Number(point.lon)))
        : [];
    } catch (error) {
      console.warn('Invalid career map data.', error);
      return [];
    }
  };

  const careerMapIcon = (point) => {
    const jobsCount = Number(point.jobs_count) || 0;
    const hasJobs = jobsCount > 0;
    const size = hasJobs ? 28 : 20;

    return window.L.divIcon({
      className: 'career-map-pin-wrapper',
      html: `<span class="career-map-pin${hasJobs ? ' career-map-pin--jobs' : ''}">${hasJobs ? escapeHtml(jobsCount) : ''}</span>`,
      iconSize: [size, size],
      iconAnchor: [size / 2, size / 2],
      popupAnchor: [0, -size / 2],
    });
  };

  const careerMapPopupHtml = (container, point) => {
    const jobsCount = Number(point.jobs_count) || 0;
    const branchLabel = container.dataset.labelBranch || '';
    const jobsLabel = container.dataset.labelJobs || '';
    const title = escapeHtml(point.title || branchLabel);
    const address = point.address ? `<p>${escapeHtml(point.address)}</p>` : '';
    const jobs = jobsCount > 0 ? `<strong>${escapeHtml(jobsLabel)}: ${escapeHtml(jobsCount)}</strong>` : '';

    return `<div class="career-map-popup"><h3>${title}</h3>${address}${jobs}</div>`;
  };

  const normalizeCareerCity = (value) => String(value || '').trim();

  const parseMarketsMapPoints = (container) => {
    try {
      const points = JSON.parse(container.dataset.points || '[]');
      return Array.isArray(points)
        ? points.filter((point) => Number.isFinite(Number(point.lat)) && Number.isFinite(Number(point.lon)))
        : [];
    } catch (error) {
      console.warn('Invalid markets map data.', error);
      return [];
    }
  };

  const parseJsonElement = (root, selector, fallback) => {
    const element = root ? root.querySelector(selector) : null;
    if (!element) {
      return fallback;
    }

    try {
      return JSON.parse(element.textContent || '');
    } catch (error) {
      console.warn('Invalid JSON data.', error);
      return fallback;
    }
  };

  const normalizeMarketsCity = (value) => String(value || '').trim();

  const qantoMapConfig = window.qantoMapConfig || {};

  const qantoUsesMapy = () => {
    return qantoMapConfig.provider === 'mapy' && qantoMapConfig.mapy && qantoMapConfig.mapy.apiKey;
  };

  const qantoMapTileLayer = () => {
    if (qantoUsesMapy()) {
      const mapy = qantoMapConfig.mapy || {};
      const mapset = encodeURIComponent(mapy.mapset || 'basic');
      const tileSize = encodeURIComponent(mapy.tileSize || '256@2x');
      const apiKey = encodeURIComponent(mapy.apiKey);
      const lang = mapy.lang ? `&lang=${encodeURIComponent(mapy.lang)}` : '';

      return window.L.tileLayer(`https://api.mapy.com/v1/maptiles/${mapset}/${tileSize}/{z}/{x}/{y}?apikey=${apiKey}${lang}`, {
        maxZoom: 20,
        attribution: '&copy; <a href="https://mapy.com/" target="_blank" rel="noopener">Mapy.com</a> &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
      });
    }

    return window.L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
      maxZoom: 19,
      subdomains: 'abcd',
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions" target="_blank" rel="noopener">CARTO</a>',
    });
  };

  const addQantoMapyLogo = (map) => {
    if (!qantoUsesMapy() || !window.L || !map) {
      return;
    }

    const MapyLogoControl = window.L.Control.extend({
      options: {
        position: 'bottomleft',
      },
      onAdd: () => {
        const link = window.L.DomUtil.create('a', 'qanto-mapy-logo');
        link.href = 'https://mapy.com/';
        link.target = '_blank';
        link.rel = 'noopener';
        link.textContent = 'Mapy.com';
        link.setAttribute('aria-label', 'Mapy.com');
        return link;
      },
    });

    map.addControl(new MapyLogoControl());
  };

  const addMapResetControl = (map, label, onReset) => {
    if (!window.L || !map || !label || typeof onReset !== 'function') {
      return;
    }

    const ResetControl = window.L.Control.extend({
      options: {
        position: 'topright',
      },
      onAdd: () => {
        const button = window.L.DomUtil.create('button', 'qanto-map-reset');
        button.type = 'button';
        button.textContent = label;
        button.setAttribute('aria-label', label);
        window.L.DomEvent.disableClickPropagation(button);
        window.L.DomEvent.disableScrollPropagation(button);
        window.L.DomEvent.on(button, 'click', (event) => {
          window.L.DomEvent.preventDefault(event);
          onReset();
        });
        return button;
      },
    });

    map.addControl(new ResetControl());
  };

  const marketsMapIcon = () => window.L.divIcon({
    className: 'markets-map-pin-wrapper',
    html: '<span class="markets-map-pin"></span>',
    iconSize: [26, 34],
    iconAnchor: [13, 34],
    popupAnchor: [0, -30],
  });

  const marketsMapPopupHtml = (container, point) => {
    const branchLabel = container.dataset.labelBranch || '';
    const title = escapeHtml(point.title || branchLabel);
    const address = point.address ? `<p>${escapeHtml(point.address)}</p>` : '';
    const opening = point.opening ? `<strong>${escapeHtml(point.opening)}</strong>` : '';

    return `<div class="markets-map-popup"><h3>${title}</h3>${address}${opening}</div>`;
  };

  const wholesaleBranchIcon = () => window.L.divIcon({
    className: 'wholesale-map-pin-wrapper',
    html: '<span class="wholesale-map-pin"></span>',
    iconSize: [32, 32],
    iconAnchor: [16, 16],
    popupAnchor: [0, -16],
  });

  const wholesaleBranchPopupHtml = (container, point) => {
    const branchLabel = container.dataset.labelBranch || '';
    const title = escapeHtml(point.title || branchLabel);
    const address = point.address ? `<p>${escapeHtml(point.address)}</p>` : '';

    return `<div class="markets-map-popup"><h3>${title}</h3>${address}</div>`;
  };

  const wholesaleAreaPopupHtml = (properties) => {
    const name = escapeHtml(properties.name || '');
    const district = properties.okres ? `<p>${escapeHtml(properties.okres)} · ${escapeHtml(properties.kraj || '')}</p>` : '';
    const psc = properties.psc ? `<strong>PSČ: ${escapeHtml(properties.psc)}</strong>` : '';

    return `<div class="markets-map-popup"><h3>${name}</h3>${district}${psc}</div>`;
  };

  const initWholesaleMap = (container) => {
    const areas = parseJsonElement(container, '[data-wholesale-map-areas]', { type: 'FeatureCollection', features: [] });
    const branches = parseJsonElement(container, '[data-wholesale-map-branches]', []);
    const section = container.closest('.wholesale-finder');
    const mobileToggle = section ? section.querySelector('[data-wholesale-mobile-toggle]') : null;
    const mobileToggleLabel = mobileToggle ? mobileToggle.querySelector('[data-wholesale-mobile-toggle-label]') : null;
    const branchCards = section ? Array.from(section.querySelectorAll('[data-wholesale-branch-card]')) : [];
    const features = Array.isArray(areas.features) ? areas.features : [];
    const branchPoints = Array.isArray(branches)
      ? branches.filter((point) => Number.isFinite(Number(point.lat)) && Number.isFinite(Number(point.lon)))
      : [];

    const setActiveBranch = (branchId, emit = false) => {
      branchCards.forEach((card) => {
        card.classList.toggle('is-active', String(card.dataset.branchId || '') === String(branchId || ''));
      });

      if (emit) {
        document.dispatchEvent(new CustomEvent('qanto:wholesale-branch-selected', {
          detail: { branchId: String(branchId || ''), source: 'map' },
        }));
      }
    };

    container.replaceChildren();

    if ((!features.length && !branchPoints.length) || !window.L) {
      const empty = document.createElement('div');
      empty.className = 'markets-map__empty';
      empty.textContent = container.dataset.empty || '';
      container.appendChild(empty);
      return;
    }

    const mapElement = document.createElement('div');
    mapElement.className = 'wholesale-map__leaflet';
    container.appendChild(mapElement);

    const map = window.L.map(mapElement, {
      scrollWheelZoom: true,
      zoomControl: true,
      zoomSnap: 0.25,
      zoomDelta: 0.5,
    });

    qantoMapTileLayer().addTo(map);
    addQantoMapyLogo(map);

    let highlightedArea = null;
    const areaDefaultStyle = {
      color: '#c42d26',
      weight: 1,
      opacity: 0.5,
      fillColor: '#c42d26',
      fillOpacity: 0.16,
    };
    const areaHighlightStyle = {
      color: '#006eb8',
      weight: 3,
      opacity: 0.95,
      fillColor: '#2f7ed8',
      fillOpacity: 0.28,
    };

    const clearHighlightedArea = () => {
      if (highlightedArea && areaLayer) {
        areaLayer.resetStyle(highlightedArea);
      }
      highlightedArea = null;
    };

    const areaLayer = features.length
      ? window.L.geoJSON(areas, {
        style: () => areaDefaultStyle,
        onEachFeature: (feature, layer) => {
          const properties = feature && feature.properties ? feature.properties : {};
          layer.bindPopup(wholesaleAreaPopupHtml(properties), { autoPan: false });
          layer.on('mouseover', () => {
            layer.setStyle({
              weight: 2,
              opacity: 0.82,
              fillOpacity: 0.24,
            });
          });
          layer.on('mouseout', () => {
            if (layer === highlightedArea) {
              layer.setStyle(areaHighlightStyle);
              return;
            }
            areaLayer.resetStyle(layer);
          });
        },
      }).addTo(map)
      : null;

    const branchMarkers = branchPoints.map((point) => {
      const marker = window.L.marker([Number(point.lat), Number(point.lon)], {
        icon: wholesaleBranchIcon(),
        zIndexOffset: 1000,
      });
      marker.bindPopup(wholesaleBranchPopupHtml(container, point), { autoPan: false });
      marker.on('click', () => {
        setActiveBranch(point.id, true);
        clearHighlightedArea();
        const lat = Number(point.lat);
        const lon = Number(point.lon);
        map.setView([lat, lon], Math.max(Math.min(map.getZoom(), 8.6), 8.2), { animate: true });
      });
      marker.addTo(map);
      return { point, marker };
    });

    const boundsItems = [];
    if (areaLayer) {
      boundsItems.push(areaLayer);
    }
    if (branchMarkers.length) {
      boundsItems.push(...branchMarkers.map((item) => item.marker));
    }

    const fitDefaultView = (animate = false, emit = false) => {
      map.closePopup();
      setActiveBranch('', emit);
      clearHighlightedArea();
      if (!boundsItems.length) {
        map.setView([49.85, 15.5], 7.25, { animate });
        return;
      }
      const group = window.L.featureGroup(boundsItems);
      map.fitBounds(group.getBounds().pad(0.01), { maxZoom: 8.25, animate });
      const currentZoom = Number(map.getZoom());
      if (Number.isFinite(currentZoom)) {
        map.setZoom(Math.min(currentZoom + 0.35, 8.25), { animate });
      }
    };

    fitDefaultView(false);
    addMapResetControl(map, container.dataset.labelReset || '', () => fitDefaultView(true, true));

    if (section) {
      const setMobileMapVisible = (visible) => {
        if (!mobileToggle) {
          return;
        }

        section.classList.toggle('is-map-visible', visible);
        mobileToggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
        if (mobileToggleLabel) {
          mobileToggleLabel.textContent = visible
            ? (mobileToggle.dataset.labelList || '')
            : (mobileToggle.dataset.labelMap || '');
        }

        if (visible) {
          window.setTimeout(() => {
            map.invalidateSize();
            fitDefaultView(true);
            window.setTimeout(() => {
              map.invalidateSize();
              fitDefaultView(true);
            }, 120);
          }, 80);
        }
      };

      if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
          setMobileMapVisible(!section.classList.contains('is-map-visible'));
        });
      }

      document.addEventListener('qanto:wholesale-branch-selected', (event) => {
        if (event.detail && event.detail.source === 'map') {
          return;
        }

        const branchId = String((event.detail && event.detail.branchId) || '');
        if (branchId === '') {
          fitDefaultView(true);
          return;
        }

        const item = branchMarkers.find((markerItem) => String(markerItem.point.id || '') === branchId);
        setActiveBranch(branchId);
        clearHighlightedArea();
        if (!item) {
          return;
        }

        const lat = Number(item.point.lat);
        const lon = Number(item.point.lon);
        map.setView([lat, lon], Math.max(Math.min(map.getZoom(), 8.6), 8.2), { animate: true });
        item.marker.openPopup();
      });

      section.addEventListener('qanto:wholesale-place-selected', (event) => {
        const placeId = Number(event.detail && event.detail.id);
        if (!placeId || !areaLayer) {
          clearHighlightedArea();
          return;
        }

        let matchedLayer = null;
        areaLayer.eachLayer((layer) => {
          const featureId = Number(layer.feature && layer.feature.properties && layer.feature.properties.id);
          if (featureId === placeId) {
            matchedLayer = layer;
          }
        });

        clearHighlightedArea();
        if (!matchedLayer) {
          return;
        }

        highlightedArea = matchedLayer;
        highlightedArea.setStyle(areaHighlightStyle);
        if (typeof highlightedArea.bringToFront === 'function') {
          highlightedArea.bringToFront();
        }
        map.fitBounds(highlightedArea.getBounds().pad(0.9), { maxZoom: 9, animate: true });
        highlightedArea.openPopup();
      });

      section.querySelectorAll('[data-wholesale-branch-focus]').forEach((button) => {
        button.addEventListener('click', () => {
          const branchId = String(button.dataset.wholesaleBranchFocus || '');
          const item = branchMarkers.find((markerItem) => String(markerItem.point.id || '') === branchId);
          setActiveBranch(branchId, true);
          clearHighlightedArea();
          if (!item) {
            return;
          }
          const lat = Number(item.point.lat);
          const lon = Number(item.point.lon);
          map.setView([lat, lon], Math.max(Math.min(map.getZoom(), 8.6), 8.2), { animate: true });
          item.marker.openPopup();
        });
      });
    }

    window.setTimeout(() => map.invalidateSize(), 0);
  };

  const initMarketsMap = (container) => {
    const points = parseMarketsMapPoints(container);
    const section = container.closest('.markets-finder');
    const cityTrigger = section ? section.querySelector('[data-markets-city-trigger]') : null;
    const cityLabel = section ? section.querySelector('[data-markets-city-label]') : null;
    const cityPanel = section ? section.querySelector('[data-markets-city-panel]') : null;
    const mobileToggle = section ? section.querySelector('[data-markets-mobile-toggle]') : null;
    const mobileToggleLabel = mobileToggle ? mobileToggle.querySelector('[data-markets-mobile-toggle-label]') : null;
    const marketCards = section ? Array.from(section.querySelectorAll('[data-market-card]')) : [];
    const empty = section ? section.querySelector('[data-markets-empty]') : null;
    const allCitiesLabel = container.dataset.labelAllCities || '';
    let selectedCity = '';
    let citySearchTerm = '';
    let citySearchInput = null;
    let citySearchEmpty = null;

    container.replaceChildren();

    if (!points.length || !window.L) {
      const mapEmpty = document.createElement('div');
      mapEmpty.className = 'markets-map__empty';
      mapEmpty.textContent = container.dataset.empty || '';
      container.appendChild(mapEmpty);
      return;
    }

    const mapElement = document.createElement('div');
    mapElement.className = 'markets-map__leaflet';
    container.appendChild(mapElement);

    const map = window.L.map(mapElement, {
      scrollWheelZoom: true,
      zoomControl: true,
      zoomSnap: 0.25,
      zoomDelta: 0.5,
    });

    qantoMapTileLayer().addTo(map);
    addQantoMapyLogo(map);

    const markerItems = points.map((point) => {
      const marker = window.L.marker([Number(point.lat), Number(point.lon)], {
        icon: marketsMapIcon(),
        zIndexOffset: 500,
      });
      marker.bindPopup(marketsMapPopupHtml(container, point), { autoPan: false });
      marker.on('click', () => {
        const card = marketCards.find((item) => Number(item.dataset.marketId) === Number(point.id));
        if (card) {
          card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
          marketCards.forEach((item) => item.classList.toggle('is-active', item === card));
        }
        map.setView([Number(point.lat), Number(point.lon)], Math.max(Math.min(map.getZoom(), 11), 10), { animate: true });
      });
      return { point, marker };
    });

    const cityOptions = Array.from(new Set([
      ...points.map((point) => normalizeMarketsCity(point.city)).filter(Boolean),
      ...marketCards.map((card) => normalizeMarketsCity(card.dataset.city)).filter(Boolean),
    ])).sort((a, b) => a.localeCompare(b, 'cs', { sensitivity: 'base' }));

    const closeCityPanel = () => {
      if (!cityPanel || !cityTrigger) {
        return;
      }
      cityPanel.hidden = true;
      cityTrigger.setAttribute('aria-expanded', 'false');
      citySearchTerm = '';
      if (citySearchInput) {
        citySearchInput.value = '';
      }
      filterCityOptions();
    };

    const filterCityOptions = () => {
      if (!cityPanel) {
        return;
      }

      const query = normalizeFilterText(citySearchTerm);
      let visibleCount = 0;
      cityPanel.querySelectorAll('[data-markets-city-option]').forEach((button) => {
        const matchesSearch = !query || normalizeFilterText(button.textContent).includes(query);
        button.hidden = !matchesSearch;
        if (matchesSearch) {
          visibleCount += 1;
        }
      });

      if (citySearchEmpty) {
        citySearchEmpty.hidden = visibleCount > 0;
      }
    };

    const setCityOptionsState = () => {
      if (cityLabel) {
        cityLabel.textContent = selectedCity || allCitiesLabel;
      }

      if (!cityPanel) {
        return;
      }

      cityPanel.querySelectorAll('[data-markets-city-option]').forEach((button) => {
        const isActive = normalizeMarketsCity(button.dataset.city) === selectedCity;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
      filterCityOptions();
    };

    const visibleMarkerItems = () => markerItems.filter(({ point }) => {
      return !selectedCity || normalizeMarketsCity(point.city) === selectedCity;
    });

    const renderMarkers = (fitBounds = true) => {
      markerItems.forEach(({ marker }) => {
        if (map.hasLayer(marker)) {
          map.removeLayer(marker);
        }
      });

      const visibleItems = visibleMarkerItems();
      visibleItems.forEach(({ marker }) => marker.addTo(map));

      if (fitBounds) {
        if (visibleItems.length) {
          if (visibleItems.length === 1) {
            const { point } = visibleItems[0];
            map.setView([Number(point.lat), Number(point.lon)], selectedCity ? 11 : 10.5, { animate: true });
          } else {
            const group = window.L.featureGroup(visibleItems.map(({ marker }) => marker));
            map.fitBounds(group.getBounds().pad(selectedCity ? 0.12 : 0.08), { maxZoom: selectedCity ? 11 : 8.75 });
            if (!selectedCity) {
              const currentZoom = Number(map.getZoom());
              if (Number.isFinite(currentZoom)) {
                map.setZoom(Math.min(currentZoom + 0.25, 8.75), { animate: true });
              }
            }
          }
        } else {
          map.setView([49.85, 15.5], 7.25);
        }
      }
    };

    const renderCards = () => {
      let visibleCount = 0;
      marketCards.forEach((card) => {
        const matchesCity = !selectedCity || normalizeMarketsCity(card.dataset.city) === selectedCity;
        card.hidden = !matchesCity;
        card.classList.remove('is-active');
        if (matchesCity) {
          visibleCount += 1;
        }
      });

      if (empty) {
        empty.hidden = visibleCount > 0;
      }
    };

    const render = (fitBounds = true) => {
      setCityOptionsState();
      renderMarkers(fitBounds);
      renderCards();
    };

    const setMobileMapVisible = (visible) => {
      if (!section || !mobileToggle) {
        return;
      }

      section.classList.toggle('is-map-visible', visible);
      mobileToggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
      if (mobileToggleLabel) {
        mobileToggleLabel.textContent = visible
          ? (mobileToggle.dataset.labelList || '')
          : (mobileToggle.dataset.labelMap || '');
      }

      if (visible) {
        window.setTimeout(() => {
          map.invalidateSize();
          render(true);
          window.setTimeout(() => {
            map.invalidateSize();
            render(true);
          }, 120);
        }, 80);
      }
    };

    if (cityPanel) {
      const options = [{ value: '', label: allCitiesLabel }, ...cityOptions.map((city) => ({ value: city, label: city }))];
      const searchWrap = document.createElement('div');
      searchWrap.className = 'markets-city__search';

      citySearchInput = document.createElement('input');
      citySearchInput.type = 'search';
      citySearchInput.autocomplete = 'off';
      citySearchInput.placeholder = cityPanel.dataset.searchPlaceholder || '';
      citySearchInput.setAttribute('aria-label', cityPanel.dataset.searchPlaceholder || '');
      searchWrap.appendChild(citySearchInput);

      const optionsList = document.createElement('div');
      optionsList.className = 'markets-city__options';
      optionsList.setAttribute('role', 'listbox');
      optionsList.append(...options.map((option) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'markets-city__option';
        button.dataset.marketsCityOption = '';
        button.dataset.city = option.value;
        button.setAttribute('role', 'option');
        button.textContent = option.label;
        return button;
      }));

      citySearchEmpty = document.createElement('div');
      citySearchEmpty.className = 'markets-city__empty';
      citySearchEmpty.textContent = cityPanel.dataset.searchEmpty || '';
      citySearchEmpty.hidden = true;

      cityPanel.replaceChildren(searchWrap, optionsList, citySearchEmpty);

      citySearchInput.addEventListener('input', () => {
        citySearchTerm = citySearchInput ? citySearchInput.value : '';
        filterCityOptions();
      });

      citySearchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          const firstVisibleOption = cityPanel.querySelector('[data-markets-city-option]:not([hidden])');
          if (firstVisibleOption) {
            event.preventDefault();
            firstVisibleOption.click();
          }
        } else if (event.key === 'Escape') {
          event.preventDefault();
          closeCityPanel();
          cityTrigger?.focus();
        }
      });

      cityPanel.addEventListener('click', (event) => {
        const option = event.target.closest('[data-markets-city-option]');
        if (!option) {
          return;
        }
        selectedCity = normalizeMarketsCity(option.dataset.city);
        closeCityPanel();
        render(true);
      });
    }

    if (cityTrigger && cityPanel) {
      cityTrigger.addEventListener('click', () => {
        const shouldOpen = cityPanel.hidden;
        cityPanel.hidden = !shouldOpen;
        cityTrigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        if (shouldOpen && citySearchInput) {
          window.setTimeout(() => citySearchInput?.focus(), 0);
        }
      });

      document.addEventListener('click', (event) => {
        if (section && !section.contains(event.target)) {
          closeCityPanel();
        }
      });
    }

    if (section) {
      if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
          setMobileMapVisible(!section.classList.contains('is-map-visible'));
        });
      }

      addMapResetControl(map, allCitiesLabel, () => {
        selectedCity = '';
        map.closePopup();
        marketCards.forEach((card) => card.classList.remove('is-active'));
        render(true);
      });

      section.querySelectorAll('[data-market-focus]').forEach((button) => {
        button.addEventListener('click', () => {
          const id = Number(button.dataset.marketFocus) || 0;
          const item = markerItems.find(({ point }) => Number(point.id) === id);
          if (!item) {
            return;
          }
          marketCards.forEach((card) => card.classList.toggle('is-active', Number(card.dataset.marketId) === id));
          map.setView([Number(item.point.lat), Number(item.point.lon)], Math.max(Math.min(map.getZoom(), 11), 10), { animate: true });
          item.marker.openPopup();
        });
      });
    }

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeCityPanel();
      }
    });

    render(true);
    window.setTimeout(() => map.invalidateSize(), 0);
  };

  const initCareerBranchMap = (container) => {
    const points = parseCareerMapPoints(container);
    const mapRoot = container.closest('.career-map');
    const careerSection = container.closest('.career-jobs');
    const mobileToggle = careerSection ? careerSection.querySelector('[data-career-mobile-toggle]') : null;
    const mobileToggleLabel = mobileToggle ? mobileToggle.querySelector('[data-career-mobile-toggle-label]') : null;
    const cityPicker = careerSection
      ? careerSection.querySelector('[data-career-map-city-picker]')
      : (mapRoot ? mapRoot.querySelector('[data-career-map-city-picker]') : null);
    const cityTrigger = cityPicker ? cityPicker.querySelector('[data-career-map-city-trigger]') : null;
    const cityLabel = cityPicker ? cityPicker.querySelector('[data-career-map-city-label]') : null;
    const cityPanel = cityPicker ? cityPicker.querySelector('[data-career-map-city-panel]') : null;
    const jobsOnlyButton = mapRoot ? mapRoot.querySelector('[data-career-map-jobs-only]') : null;
    const resetButton = mapRoot ? mapRoot.querySelector('[data-career-map-reset]') : null;
    const filterEmpty = mapRoot ? mapRoot.querySelector('[data-career-map-filter-empty]') : null;
    const jobCards = careerSection ? Array.from(careerSection.querySelectorAll('[data-career-job-card]')) : [];
    const jobsEmpty = careerSection ? careerSection.querySelector('[data-career-jobs-empty]') : null;
    const allCitiesLabel = container.dataset.labelAllCities || '';
    let selectedCity = '';
    let selectedStredisko = '';
    let jobsOnly = false;
    let citySearchTerm = '';
    let citySearchInput = null;
    let citySearchEmpty = null;

    container.replaceChildren();

    if (!points.length || !window.L) {
      const empty = document.createElement('div');
      empty.className = 'career-map__empty';
      empty.textContent = container.dataset.empty || '';
      container.appendChild(empty);
      return;
    }

    const mapElement = document.createElement('div');
    mapElement.className = 'career-map__leaflet';
    container.appendChild(mapElement);

    const map = window.L.map(mapElement, {
      scrollWheelZoom: true,
      zoomControl: true,
    });

    qantoMapTileLayer().addTo(map);
    addQantoMapyLogo(map);

    const markerItems = points.map((point) => {
      const marker = window.L.marker([Number(point.lat), Number(point.lon)], {
        icon: careerMapIcon(point),
        zIndexOffset: (Number(point.jobs_count) || 0) > 0 ? 1000 : 0,
      });
      marker.bindPopup(careerMapPopupHtml(container, point));
      return { point, marker };
    });

    const pointCityOptions = points
      .filter((point) => (Number(point.jobs_count) || 0) > 0)
      .map((point) => normalizeCareerCity(point.city))
      .filter(Boolean);
    const jobCardCityOptions = jobCards
      .map((card) => normalizeCareerCity(card.dataset.city))
      .filter(Boolean);
    const cityOptions = Array.from(new Set([...pointCityOptions, ...jobCardCityOptions]))
      .sort((a, b) => a.localeCompare(b, 'cs', { sensitivity: 'base' }));

    const closeCityPanel = () => {
      if (!cityPanel || !cityTrigger) {
        return;
      }
      cityPanel.hidden = true;
      cityTrigger.setAttribute('aria-expanded', 'false');
      citySearchTerm = '';
      if (citySearchInput) {
        citySearchInput.value = '';
        filterCareerCityOptions();
      }
    };

    const filterCareerCityOptions = () => {
      if (!cityPanel) {
        return;
      }

      const query = normalizeFilterText(citySearchTerm);
      let visibleCount = 0;
      cityPanel.querySelectorAll('[data-career-map-city-option]').forEach((button) => {
        const text = normalizeFilterText(button.textContent || '');
        const isVisible = !query || text.includes(query);
        button.hidden = !isVisible;
        if (isVisible) {
          visibleCount += 1;
        }
      });

      if (citySearchEmpty) {
        citySearchEmpty.hidden = visibleCount > 0;
      }
    };

    const setCityOptionsState = () => {
      if (cityLabel) {
        cityLabel.textContent = selectedCity || allCitiesLabel;
      }

      if (resetButton) {
        resetButton.hidden = selectedCity === '' && selectedStredisko === '';
      }

      if (!cityPanel) {
        return;
      }

      cityPanel.querySelectorAll('[data-career-map-city-option]').forEach((button) => {
        const isActive = normalizeCareerCity(button.dataset.city) === selectedCity;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
    };

    const getVisibleItems = () => markerItems.filter(({ point }) => {
      const matchesCity = !selectedCity || normalizeCareerCity(point.city) === selectedCity;
      const matchesJobs = !jobsOnly || (Number(point.jobs_count) || 0) > 0;
      return matchesCity && matchesJobs;
    });

    const renderMarkers = (fitBounds = true) => {
      markerItems.forEach(({ marker }) => {
        if (map.hasLayer(marker)) {
          map.removeLayer(marker);
        }
      });

      const visibleItems = getVisibleItems();
      visibleItems.forEach(({ marker }) => marker.addTo(map));

      if (filterEmpty) {
        filterEmpty.hidden = visibleItems.length > 0;
      }

      if (!fitBounds) {
        return;
      }

      if (visibleItems.length) {
        const group = window.L.featureGroup(visibleItems.map(({ marker }) => marker));
        map.fitBounds(group.getBounds().pad(0.16), { maxZoom: selectedCity ? 12 : 9 });
      } else {
        map.setView([49.85, 15.5], 7);
      }
    };

    const renderJobCards = () => {
      if (!jobCards.length) {
        return;
      }

      let visibleCount = 0;
      jobCards.forEach((card) => {
        const matchesBranch = !selectedStredisko || String(card.dataset.stredisko || '') === selectedStredisko;
        const matchesCity = !selectedCity || normalizeCareerCity(card.dataset.city) === selectedCity;
        const isVisible = matchesBranch && matchesCity;
        card.hidden = !isVisible;
        if (isVisible) {
          visibleCount += 1;
        }
      });

      if (jobsEmpty) {
        jobsEmpty.hidden = visibleCount > 0;
      }
    };

    const renderCareerFilter = (fitBounds = true) => {
      setCityOptionsState();
      renderMarkers(fitBounds);
      renderJobCards();
    };

    const setMobileMapVisible = (visible) => {
      if (!careerSection || !mobileToggle) {
        return;
      }

      careerSection.classList.toggle('is-map-visible', visible);
      mobileToggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
      if (mobileToggleLabel) {
        mobileToggleLabel.textContent = visible
          ? (mobileToggle.dataset.labelList || '')
          : (mobileToggle.dataset.labelMap || '');
      }

      if (visible) {
        window.setTimeout(() => {
          map.invalidateSize();
          renderCareerFilter(true);
          window.setTimeout(() => {
            map.invalidateSize();
            renderCareerFilter(true);
          }, 120);
        }, 80);
      }
    };

    markerItems.forEach(({ point, marker }) => {
      marker.on('click', () => {
        selectedCity = normalizeCareerCity(point.city);
        selectedStredisko = String(point.stredisko || '');
        closeCityPanel();
        renderCareerFilter(true);
        marker.openPopup();
      });
    });

    if (cityPanel) {
      const options = [{ value: '', label: allCitiesLabel }, ...cityOptions.map((city) => ({ value: city, label: city }))];
      const searchWrap = document.createElement('div');
      searchWrap.className = 'markets-city__search';

      citySearchInput = document.createElement('input');
      citySearchInput.type = 'search';
      citySearchInput.autocomplete = 'off';
      citySearchInput.placeholder = cityPanel.dataset.searchPlaceholder || '';
      citySearchInput.setAttribute('aria-label', cityPanel.dataset.searchPlaceholder || '');
      searchWrap.appendChild(citySearchInput);

      const optionsList = document.createElement('div');
      optionsList.className = 'markets-city__options';

      options.forEach((option) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'markets-city__option career-map-city__option';
        button.dataset.careerMapCityOption = '';
        button.dataset.city = option.value;
        button.setAttribute('role', 'option');
        button.textContent = option.label;
        optionsList.appendChild(button);
      });

      citySearchEmpty = document.createElement('div');
      citySearchEmpty.className = 'markets-city__empty';
      citySearchEmpty.textContent = cityPanel.dataset.searchEmpty || '';
      citySearchEmpty.hidden = true;

      cityPanel.replaceChildren(searchWrap, optionsList, citySearchEmpty);

      citySearchInput.addEventListener('input', () => {
        citySearchTerm = citySearchInput ? citySearchInput.value : '';
        filterCareerCityOptions();
      });

      citySearchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          const firstVisibleOption = cityPanel.querySelector('[data-career-map-city-option]:not([hidden])');
          if (firstVisibleOption) {
            firstVisibleOption.click();
          }
        }
        if (event.key === 'Escape') {
          event.preventDefault();
          closeCityPanel();
          cityTrigger?.focus();
        }
      });

      cityPanel.addEventListener('click', (event) => {
        const option = event.target.closest('[data-career-map-city-option]');
        if (!option) {
          return;
        }
        selectedCity = normalizeCareerCity(option.dataset.city);
        selectedStredisko = '';
        closeCityPanel();
        renderCareerFilter(true);
      });
    }

    if (cityTrigger && cityPanel) {
      cityTrigger.addEventListener('click', () => {
        const shouldOpen = cityPanel.hidden;
        cityPanel.hidden = !shouldOpen;
        cityTrigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        if (shouldOpen && citySearchInput) {
          window.setTimeout(() => citySearchInput?.focus(), 0);
        }
      });

      document.addEventListener('click', (event) => {
        if (cityPicker && !cityPicker.contains(event.target)) {
          closeCityPanel();
        }
      });
    }

    if (resetButton) {
      resetButton.addEventListener('click', () => {
        selectedCity = '';
        selectedStredisko = '';
        closeCityPanel();
        renderCareerFilter(true);
      });
    }

    if (jobsOnlyButton) {
      jobsOnlyButton.addEventListener('click', () => {
        jobsOnly = !jobsOnly;
        jobsOnlyButton.classList.toggle('is-active', jobsOnly);
        jobsOnlyButton.setAttribute('aria-pressed', jobsOnly ? 'true' : 'false');
        renderMarkers(true);
      });
    }

    if (mobileToggle) {
      mobileToggle.addEventListener('click', () => {
        if (careerSection) {
          setMobileMapVisible(!careerSection.classList.contains('is-map-visible'));
        }
      });
    }

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeCityPanel();
      }
    });

    setCityOptionsState();
    if (jobsOnlyButton) {
      jobsOnlyButton.setAttribute('aria-pressed', 'false');
    }
    renderMarkers(true);
    renderJobCards();

    window.setTimeout(() => map.invalidateSize(), 0);
  };

  const normalizeAvailabilityToken = (value) => normalizeFilterText(value).replace(/\s+/g, ' ');

  const placeMatchesQuery = (place, query) => {
    const normalizedQuery = normalizeAvailabilityToken(query);
    if (!normalizedQuery) {
      return false;
    }

    const pscQuery = normalizedQuery.replace(/\D+/g, '');
    const name = normalizeAvailabilityToken(place.name);
    const district = normalizeAvailabilityToken(place.district);
    const region = normalizeAvailabilityToken(place.region);
    const psc = String(place.psc || '').replace(/\D+/g, '');

    return name.includes(normalizedQuery)
      || district.includes(normalizedQuery)
      || region.includes(normalizedQuery)
      || (pscQuery.length >= 3 && psc.includes(pscQuery));
  };

  const findAvailabilityPlace = (places, query) => {
    const normalizedQuery = normalizeAvailabilityToken(query);
    const pscQuery = normalizedQuery.replace(/\D+/g, '');
    if (!normalizedQuery) {
      return null;
    }

    return places.find((place) => normalizeAvailabilityToken(place.name) === normalizedQuery)
      || (pscQuery.length === 5 ? places.find((place) => String(place.psc || '').replace(/\D+/g, '').includes(pscQuery)) : null)
      || places.find((place) => placeMatchesQuery(place, query))
      || null;
  };

  const contactHtml = (root, place) => {
    const contact = place && place.contact ? place.contact : {};
    const name = String(contact.name || '').trim();
    const email = String(contact.email || '').trim();
    const phone = String(contact.phone || '').trim();
    if (!name && !email && !phone) {
      return `<p>${escapeHtml(root.dataset.labelNoContact || '')}</p>`;
    }

    const rows = [];
    if (name) rows.push(`<strong>${escapeHtml(name)}</strong>`);
    if (email) rows.push(`<a href="mailto:${escapeHtml(email)}">${escapeHtml(email)}</a>`);
    if (phone) rows.push(`<a href="tel:${escapeHtml(phone.replace(/\s+/g, ''))}">${escapeHtml(phone)}</a>`);

    return `<p>${escapeHtml(root.dataset.labelContact || '')}</p><div>${rows.join('')}</div>`;
  };

  const availabilityStatusHtml = (root, place) => {
    if (!place) {
      return {
        className: 'is-not-served',
        html: `<strong>${escapeHtml(root.dataset.labelNoResult || '')}</strong>`,
      };
    }

    const status = String(place.status || 'not_served');
    const labels = {
      served: root.dataset.labelServed || '',
      excluded: root.dataset.labelExcluded || '',
      review: root.dataset.labelReview || '',
      not_served: root.dataset.labelNotServed || '',
    };
    const meta = [place.district, place.region].filter(Boolean).join(' · ');
    const psc = place.psc ? `PSČ: ${escapeHtml(place.psc)}` : '';
    const contact = (status === 'served' || status === 'review') ? contactHtml(root, place) : '';

    return {
      className: `is-${status.replace('_', '-')}`,
      html: `<strong>${escapeHtml(labels[status] || labels.not_served || '')}</strong><span>${escapeHtml(place.name || '')}${meta ? ` · ${escapeHtml(meta)}` : ''}</span>${psc ? `<small>${psc}</small>` : ''}${contact}`,
    };
  };

  const initWholesaleAvailability = (root) => {
    const form = root.querySelector('[data-wholesale-availability-form]');
    const input = root.querySelector('[data-wholesale-availability-input]');
    const result = root.querySelector('[data-wholesale-availability-result]');
    const suggestions = root.querySelector('[data-wholesale-availability-suggestions]');
    const places = parseJsonElement(root, '[data-wholesale-availability-places]', []);

    if (!form || !input || !result || !suggestions || !Array.isArray(places)) {
      return;
    }

    const renderResult = (place) => {
      const output = availabilityStatusHtml(root, place);
      result.className = `wholesale-availability__result ${output.className}`;
      result.innerHTML = output.html;
      result.hidden = false;
      suggestions.hidden = true;
      const section = root.closest('.wholesale-finder');
      if (section) {
        section.dispatchEvent(new CustomEvent('qanto:wholesale-place-selected', {
          detail: {
            id: place && place.id ? Number(place.id) : 0,
            status: place && place.status ? String(place.status) : '',
          },
        }));
      }
    };

    const renderSuggestions = () => {
      const query = input.value;
      if (normalizeAvailabilityToken(query).length < 2) {
        suggestions.hidden = true;
        suggestions.replaceChildren();
        return;
      }

      const matches = places.filter((place) => placeMatchesQuery(place, query)).slice(0, 8);
      if (!matches.length) {
        suggestions.hidden = true;
        suggestions.replaceChildren();
        return;
      }

      suggestions.replaceChildren(...matches.map((place) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.innerHTML = `<strong>${escapeHtml(place.name || '')}</strong><span>${escapeHtml([place.district, place.region].filter(Boolean).join(' · '))}</span>`;
        button.addEventListener('click', () => {
          input.value = place.name || '';
          renderResult(place);
        });
        return button;
      }));
      suggestions.hidden = false;
    };

    input.addEventListener('input', renderSuggestions);
    input.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        suggestions.hidden = true;
      }
      if (event.key === 'Enter') {
        const first = suggestions.querySelector('button');
        if (first && !suggestions.hidden) {
          event.preventDefault();
          first.click();
        }
      }
    });

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      renderResult(findAvailabilityPlace(places, input.value));
    });

    document.addEventListener('click', (event) => {
      if (!root.contains(event.target)) {
        suggestions.hidden = true;
      }
    });
  };

  const initWholesaleBranchFilter = (root) => {
    const section = root.closest('.wholesale-representatives');
    const cards = section ? Array.from(section.querySelectorAll('[data-wholesale-representative]')) : [];
    const empty = section ? section.querySelector('[data-wholesale-representatives-empty]') : null;
    if (!cards.length) {
      return;
    }

    const setFilter = (branchId) => {
      let visibleCount = 0;
      cards.forEach((card) => {
        const matches = !branchId || String(card.dataset.branchId || '') === branchId;
        card.hidden = !matches;
        if (matches) {
          visibleCount += 1;
        }
      });

      root.querySelectorAll('[data-wholesale-branch-filter-button]').forEach((button) => {
        const isActive = String(button.dataset.wholesaleBranchFilterButton || '') === branchId;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });

      if (empty) {
        empty.hidden = visibleCount > 0;
      }
    };

    root.querySelectorAll('[data-wholesale-branch-filter-button]').forEach((button) => {
      button.addEventListener('click', () => {
        const branchId = String(button.dataset.wholesaleBranchFilterButton || '');
        setFilter(branchId);
        document.dispatchEvent(new CustomEvent('qanto:wholesale-branch-selected', {
          detail: { branchId, source: 'filter' },
        }));
      });
    });

    document.addEventListener('qanto:wholesale-branch-selected', (event) => {
      const branchId = String((event.detail && event.detail.branchId) || '');
      setFilter(branchId);
    });

    setFilter('');
  };

  const initContactSelect = (root) => {
    const native = root.querySelector('[data-contact-select-native]');
    const ui = root.querySelector('[data-contact-select-ui]');
    const trigger = root.querySelector('[data-contact-select-trigger]');
    const label = root.querySelector('[data-contact-select-label]');
    const panel = root.querySelector('[data-contact-select-panel]');
    const options = Array.from(root.querySelectorAll('[data-contact-select-option]'));

    if (!native || !ui || !trigger || !label || !panel || !options.length) {
      return;
    }

    root.classList.add('is-enhanced');
    let validationShown = false;

    const updateLabel = () => {
      const selectedOption = native.options[native.selectedIndex];
      const selectedValue = native.value;
      label.textContent = selectedOption ? selectedOption.textContent.trim() : '';
      options.forEach((option) => {
        const isSelected = option.dataset.value === selectedValue;
        option.setAttribute('aria-selected', isSelected ? 'true' : 'false');
      });
      const isInvalid = native.required && !selectedValue;
      root.classList.toggle('is-invalid', validationShown && isInvalid);
      trigger.setAttribute('aria-invalid', validationShown && isInvalid ? 'true' : 'false');
    };

    const close = () => {
      ui.classList.remove('is-open');
      panel.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
    };

    const open = () => {
      ui.classList.add('is-open');
      panel.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      const active = options.find((option) => option.getAttribute('aria-selected') === 'true') || options[0];
      window.requestAnimationFrame(() => active.focus());
    };

    trigger.addEventListener('click', () => {
      if (panel.hidden) {
        open();
      } else {
        close();
      }
    });

    options.forEach((option) => {
      option.addEventListener('click', () => {
        native.value = option.dataset.value || '';
        validationShown = true;
        native.dispatchEvent(new Event('change', { bubbles: true }));
        updateLabel();
        close();
        trigger.focus();
      });
    });

    root.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        close();
        trigger.focus();
      }
    });

    document.addEventListener('click', (event) => {
      if (!root.contains(event.target)) {
        close();
      }
    });

    native.addEventListener('change', updateLabel);
    const form = native.closest('form');
    if (form) {
      form.addEventListener('submit', (event) => {
        if (native.required && !native.value) {
          event.preventDefault();
          validationShown = true;
          updateLabel();
          trigger.focus();
        }
      });
    }
    updateLabel();
  };

  const initBrigadaForm = (form) => {
    const page = form.closest('.brigada-page');
    const modal = page ? page.querySelector('[data-brigada-branch-modal]') : null;
    const openButton = form.querySelector('[data-brigada-branch-open]');
    const closeButtons = modal ? modal.querySelectorAll('[data-brigada-branch-close]') : [];
    const input = form.querySelector('[data-brigada-branch-id]');
    const field = form.querySelector('.brigada-branch-field');
    const error = form.querySelector('[data-brigada-branch-error]');
    const nameTarget = form.querySelector('[data-brigada-branch-name]');
    const metaTarget = form.querySelector('[data-brigada-branch-meta]');
    const search = modal ? modal.querySelector('[data-brigada-branch-search]') : null;
    const options = modal ? Array.from(modal.querySelectorAll('[data-brigada-branch-option]')) : [];
    const empty = modal ? modal.querySelector('[data-brigada-branch-empty]') : null;
    let lastFocused = null;

    if (!modal || !openButton || !input || !field || !nameTarget || !metaTarget || !options.length) {
      return;
    }

    const setInvalid = (invalid) => {
      field.classList.toggle('is-invalid', invalid);
      if (error) {
        error.hidden = !invalid;
      }
    };

    const close = () => {
      modal.hidden = true;
      document.body.classList.remove('has-brigada-modal');
      if (lastFocused && typeof lastFocused.focus === 'function') {
        lastFocused.focus();
      }
    };

    const open = () => {
      lastFocused = document.activeElement;
      modal.hidden = false;
      document.body.classList.add('has-brigada-modal');
      if (search) {
        search.value = '';
        filterOptions('');
      }
      window.requestAnimationFrame(() => {
        if (search) {
          search.focus();
        }
      });
    };

    const selectOption = (option) => {
      input.value = option.dataset.id || '';
      nameTarget.textContent = option.dataset.name || '';
      metaTarget.textContent = option.dataset.meta || '';
      options.forEach((item) => item.classList.toggle('is-selected', item === option));
      setInvalid(false);
      close();
    };

    function filterOptions(query) {
      const needle = normalizeFilterText(query);
      let visibleCount = 0;
      options.forEach((option) => {
        const haystack = normalizeFilterText(`${option.dataset.search || ''} ${option.dataset.name || ''} ${option.dataset.meta || ''}`);
        const visible = !needle || haystack.includes(needle);
        option.hidden = !visible;
        if (visible) {
          visibleCount += 1;
        }
      });

      if (empty) {
        empty.hidden = visibleCount > 0;
      }

      const headings = modal.querySelectorAll('.brigada-branch-modal__list h3');
      headings.forEach((heading) => {
        let sibling = heading.nextElementSibling;
        let hasVisible = false;
        while (sibling && !sibling.matches('h3')) {
          if (sibling.matches('[data-brigada-branch-option]') && !sibling.hidden) {
            hasVisible = true;
            break;
          }
          sibling = sibling.nextElementSibling;
        }
        heading.hidden = !hasVisible;
      });
    }

    openButton.addEventListener('click', open);
    closeButtons.forEach((button) => button.addEventListener('click', close));
    options.forEach((option) => {
      option.addEventListener('click', () => selectOption(option));
    });

    if (search) {
      search.addEventListener('input', () => filterOptions(search.value));
    }

    document.addEventListener('keydown', (event) => {
      if (!modal.hidden && event.key === 'Escape') {
        close();
      }
    });

    form.addEventListener('submit', (event) => {
      if (!input.value) {
        event.preventDefault();
        setInvalid(true);
        openButton.focus();
      }
    });

    if (input.value) {
      const selected = options.find((option) => option.dataset.id === input.value);
      if (selected) {
        selected.classList.add('is-selected');
      }
    }
  };

  const initMarketGallery = (root) => {
    const items = Array.from(root.querySelectorAll('[data-market-gallery-item]'))
      .map((button, index) => ({
        button,
        index,
        full: button.dataset.full || '',
        title: button.dataset.title || '',
      }))
      .filter((item) => item.full);
    const lightbox = root.querySelector('[data-market-gallery-lightbox]');

    if (!items.length || !lightbox) {
      return;
    }

    const closeLabel = lightbox.dataset.labelClose || 'Zavřít';
    const prevLabel = lightbox.dataset.labelPrev || 'Předchozí';
    const nextLabel = lightbox.dataset.labelNext || 'Další';
    let currentIndex = 0;
    let previousFocus = null;

    lightbox.innerHTML = `
      <div class="market-gallery-lightbox__top">
        <p class="market-gallery-lightbox__title" data-market-gallery-title></p>
        <button type="button" class="market-gallery-lightbox__button" data-market-gallery-close aria-label="${escapeHtml(closeLabel)}">×</button>
      </div>
      <div class="market-gallery-lightbox__stage">
        <img class="market-gallery-lightbox__image" data-market-gallery-image alt="">
      </div>
      <div class="market-gallery-lightbox__bottom">
        <span class="market-gallery-lightbox__count" data-market-gallery-count></span>
        <div class="market-gallery-lightbox__nav">
          <button type="button" class="market-gallery-lightbox__button" data-market-gallery-prev aria-label="${escapeHtml(prevLabel)}">←</button>
          <button type="button" class="market-gallery-lightbox__button" data-market-gallery-next aria-label="${escapeHtml(nextLabel)}">→</button>
        </div>
      </div>
    `;

    const image = lightbox.querySelector('[data-market-gallery-image]');
    const title = lightbox.querySelector('[data-market-gallery-title]');
    const count = lightbox.querySelector('[data-market-gallery-count]');
    const closeButton = lightbox.querySelector('[data-market-gallery-close]');
    const prevButton = lightbox.querySelector('[data-market-gallery-prev]');
    const nextButton = lightbox.querySelector('[data-market-gallery-next]');

    const render = () => {
      const item = items[currentIndex];
      if (!item || !image || !title || !count || !prevButton || !nextButton) {
        return;
      }

      image.src = item.full;
      image.alt = item.title || '';
      title.textContent = item.title || '';
      count.textContent = `${currentIndex + 1} / ${items.length}`;
      prevButton.disabled = items.length <= 1;
      nextButton.disabled = items.length <= 1;
    };

    const open = (index, trigger) => {
      currentIndex = clampNumber(index, 0, items.length - 1);
      previousFocus = trigger || document.activeElement;
      render();
      lightbox.hidden = false;
      document.body.classList.add('has-market-lightbox');
      closeButton?.focus({ preventScroll: true });
    };

    const close = () => {
      lightbox.hidden = true;
      document.body.classList.remove('has-market-lightbox');
      image?.removeAttribute('src');
      if (previousFocus && typeof previousFocus.focus === 'function') {
        previousFocus.focus({ preventScroll: true });
      }
    };

    const move = (delta) => {
      currentIndex = (currentIndex + delta + items.length) % items.length;
      render();
    };

    root.addEventListener('click', (event) => {
      const button = event.target.closest('[data-market-gallery-item]');
      if (!button || !root.contains(button)) {
        return;
      }

      const index = Number(button.dataset.marketGalleryIndex) || 0;
      open(index, button);
    });

    lightbox.addEventListener('click', (event) => {
      if (event.target === lightbox || event.target.closest('[data-market-gallery-close]')) {
        close();
      } else if (event.target.closest('[data-market-gallery-prev]')) {
        move(-1);
      } else if (event.target.closest('[data-market-gallery-next]')) {
        move(1);
      }
    });

    document.addEventListener('keydown', (event) => {
      if (lightbox.hidden) {
        return;
      }

      if (event.key === 'Escape') {
        close();
      } else if (event.key === 'ArrowLeft') {
        move(-1);
      } else if (event.key === 'ArrowRight') {
        move(1);
      }
    });
  };

  const viewerPageSize = (pages, fallbackWidth = 1200, fallbackHeight = 1674) => {
    const firstSizedPage = pages.find((page) => Number(page.width) > 0 && Number(page.height) > 0);
    const width = firstSizedPage ? Number(firstSizedPage.width) : fallbackWidth;
    const height = firstSizedPage ? Number(firstSizedPage.height) : fallbackHeight;

    return {
      width: Math.round(clampNumber(width, 280, 1720)),
      height: Math.round(clampNumber(height, 390, 2400)),
    };
  };

  const initAkceViewer = (viewer) => {
    const pages = parseViewerPages(viewer);
    const book = viewer.querySelector('[data-akce-viewer-book]');
    const stage = viewer.querySelector('[data-akce-viewer-stage]');
    const bookWrap = viewer.querySelector('.akce-flip-viewer__book-wrap');
    const thumbs = viewer.querySelector('[data-akce-viewer-thumbs]');
    const fallback = viewer.querySelector('[data-akce-viewer-fallback]');
    const pageLabel = viewer.querySelector('[data-akce-viewer-page]');
    const zoomReset = viewer.querySelector('[data-akce-viewer-action="zoom-reset"]');
    const closeUrl = viewer.dataset.closeUrl || '';
    const closeMode = viewer.dataset.closeMode || '';
    const isModalViewer = viewer.hasAttribute('data-akce-viewer-modal');

    if (!pages.length || !book || !stage || !bookWrap || !thumbs || !pageLabel || !window.St || !window.St.PageFlip) {
      return;
    }

    let pageFlip = null;
    let zoom = 1;
    let panX = 0;
    let panY = 0;
    let isPanning = false;
    let panStartX = 0;
    let panStartY = 0;
    let panOriginX = 0;
    let panOriginY = 0;

    const clampPan = () => {
      if (zoom <= 1) {
        panX = 0;
        panY = 0;
        return;
      }

      const maxPanX = Math.max(0, bookWrap.clientWidth * (zoom - 1) / 2);
      const maxPanY = Math.max(0, bookWrap.clientHeight * (zoom - 1) / 2);
      panX = clampNumber(panX, -maxPanX, maxPanX);
      panY = clampNumber(panY, -maxPanY, maxPanY);
    };

    const applyZoom = () => {
      clampPan();
      stage.style.setProperty('--akce-viewer-zoom', String(zoom));
      stage.style.setProperty('--akce-viewer-pan-x', `${panX}px`);
      stage.style.setProperty('--akce-viewer-pan-y', `${panY}px`);
      bookWrap.classList.toggle('is-zoomed', zoom > 1);
      bookWrap.classList.toggle('is-dragging', isPanning);
      if (zoomReset) {
        zoomReset.textContent = `${Math.round(zoom * 100)}%`;
      }
    };

    const setZoom = (nextZoom) => {
      zoom = clampNumber(Math.round(nextZoom * 100) / 100, 1, 3);
      if (zoom <= 1) {
        panX = 0;
        panY = 0;
      }
      applyZoom();
    };

    const setViewerSize = () => {
      const minHeight = window.innerWidth < 576 ? 430 : 560;
      const availableHeight = Math.max(minHeight, window.innerHeight * 0.995);
      viewer.style.setProperty('--akce-viewer-height', `${Math.floor(availableHeight)}px`);

      window.requestAnimationFrame(() => {
        if (pageFlip && typeof pageFlip.update === 'function') {
          pageFlip.update();
        }
        applyZoom();
      });
    };

    thumbs.innerHTML = '';
    pages.forEach((page, index) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'akce-flip-viewer__thumb';
      button.dataset.akceViewerThumb = '1';
      button.dataset.pageIndex = String(index);
      button.title = page.label || String(index + 1);

      const image = document.createElement('img');
      image.src = page.thumb || page.src;
      image.alt = page.label || String(index + 1);
      image.loading = 'lazy';
      image.decoding = 'async';

      const number = document.createElement('span');
      number.textContent = String(index + 1);
      button.append(image, number);
      thumbs.appendChild(button);
    });

    setViewerSize();

    const pageSize = viewerPageSize(pages);
    pageFlip = new window.St.PageFlip(book, {
      width: pageSize.width,
      height: pageSize.height,
      minWidth: 280,
      maxWidth: pageSize.width,
      minHeight: 395,
      maxHeight: pageSize.height,
      size: 'stretch',
      autoSize: true,
      showCover: true,
      usePortrait: true,
      drawShadow: true,
      flippingTime: 700,
      maxShadowOpacity: 0.28,
      mobileScrollSupport: false,
      swipeDistance: 30,
    });

    const updateUi = (pageIndex) => {
      const safeIndex = Math.max(0, Math.min(pageIndex, pages.length - 1));
      const pageWord = pageLabel.dataset.pageWord || '';
      pageLabel.textContent = `${pageWord} ${safeIndex + 1} / ${pages.length}`.trim();
      setViewerButtonState(viewer, safeIndex, pages.length);
      updateViewerThumb(viewer, safeIndex);
    };

    pageFlip.loadFromImages(pages.map((page) => page.src));
    pageFlip.on('flip', (event) => updateUi(Number(event.data || 0)));
    pageFlip.on('init', (event) => updateUi(Number(event.data && event.data.page ? event.data.page : 0)));
    pageFlip.on('changeOrientation', setViewerSize);
    updateUi(0);

    if (fallback) {
      fallback.hidden = true;
    }

    const goPrev = () => {
      pageFlip.flipPrev('top');
      window.setTimeout(() => updateUi(pageFlip.getCurrentPageIndex()), 760);
    };

    const goNext = () => {
      pageFlip.flipNext('top');
      window.setTimeout(() => updateUi(pageFlip.getCurrentPageIndex()), 760);
    };

    const closeViewer = () => {
      if (document.fullscreenElement && document.exitFullscreen) {
        document.exitFullscreen().catch(() => {});
      }

      if (isModalViewer) {
        viewer.classList.remove('is-open');
        document.body.classList.remove('has-akce-modal');
        return;
      }

      if (closeMode === 'history') {
        let referrerUrl = '';
        try {
          const referrer = new URL(document.referrer);
          if (referrer.origin === window.location.origin && referrer.href !== window.location.href) {
            referrerUrl = referrer.href;
          }
        } catch (error) {
          referrerUrl = '';
        }

        if (referrerUrl && window.history.length > 1) {
          window.history.back();
          return;
        }

        if (referrerUrl) {
          window.location.href = referrerUrl;
          return;
        }
      }

      if (closeUrl) {
        window.location.href = closeUrl;
      }
    };

    const openViewer = () => {
      viewer.classList.add('is-open');
      document.body.classList.add('has-akce-modal');
      window.setTimeout(() => {
        setViewerSize();
        viewer.focus({ preventScroll: true });
      }, 40);
    };

    viewer.addEventListener('click', (event) => {
      const actionButton = event.target.closest('[data-akce-viewer-action]');
      const thumbButton = event.target.closest('[data-akce-viewer-thumb]');

      if (thumbButton) {
        const pageIndex = Number(thumbButton.dataset.pageIndex || 0);
        pageFlip.turnToPage(pageIndex);
        updateUi(pageIndex);
        return;
      }

      if (!actionButton) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();

      const action = actionButton.dataset.akceViewerAction;
      if (action === 'first') pageFlip.turnToPage(0);
      if (action === 'prev') goPrev();
      if (action === 'next') goNext();
      if (action === 'last') pageFlip.turnToPage(pages.length - 1);
      if (action === 'zoom-in') setZoom(zoom + 0.25);
      if (action === 'zoom-out') setZoom(zoom - 0.25);
      if (action === 'zoom-reset') setZoom(1);
      if (action === 'close') closeViewer();
      if (action === 'fullscreen' && viewer.requestFullscreen) {
        viewer.requestFullscreen();
        window.setTimeout(setViewerSize, 120);
      }

      if (action === 'first') updateUi(0);
      if (action === 'last') updateUi(pages.length - 1);
    });

    bookWrap.addEventListener('pointerdown', (event) => {
      if (zoom <= 1 || event.target.closest('button')) {
        return;
      }

      isPanning = true;
      panStartX = event.clientX;
      panStartY = event.clientY;
      panOriginX = panX;
      panOriginY = panY;
      bookWrap.setPointerCapture(event.pointerId);
      applyZoom();
      event.preventDefault();
    });

    bookWrap.addEventListener('pointermove', (event) => {
      if (!isPanning) {
        return;
      }

      panX = panOriginX + event.clientX - panStartX;
      panY = panOriginY + event.clientY - panStartY;
      applyZoom();
    });

    const stopPan = () => {
      if (!isPanning) {
        return;
      }
      isPanning = false;
      applyZoom();
    };

    bookWrap.addEventListener('pointerup', stopPan);
    bookWrap.addEventListener('pointercancel', stopPan);

    viewer.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        event.stopPropagation();
        goPrev();
      }
      if (event.key === 'ArrowRight') {
        event.preventDefault();
        event.stopPropagation();
        goNext();
      }
      if (event.key === '+') {
        event.preventDefault();
        setZoom(zoom + 0.25);
      }
      if (event.key === '-') {
        event.preventDefault();
        setZoom(zoom - 0.25);
      }
      if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        closeViewer();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (isModalViewer && !viewer.classList.contains('is-open')) {
        return;
      }

      const activeTag = String(document.activeElement && document.activeElement.tagName || '').toLowerCase();
      if (['input', 'textarea', 'select'].includes(activeTag)) {
        return;
      }

      if (event.key === 'Escape') {
        event.preventDefault();
        closeViewer();
      } else if (event.key === 'ArrowLeft') {
        event.preventDefault();
        goPrev();
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        goNext();
      }
    });

    window.addEventListener('resize', setViewerSize, { passive: true });
    document.addEventListener('fullscreenchange', setViewerSize);

    viewer.tabIndex = 0;
    viewer.classList.add('is-ready');
    if (!isModalViewer) {
      document.body.classList.add('has-akce-modal');
    }

    if (isModalViewer && viewer.id) {
      document.querySelectorAll('[data-akce-viewer-open]').forEach((button) => {
        if (button.getAttribute('data-akce-viewer-open') !== viewer.id) {
          return;
        }
        button.addEventListener('click', (event) => {
          event.preventDefault();
          openViewer();
        });
      });
    }
  };

  const updateChrome = () => {
    const isScrolled = window.scrollY > 16;
    if (header) header.classList.toggle('is-scrolled', isScrolled);
    if (scrollTop) scrollTop.classList.toggle('is-visible', window.scrollY > 360);
  };

  if (scrollTop) {
    scrollTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  adCarousels.forEach((carousel) => {
    const track = carousel.querySelector('[data-ad-carousel-track]');
    const prev = carousel.querySelector('[data-ad-carousel-prev]');
    const next = carousel.querySelector('[data-ad-carousel-next]');
    if (!track) return;

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let paused = prefersReducedMotion;
    let rafId = 0;
    let lastTime = 0;

    const getLoopWidth = () => track.scrollWidth / 2;
    const scrollByCard = (direction) => {
      const card = track.querySelector('.ad-card');
      const distance = card ? card.getBoundingClientRect().width + 8 : 548;
      track.scrollBy({ left: direction * distance, behavior: 'smooth' });
    };

    const tick = (time) => {
      if (!lastTime) lastTime = time;
      const delta = time - lastTime;
      lastTime = time;

      if (!paused) {
        track.scrollLeft += delta * 0.035;
        const loopWidth = getLoopWidth();
        if (loopWidth > 0 && track.scrollLeft >= loopWidth) track.scrollLeft -= loopWidth;
      }

      rafId = window.requestAnimationFrame(tick);
    };

    carousel.addEventListener('mouseenter', () => { paused = true; });
    carousel.addEventListener('mouseleave', () => { paused = prefersReducedMotion; });
    carousel.addEventListener('focusin', () => { paused = true; });
    carousel.addEventListener('focusout', () => { paused = prefersReducedMotion; });
    if (prev) prev.addEventListener('click', () => scrollByCard(-1));
    if (next) next.addEventListener('click', () => scrollByCard(1));

    rafId = window.requestAnimationFrame(tick);
    window.addEventListener('beforeunload', () => window.cancelAnimationFrame(rafId), { once: true });
  });

  flyerSections.forEach((section) => {
    const tabs = Array.from(section.querySelectorAll('[data-flyer-type]'));
    const panels = Array.from(section.querySelectorAll('[data-flyer-panel]'));
    if (!panels.length) return;

    const showFlyerPage = (panel, page) => {
      const pages = Array.from(panel.querySelectorAll('[data-flyer-page]'));
      if (!pages.length) return;

      const totalPages = pages.length;
      const nextPage = clampNumber(Number(page) || 1, 1, totalPages);
      const visibleButtonCount = Math.min(5, totalPages);
      let startPage = Math.max(1, nextPage - 2);
      let endPage = Math.min(totalPages, startPage + visibleButtonCount - 1);
      startPage = Math.max(1, endPage - visibleButtonCount + 1);

      pages.forEach((pageEl) => {
        const isActive = Number(pageEl.getAttribute('data-flyer-page')) === nextPage;
        pageEl.classList.toggle('is-active', isActive);
        pageEl.toggleAttribute('hidden', !isActive);
      });

      panel.querySelectorAll('[data-flyer-page-button]').forEach((button) => {
        const buttonPage = Number(button.getAttribute('data-flyer-page-button')) || 1;
        const isActive = buttonPage === nextPage;
        const isVisible = buttonPage >= startPage && buttonPage <= endPage;
        button.classList.toggle('is-active', isActive);
        button.toggleAttribute('hidden', !isVisible);
        if (isActive) {
          button.setAttribute('aria-current', 'page');
        } else {
          button.removeAttribute('aria-current');
        }
      });

      panel.querySelectorAll('[data-flyer-page-action="first"], [data-flyer-page-action="prev"]').forEach((button) => {
        button.disabled = nextPage <= 1;
        button.toggleAttribute('hidden', nextPage <= 1);
      });
      panel.querySelectorAll('[data-flyer-page-action="next"], [data-flyer-page-action="last"]').forEach((button) => {
        button.disabled = nextPage >= totalPages;
        button.toggleAttribute('hidden', nextPage >= totalPages);
      });

      panel.setAttribute('data-flyer-current-page', String(nextPage));
    };

    const activate = (type) => {
      tabs.forEach((tab) => {
        const isActive = tab.getAttribute('data-flyer-type') === type;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
      panels.forEach((panel) => {
        const isActive = panel.getAttribute('data-flyer-panel') === type;
        panel.classList.toggle('is-active', isActive);
        panel.toggleAttribute('hidden', !isActive);
        if (isActive) showFlyerPage(panel, 1);
      });
    };

    if (tabs.length) {
      tabs.forEach((tab) => {
        tab.addEventListener('click', () => activate(tab.getAttribute('data-flyer-type') || ''));
      });
    }

    const requestedType = new URLSearchParams(window.location.search).get('typ');
    if (requestedType && panels.some((panel) => panel.getAttribute('data-flyer-panel') === requestedType)) {
      activate(requestedType);
    }

    section.addEventListener('click', (event) => {
      const button = event.target.closest('[data-flyer-page-button], [data-flyer-page-action]');
      if (!button || !section.contains(button)) return;

      const panel = button.closest('[data-flyer-panel]');
      if (!panel) return;

      const totalPages = panel.querySelectorAll('[data-flyer-page]').length;
      const currentPage = Number(panel.getAttribute('data-flyer-current-page')) || 1;
      const action = button.getAttribute('data-flyer-page-action');
      let nextPage = currentPage;

      if (button.hasAttribute('data-flyer-page-button')) {
        nextPage = Number(button.getAttribute('data-flyer-page-button')) || 1;
      } else if (action === 'first') {
        nextPage = 1;
      } else if (action === 'prev') {
        nextPage = currentPage - 1;
      } else if (action === 'next') {
        nextPage = currentPage + 1;
      } else if (action === 'last') {
        nextPage = totalPages;
      }

      showFlyerPage(panel, nextPage);
    });

    panels.forEach((panel) => showFlyerPage(panel, 1));
  });

  contactSelects.forEach(initContactSelect);
  brigadaForms.forEach(initBrigadaForm);
  marketGalleries.forEach(initMarketGallery);
  wholesaleAvailabilityForms.forEach(initWholesaleAvailability);
  wholesaleBranchFilters.forEach(initWholesaleBranchFilter);

  if (akceViewers.length) {
    loadStyle(`${libBase}page-flip/stPageFlip.css`);
    loadScript(`${libBase}page-flip/page-flip.browser.js`, () => Boolean(window.St && window.St.PageFlip))
      .then(() => akceViewers.forEach(initAkceViewer))
      .catch((error) => console.warn(error.message));
  }

  if (careerBranchMaps.length || marketsMaps.length || wholesaleMaps.length) {
    loadStyle(`${libBase}leaflet/leaflet.css`);
    loadScript(`${libBase}leaflet/leaflet.js`, () => Boolean(window.L))
      .then(() => {
        careerBranchMaps.forEach(initCareerBranchMap);
        marketsMaps.forEach(initMarketsMap);
        wholesaleMaps.forEach(initWholesaleMap);
      })
      .catch((error) => console.warn(error.message));
  }

  window.addEventListener('scroll', updateChrome, { passive: true });
  updateChrome();
})();
