(() => {
  document.querySelectorAll('[data-rep-akce-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const message = form.dataset.repAkceConfirm || 'Provést akci?';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

  const adminBase = (document.body && document.body.dataset.adminJsBase) || '/assets/js/sec/';
  const libBase = adminBase.replace(/assets\/js\/sec\/?$/, 'assets/lib/');

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
    script.onerror = () => reject(new Error(`Nepodařilo se načíst ${src}`));
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

  const initUploader = () => {
    const input = document.querySelector('[data-rep-akce-pages-upload]');
    if (!input) {
      return;
    }

    const offerId = input.dataset.offerId || '';
    const csrfToken = input.dataset.csrfToken || '';
    const replaceCheckbox = document.querySelector('[data-rep-akce-replace-pages]');
    const status = document.querySelector('[data-rep-akce-upload-status]');

    if (!offerId || !csrfToken) {
      if (status) status.textContent = 'Upload stránek je dostupný až po uložení akční nabídky.';
      return;
    }

    loadStyle(`${libBase}filepond/filepond.min.css`);
    loadScript(`${libBase}filepond/filepond.min.js`, () => Boolean(window.FilePond))
      .then(() => {
        const defaultParallelUploads = 4;
        let replacePending = false;
        let replaceConsumed = false;
        let uploadedCount = 0;
        let pond = null;

        const setStatus = (message, type = 'muted') => {
          if (!status) return;
          status.className = `small text-${type} mt-2`;
          status.textContent = message;
        };

        const consumeReplace = () => {
          if (replacePending && !replaceConsumed) {
            replaceConsumed = true;
            return '1';
          }
          return '0';
        };

        const endpoint = '/secure/functions/ajax/rep_akce_pages_upload.php';
        const baseHeaders = () => ({
          'X-CSRF-Token': csrfToken,
          'X-Offer-Id': offerId,
        });

        const currentParallelUploads = () => (replaceCheckbox && replaceCheckbox.checked ? 1 : defaultParallelUploads);

        pond = window.FilePond.create(input, {
          name: 'page_image',
          allowMultiple: true,
          allowReorder: true,
          allowRevert: false,
          instantUpload: true,
          maxParallelUploads: currentParallelUploads(),
          acceptedFileTypes: ['image/jpeg', 'image/png', 'image/webp'],
          beforeAddFile: () => {
            if (pond) {
              pond.setOptions({ maxParallelUploads: currentParallelUploads() });
            }
            return true;
          },
          labelIdle: 'Přetáhněte obrázky stran nebo <span class="filepond--label-action">vyberte soubory</span>',
          labelFileProcessing: 'Nahrávám',
          labelFileProcessingComplete: 'Nahráno',
          labelFileProcessingError: 'Chyba uploadu',
          labelTapToCancel: 'kliknutím zrušit',
          labelTapToRetry: 'kliknutím opakovat',
          labelTapToUndo: 'vrátit',
          server: {
            process: {
              url: endpoint,
              method: 'POST',
              headers: baseHeaders(),
              ondata: (formData) => {
                formData.append('offer_id', offerId);
                formData.append('csrf_token', csrfToken);
                formData.append('replace_pages', consumeReplace());
                return formData;
              },
              onload: (response) => response,
              onerror: (response) => response,
            },
          },
        });

        pond.on('addfile', () => {
          if (!replacePending && !replaceConsumed && replaceCheckbox && replaceCheckbox.checked) {
            replacePending = true;
          }
        });

        pond.on('processfilestart', () => {
          const parallel = currentParallelUploads();
          setStatus(`Probíhá nahrávání stránek (${parallel} soubor${parallel === 1 ? '' : 'y'} paralelně). Nezavírejte stránku.`, 'primary');
        });

        pond.on('processfile', (error, file) => {
          if (error) {
            const fileName = file && file.filename ? ` (${file.filename})` : '';
            const message = error.body || error.main || 'Upload stránky se nepodařil.';
            setStatus(`${message}${fileName}`, 'danger');
            return;
          }

          uploadedCount += 1;
          setStatus(`Nahráno stránek: ${uploadedCount}. Po dokončení můžete stránku obnovit pro zobrazení nových náhledů.`, 'success');
        });

        pond.on('processfiles', () => {
          if (replaceCheckbox) replaceCheckbox.checked = false;
          replacePending = false;
          replaceConsumed = false;
          setStatus(`Upload dokončen. Nahráno stránek: ${uploadedCount}. Nové pořadí je uloženo podle názvů souborů.`, 'success');
        });

        pond.on('error', (error) => {
          const message = error && error.body ? error.body : 'Upload stránek se nepodařil.';
          setStatus(message, 'danger');
        });
      })
      .catch((error) => {
        if (status) status.textContent = error.message;
        console.warn(error.message);
      });
  };

  const parsePages = (viewer) => {
    try {
      const pages = JSON.parse(viewer.dataset.pages || '[]');
      return Array.isArray(pages) ? pages.filter((page) => page && page.src) : [];
    } catch (error) {
      console.warn('Akční nabídka: neplatná data prohlížeče.', error);
      return [];
    }
  };

  const setButtonState = (viewer, currentPage, totalPages) => {
    viewer.querySelectorAll('[data-akce-viewer-action="first"], [data-akce-viewer-action="prev"]').forEach((button) => {
      button.disabled = currentPage <= 0;
    });
    viewer.querySelectorAll('[data-akce-viewer-action="next"], [data-akce-viewer-action="last"]').forEach((button) => {
      button.disabled = currentPage >= totalPages - 1;
    });
  };

  const clampNumber = (value, min, max) => Math.max(min, Math.min(max, value));

  const viewerPageSize = (pages, fallbackWidth = 1200, fallbackHeight = 1674) => {
    const firstSizedPage = pages.find((page) => Number(page.width) > 0 && Number(page.height) > 0);
    const width = firstSizedPage ? Number(firstSizedPage.width) : fallbackWidth;
    const height = firstSizedPage ? Number(firstSizedPage.height) : fallbackHeight;

    return {
      width: Math.round(clampNumber(width, 280, 1720)),
      height: Math.round(clampNumber(height, 390, 2400)),
    };
  };

  const updateActiveThumb = (viewer, pageIndex) => {
    viewer.querySelectorAll('[data-akce-viewer-thumb]').forEach((button) => {
      button.classList.toggle('is-active', Number(button.dataset.pageIndex) === pageIndex);
    });
  };

  const initViewer = (viewer) => {
    const pages = parsePages(viewer);
    const book = viewer.querySelector('[data-akce-viewer-book]');
    const stage = viewer.querySelector('[data-akce-viewer-stage]');
    const thumbs = viewer.querySelector('[data-akce-viewer-thumbs]');
    const pageLabel = viewer.querySelector('[data-akce-viewer-page]');
    const bookWrap = viewer.querySelector('.rep-akce-viewer__book-wrap');
    const zoomInButton = viewer.querySelector('[data-akce-viewer-action="zoom-in"]');

    if (!pages.length || !book || !stage || !thumbs || !pageLabel || !bookWrap || !window.St || !window.St.PageFlip) {
      return;
    }

    thumbs.innerHTML = '';
    pages.forEach((page, index) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'rep-akce-viewer__thumb';
      button.dataset.akceViewerThumb = '1';
      button.dataset.pageIndex = String(index);
      button.title = page.label || `Strana ${index + 1}`;
      const image = document.createElement('img');
      image.src = page.thumb || page.src;
      image.alt = page.label || `Strana ${index + 1}`;
      image.loading = 'lazy';
      image.decoding = 'async';
      const number = document.createElement('span');
      number.textContent = String(index + 1);
      button.append(image, number);
      thumbs.appendChild(button);
    });

    const pageSize = viewerPageSize(pages);
    const pageFlip = new window.St.PageFlip(book, {
      width: pageSize.width,
      height: pageSize.height,
      minWidth: 280,
      maxWidth: pageSize.width,
      minHeight: 390,
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
      pageLabel.textContent = `Strana ${safeIndex + 1} / ${pages.length}`;
      setButtonState(viewer, safeIndex, pages.length);
      updateActiveThumb(viewer, safeIndex);
    };

    pageFlip.loadFromImages(pages.map((page) => page.src));
    pageFlip.on('flip', (event) => updateUi(Number(event.data || 0)));
    pageFlip.on('init', (event) => updateUi(Number(event.data && event.data.page ? event.data.page : 0)));
    updateUi(0);

    let zoom = 1;
    const setZoom = (nextZoom) => {
      zoom = Math.max(0.75, Math.min(3, Math.round(nextZoom * 100) / 100));
      stage.style.setProperty('--rep-akce-viewer-scale', String(zoom));
      bookWrap.classList.toggle('is-zoomed', zoom > 1.01);
      if (zoomInButton) {
        zoomInButton.title = `Zvětšit (${Math.round(zoom * 100)} %)`;
      }
    };

    viewer.addEventListener('click', (event) => {
      const actionButton = event.target.closest('[data-akce-viewer-action]');
      const thumbButton = event.target.closest('[data-akce-viewer-thumb]');

      if (thumbButton) {
        pageFlip.turnToPage(Number(thumbButton.dataset.pageIndex || 0));
        updateUi(Number(thumbButton.dataset.pageIndex || 0));
        return;
      }

      if (!actionButton) {
        return;
      }

      const action = actionButton.dataset.akceViewerAction;
      if (action === 'first') pageFlip.turnToPage(0);
      if (action === 'prev') pageFlip.flipPrev('top');
      if (action === 'next') pageFlip.flipNext('top');
      if (action === 'last') pageFlip.turnToPage(pages.length - 1);
      if (action === 'zoom-in') setZoom(zoom + 0.25);
      if (action === 'zoom-out') setZoom(zoom - 0.25);
      if (action === 'fullscreen' && viewer.requestFullscreen) viewer.requestFullscreen();

      if (['first', 'last'].includes(action)) {
        updateUi(action === 'first' ? 0 : pages.length - 1);
      }
    });

    viewer.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') pageFlip.flipPrev('top');
      if (event.key === 'ArrowRight') pageFlip.flipNext('top');
    });

    viewer.tabIndex = 0;
  };

  initUploader();

  const viewers = document.querySelectorAll('[data-rep-akce-viewer]');
  if (viewers.length) {
    loadScript(`${libBase}page-flip/page-flip.browser.js`, () => Boolean(window.St && window.St.PageFlip))
      .then(() => viewers.forEach(initViewer))
      .catch((error) => console.warn(error.message));
  }
})();
