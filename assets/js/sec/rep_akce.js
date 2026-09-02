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

  const loadFilePond = () => {
    loadStyle(`${libBase}filepond/filepond.min.css`);
    return loadScript(`${libBase}filepond/filepond.min.js`, () => Boolean(window.FilePond));
  };

  let pdfJsPromise = null;
  const loadPdfJs = () => {
    if (!pdfJsPromise) {
      pdfJsPromise = import(`${libBase}pdfjs/pdf.min.mjs`)
        .then((pdfJs) => {
          pdfJs.GlobalWorkerOptions.workerSrc = `${libBase}pdfjs/pdf.worker.min.mjs`;
          return pdfJs;
        })
        .catch((error) => {
          pdfJsPromise = null;
          throw error;
        });
    }
    return pdfJsPromise;
  };

  const initPagesUploader = () => {
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

    loadFilePond()
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

  const initPdfUploader = () => {
    const input = document.querySelector('[data-rep-akce-pdf-upload]');
    if (!input) {
      return;
    }

    const offerId = input.dataset.offerId || '';
    const csrfToken = input.dataset.csrfToken || '';
    const status = document.querySelector('[data-rep-akce-pdf-status]');
    const current = document.querySelector('[data-rep-akce-pdf-current]');

    const setStatus = (message, type = 'muted') => {
      if (!status) return;
      status.className = `small text-${type} mt-2`;
      status.textContent = message;
    };

    if (!offerId || !csrfToken) {
      setStatus('Upload PDF je dostupný až po uložení akční nabídky.', 'muted');
      return;
    }

    loadFilePond()
      .then(() => {
        const endpoint = '/secure/functions/ajax/rep_akce_pdf_upload.php';
        const chunkSize = 4 * 1024 * 1024;
        let pdfUploading = false;
        let lastTransferId = '';
        const baseHeaders = () => ({
          'X-CSRF-Token': csrfToken,
          'X-Offer-Id': offerId,
          'X-Chunk-Size': String(chunkSize),
        });

        const uploadResponseText = (response) => {
          if (typeof response === 'string') {
            return response;
          }
          if (response && typeof response.responseText === 'string') {
            return response.responseText;
          }
          if (response && typeof response.response === 'string') {
            return response.response;
          }
          return '';
        };

        const transferIdFromResponse = (response) => {
          const responseText = uploadResponseText(response).replace(/^\uFEFF/, '').trim();
          if (/^[a-f0-9]{32}$/i.test(responseText)) {
            return responseText.toLowerCase();
          }

          try {
            const data = JSON.parse(responseText);
            const transferId = String(data.transfer_id || data.transferId || data.id || '').trim();
            return /^[a-f0-9]{32}$/i.test(transferId) ? transferId.toLowerCase() : '';
          } catch (error) {
            return '';
          }
        };

        const finalizePdf = async (transferId) => {
          const formData = new FormData();
          formData.append('action', 'finalize');
          formData.append('transfer_id', transferId);
          formData.append('offer_id', offerId);
          formData.append('csrf_token', csrfToken);

          const retryDelays = [0, 1000, 3000];
          let lastError = null;
          for (const delay of retryDelays) {
            if (delay > 0) {
              await new Promise((resolve) => window.setTimeout(resolve, delay));
            }

            try {
              const response = await window.fetch(endpoint, {
                method: 'POST',
                headers: baseHeaders(),
                credentials: 'same-origin',
                body: formData,
              });
              const responseText = await response.text();
              let data = null;
              try {
                data = JSON.parse(responseText);
              } catch (error) {
                data = null;
              }

              if (response.ok && data && data.ok === true && data.path) {
                return data;
              }

              const message = data && data.message
                ? data.message
                : responseText || 'Server nepotvrdil uložení PDF.';
              const error = new Error(message);
              error.status = response.status;
              if (response.status < 500) {
                throw error;
              }
              lastError = error;
            } catch (error) {
              if (error && Number(error.status) > 0 && Number(error.status) < 500) {
                throw error;
              }
              lastError = error;
            }
          }

          throw lastError || new Error('Server nepotvrdil uložení PDF.');
        };

        const pond = window.FilePond.create(input, {
          name: 'pdf_file',
          allowMultiple: false,
          allowRevert: false,
          instantUpload: true,
          chunkUploads: true,
          chunkForce: true,
          chunkSize,
          chunkRetryDelays: [500, 1500, 3000, 5000],
          acceptedFileTypes: ['application/pdf'],
          labelIdle: 'Přetáhněte PDF nebo <span class="filepond--label-action">vyberte soubor</span>',
          labelFileProcessing: 'Nahrávám PDF',
          labelFileProcessingComplete: 'Přenos částí dokončen',
          labelFileProcessingError: 'Chyba uploadu PDF',
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
                return formData;
              },
              onload: (response) => {
                const transferId = transferIdFromResponse(response);
                if (transferId) {
                  lastTransferId = transferId;
                  return transferId;
                }
                return uploadResponseText(response);
              },
              onerror: (response) => uploadResponseText(response),
            },
            patch: {
              url: `${endpoint}?patch=`,
              method: 'PATCH',
              headers: baseHeaders(),
              onerror: (response) => uploadResponseText(response),
            },
          },
        });

        pond.on('processfilestart', () => {
          lastTransferId = '';
          pdfUploading = true;
          setStatus('Probíhá nahrávání PDF po částech. Nezavírejte stránku.', 'primary');
        });

        pond.on('processfile', async (error, file) => {
          if (error) {
            pdfUploading = false;
            const message = error.body || error.main || 'Upload PDF se nepodařil.';
            setStatus(message, 'danger');
            return;
          }

          const transferId = transferIdFromResponse(file && file.serverId) || lastTransferId;
          const fileName = file && file.filename ? file.filename : 'nahrané PDF';
          if (!/^[a-f0-9]{32}$/.test(transferId)) {
            pdfUploading = false;
            setStatus('Server nevrátil platné ID dokončeného přenosu.', 'danger');
            window.setTimeout(() => pond.removeFile(file.id), 0);
            return;
          }

          setStatus('Všechny části byly přeneseny. Ověřuji celé PDF a ukládám ho k nabídce.', 'primary');
          try {
            const result = await finalizePdf(transferId);
            const path = String(result.path || '');
            const originalName = String(result.original_name || fileName);
            if (current && path) {
              current.innerHTML = '';
              const link = document.createElement('a');
              const pdfUrl = `/${path.replace(/^\/+/, '')}`;
              link.href = pdfUrl;
              link.target = '_blank';
              link.rel = 'noopener';
              link.textContent = 'aktuální PDF';
              current.append(link, ` - ${originalName}`);
              document.dispatchEvent(new CustomEvent('rep-akce:pdf-updated', {
                detail: { url: pdfUrl },
              }));
            }
            setStatus('PDF bylo celé ověřeno a uloženo k akční nabídce.', 'success');
          } catch (finalizeError) {
            const message = finalizeError && finalizeError.message
              ? finalizeError.message
              : 'Dokončení uploadu PDF se nepodařilo.';
            setStatus(message, 'danger');
            window.setTimeout(() => pond.removeFile(file.id), 0);
          } finally {
            pdfUploading = false;
          }
        });

        pond.on('error', (error) => {
          pdfUploading = false;
          const message = error && error.body ? error.body : 'Upload PDF se nepodařil.';
          setStatus(message, 'danger');
        });

        const form = input.closest('form');
        if (form) {
          form.addEventListener('submit', (event) => {
            if (!pdfUploading) {
              return;
            }
            event.preventDefault();
            setStatus('Nejdříve počkejte na dokončení uploadu PDF, potom formulář uložte.', 'warning');
          });
        }
      })
      .catch((error) => {
        setStatus(error.message, 'danger');
        console.warn(error.message);
      });
  };

  const initPdfPagesConverter = () => {
    const converter = document.querySelector('[data-rep-akce-pdf-pages-converter]');
    if (!converter) {
      return;
    }

    const startButton = converter.querySelector('[data-rep-akce-pdf-pages-start]');
    const replaceCheckbox = converter.querySelector('[data-rep-akce-pdf-replace-pages]');
    const progress = converter.querySelector('[data-rep-akce-pdf-pages-progress]');
    const progressBar = converter.querySelector('[data-rep-akce-pdf-pages-progress-bar]');
    const status = converter.querySelector('[data-rep-akce-pdf-pages-status]');
    const pageCount = document.querySelector('[data-rep-akce-page-count]');
    const offerId = converter.dataset.offerId || '';
    const csrfToken = converter.dataset.csrfToken || '';
    const maximumPageLongEdge = 2400;
    const pdfDownloadIdleTimeout = 30000;
    const pdfDownloadRetryDelays = [1000, 3000, 5000];
    let running = false;
    let usedBrowserImageFallback = false;

    if (!startButton || !progress || !progressBar || !status || !offerId || !csrfToken) {
      return;
    }

    const outputFormat = () => {
      const format = String(converter.dataset.pageFormat || 'webp').toLowerCase();
      return ['webp', 'jpg', 'png'].includes(format) ? format : 'webp';
    };
    const outputMime = () => ({
      jpg: 'image/jpeg',
      png: 'image/png',
      webp: 'image/webp',
    })[outputFormat()];
    const outputQuality = () => {
      const quality = Number(converter.dataset.pageQuality || 82);
      return Math.max(1, Math.min(100, Number.isFinite(quality) ? quality : 82));
    };
    const outputTargetBytes = () => {
      const targetKb = Number(converter.dataset.pageTargetKb || 400);
      const safeTargetKb = Math.max(100, Math.min(2048, Number.isFinite(targetKb) ? targetKb : 400));
      return Math.round(safeTargetKb * 1024);
    };

    const setStatus = (message, type = 'muted') => {
      status.className = `small text-${type} mt-2`;
      status.textContent = message;
    };

    const setProgress = (completed, total, label = '') => {
      const safeTotal = Math.max(1, Number(total) || 1);
      const percent = Math.max(0, Math.min(100, Math.round((Number(completed) || 0) / safeTotal * 100)));
      progress.setAttribute('aria-valuenow', String(percent));
      progressBar.style.width = `${percent}%`;
      progressBar.textContent = label || `${percent} %`;
    };

    const setRunning = (isRunning) => {
      running = isRunning;
      startButton.disabled = isRunning || !converter.dataset.pdfUrl;
      if (replaceCheckbox) replaceCheckbox.disabled = isRunning;
      progressBar.classList.toggle('progress-bar-animated', isRunning);
    };

    const wait = (delay) => new Promise((resolve) => window.setTimeout(resolve, delay));

    const formatMegabytes = (bytes) => `${(Math.max(0, Number(bytes) || 0) / 1024 / 1024).toLocaleString('cs-CZ', {
      minimumFractionDigits: 1,
      maximumFractionDigits: 1,
    })} MB`;

    const responseRange = (response) => {
      const value = response.headers.get('Content-Range') || '';
      const match = /^bytes\s+(\d+)-(\d+)\/(\d+|\*)$/i.exec(value);
      if (!match) {
        return null;
      }
      return {
        start: Number(match[1]),
        end: Number(match[2]),
        total: match[3] === '*' ? 0 : Number(match[3]),
      };
    };

    const joinPdfChunks = (chunks, byteLength) => {
      const data = new Uint8Array(byteLength);
      let offset = 0;
      chunks.forEach((chunk) => {
        data.set(chunk, offset);
        offset += chunk.byteLength;
      });
      return data;
    };

    const downloadPdf = async (pdfUrl) => {
      let chunks = [];
      let loaded = 0;
      let total = 0;
      let retryIndex = 0;

      while (true) {
        const resumeOffset = loaded;
        const controller = new AbortController();
        let inactivityTimer = null;
        let inactivityTimeoutReached = false;
        const armInactivityTimer = () => {
          if (inactivityTimer !== null) {
            window.clearTimeout(inactivityTimer);
          }
          inactivityTimer = window.setTimeout(() => {
            inactivityTimeoutReached = true;
            controller.abort();
          }, pdfDownloadIdleTimeout);
        };
        const clearInactivityTimer = () => {
          if (inactivityTimer !== null) {
            window.clearTimeout(inactivityTimer);
            inactivityTimer = null;
          }
        };

        try {
          armInactivityTimer();
          const response = await window.fetch(pdfUrl, {
            method: 'GET',
            headers: resumeOffset > 0 ? { Range: `bytes=${resumeOffset}-` } : {},
            credentials: 'same-origin',
            cache: 'no-store',
            signal: controller.signal,
          });
          clearInactivityTimer();

          if (!response.ok) {
            const error = new Error(`Stažení PDF skončilo HTTP chybou ${response.status}.`);
            error.status = response.status;
            throw error;
          }

          const range = responseRange(response);
          if (resumeOffset > 0 && response.status === 206) {
            if (!range || range.start !== resumeOffset) {
              const error = new Error('Server vrátil jinou část PDF, než byla vyžádána.');
              error.status = 409;
              throw error;
            }
          } else if (resumeOffset > 0 && response.status === 200) {
            chunks = [];
            loaded = 0;
          }

          const contentLength = Number(response.headers.get('Content-Length') || 0);
          if (range && range.total > 0) {
            total = range.total;
          } else if (contentLength > 0) {
            total = loaded + contentLength;
          }

          if (!response.body) {
            armInactivityTimer();
            const value = new Uint8Array(await response.arrayBuffer());
            clearInactivityTimer();
            chunks.push(value);
            loaded += value.byteLength;
            setProgress(loaded, total || loaded, `Načítání ${Math.round(loaded / Math.max(1, total || loaded) * 100)} %`);
          } else {
            const reader = response.body.getReader();
            while (true) {
              armInactivityTimer();
              const { done, value } = await reader.read();
              clearInactivityTimer();
              if (done) {
                break;
              }
              if (!value || value.byteLength === 0) {
                continue;
              }
              chunks.push(value);
              loaded += value.byteLength;
              const safeTotal = total || loaded;
              const percent = Math.round(loaded / Math.max(1, safeTotal) * 100);
              setProgress(loaded, safeTotal, `Načítání ${percent} %`);
              setStatus(`Načítám PDF: ${formatMegabytes(loaded)} z ${total > 0 ? formatMegabytes(total) : 'neznámé velikosti'}.`, 'primary');
            }
          }

          if (total > 0 && loaded !== total) {
            throw new Error(`Přenos PDF skončil předčasně po ${formatMegabytes(loaded)} z ${formatMegabytes(total)}.`);
          }
          if (loaded <= 0) {
            throw new Error('Server vrátil prázdné PDF.');
          }

          setStatus(`PDF bylo načteno celé (${formatMegabytes(loaded)}). Připravuji jeho strukturu.`, 'primary');
          return joinPdfChunks(chunks, loaded);
        } catch (error) {
          clearInactivityTimer();
          const statusCode = Number(error && error.status ? error.status : 0);
          const retryable = statusCode === 0 || statusCode >= 500 || [408, 429].includes(statusCode);
          if (!retryable || retryIndex >= pdfDownloadRetryDelays.length) {
            if (inactivityTimeoutReached) {
              throw new Error(`Načítání PDF nepřijalo ${pdfDownloadIdleTimeout / 1000} sekund žádná data.`);
            }
            throw error;
          }

          const delay = pdfDownloadRetryDelays[retryIndex];
          retryIndex += 1;
          const reason = inactivityTimeoutReached ? 'Přenos přestal odpovídat.' : 'Přenos byl přerušen.';
          setStatus(`${reason} Za ${Math.round(delay / 1000)} s pokračuji od ${formatMegabytes(loaded)} (pokus ${retryIndex + 1}/${pdfDownloadRetryDelays.length + 1}).`, 'warning');
          await wait(delay);
        }
      }
    };

    const encodeCanvas = (canvas, mime, quality) => new Promise((resolve) => {
      canvas.toBlob((blob) => {
        resolve(blob);
      }, mime, quality / 100);
    });

    const imageFormatForMime = (mime) => ({
      'image/jpeg': 'jpg',
      'image/png': 'png',
      'image/webp': 'webp',
    })[String(mime || '').toLowerCase()] || '';

    const canvasToBlob = async (canvas, mime, quality) => {
      const preferredBlob = await encodeCanvas(canvas, mime, quality);
      const preferredFormat = preferredBlob ? imageFormatForMime(preferredBlob.type) : '';
      if (preferredBlob && preferredBlob.type === mime && preferredFormat !== '') {
        return { blob: preferredBlob, format: preferredFormat, fallback: false };
      }

      // Some browsers silently return PNG when the requested canvas format is
      // unsupported. Keep that lossless result: a JPEG intermediary followed by
      // the configured server conversion would compress fine text twice.
      if (preferredBlob && preferredFormat === 'png') {
        return { blob: preferredBlob, format: 'png', fallback: true };
      }

      const losslessBlob = await encodeCanvas(canvas, 'image/png', 100);
      if (losslessBlob && losslessBlob.type === 'image/png') {
        return { blob: losslessBlob, format: 'png', fallback: true };
      }

      throw new Error('Prohlížeč nedokázal vytvořit bezeztrátový obrázek stránky.');
    };

    const canvasToTargetBlob = async (canvas, mime, maxQuality, targetBytes) => {
      const minimumQuality = Math.min(45, maxQuality);
      const findBestQuality = async (sourceCanvas) => {
        const maximum = await canvasToBlob(sourceCanvas, mime, maxQuality);
        if (maximum.fallback || mime === 'image/png' || maximum.blob.size <= targetBytes) {
          return { ...maximum, quality: maxQuality, targetExceeded: maximum.blob.size > targetBytes };
        }

        const minimum = await canvasToBlob(sourceCanvas, mime, minimumQuality);
        if (minimum.fallback || minimum.blob.size > targetBytes) {
          return { ...minimum, quality: minimumQuality, targetExceeded: minimum.blob.size > targetBytes };
        }

        let low = minimumQuality;
        let high = maxQuality;
        let best = { ...minimum, quality: minimumQuality, targetExceeded: false };
        while (high - low > 1) {
          const quality = Math.floor((low + high) / 2);
          const candidate = await canvasToBlob(sourceCanvas, mime, quality);
          if (candidate.fallback) {
            return { ...candidate, quality, targetExceeded: candidate.blob.size > targetBytes };
          }
          if (candidate.blob.size <= targetBytes) {
            low = quality;
            best = { ...candidate, quality, targetExceeded: false };
          } else {
            high = quality;
          }
        }
        return best;
      };

      let workingCanvas = canvas;
      let result = await findBestQuality(workingCanvas);
      const originalLongEdge = Math.max(canvas.width, canvas.height);
      const minimumLongEdge = Math.min(originalLongEdge, 1800);

      while (!result.fallback && result.targetExceeded && Math.max(workingCanvas.width, workingCanvas.height) > minimumLongEdge) {
        const estimatedScale = Math.sqrt(targetBytes / Math.max(1, result.blob.size)) * 0.96;
        const scale = Math.max(minimumLongEdge / Math.max(workingCanvas.width, workingCanvas.height), Math.min(0.92, estimatedScale));
        const resized = document.createElement('canvas');
        resized.width = Math.max(1, Math.round(workingCanvas.width * scale));
        resized.height = Math.max(1, Math.round(workingCanvas.height * scale));
        const resizedContext = resized.getContext('2d', { alpha: false });
        if (!resizedContext) {
          break;
        }
        resizedContext.fillStyle = '#ffffff';
        resizedContext.fillRect(0, 0, resized.width, resized.height);
        resizedContext.drawImage(workingCanvas, 0, 0, resized.width, resized.height);
        if (workingCanvas !== canvas) {
          workingCanvas.width = 1;
          workingCanvas.height = 1;
        }
        workingCanvas = resized;
        result = await findBestQuality(workingCanvas);
      }

      const encoded = {
        ...result,
        width: workingCanvas.width,
        height: workingCanvas.height,
      };
      if (workingCanvas !== canvas) {
        workingCanvas.width = 1;
        workingCanvas.height = 1;
      }
      return encoded;
    };

    const pageRenderLongEdge = async (page, pdfJs) => {
      try {
        const operatorList = await page.getOperatorList();
        const rasterOperators = new Set([
          pdfJs.OPS.paintImageXObject,
          pdfJs.OPS.paintInlineImageXObject,
        ]);
        const contentOperators = new Set([
          pdfJs.OPS.showText,
          pdfJs.OPS.showSpacedText,
          pdfJs.OPS.nextLineShowText,
          pdfJs.OPS.nextLineSetSpacingShowText,
          pdfJs.OPS.paintFormXObjectBegin,
          pdfJs.OPS.paintImageMaskXObject,
          pdfJs.OPS.paintImageMaskXObjectGroup,
          pdfJs.OPS.paintImageXObjectRepeat,
          pdfJs.OPS.paintInlineImageXObjectGroup,
          pdfJs.OPS.paintImageMaskXObjectRepeat,
          pdfJs.OPS.paintSolidColorImageMask,
          pdfJs.OPS.constructPath,
          pdfJs.OPS.rawFillPath,
          pdfJs.OPS.stroke,
          pdfJs.OPS.closeStroke,
          pdfJs.OPS.fill,
          pdfJs.OPS.eoFill,
          pdfJs.OPS.fillStroke,
          pdfJs.OPS.eoFillStroke,
          pdfJs.OPS.closeFillStroke,
          pdfJs.OPS.closeEOFillStroke,
          pdfJs.OPS.shadingFill,
        ]);
        const rasterImages = [];
        let containsOtherContent = false;

        operatorList.fnArray.forEach((operator, index) => {
          if (rasterOperators.has(operator)) {
            const args = operatorList.argsArray[index] || [];
            const imageData = operator === pdfJs.OPS.paintInlineImageXObject ? args[0] : null;
            const width = Number(imageData && imageData.width || args[1] || 0);
            const height = Number(imageData && imageData.height || args[2] || 0);
            if (width > 0 && height > 0) {
              rasterImages.push({ width, height });
            } else {
              containsOtherContent = true;
            }
          } else if (contentOperators.has(operator)) {
            containsOtherContent = true;
          }
        });

        if (!containsOtherContent && rasterImages.length === 1) {
          return Math.min(maximumPageLongEdge, Math.max(rasterImages[0].width, rasterImages[0].height));
        }
      } catch (error) {
        console.warn('Nepodařilo se určit přirozené rozlišení stránky PDF.', error);
      }
      return maximumPageLongEdge;
    };

    const uploadPage = async (blob, pageNumber, replacePages, sourceFormat = outputFormat()) => {
      const extension = ['webp', 'jpg', 'png'].includes(sourceFormat) ? sourceFormat : outputFormat();
      const fileName = `pdf-strana-${String(pageNumber).padStart(4, '0')}.${extension}`;
      const endpoint = '/secure/functions/ajax/rep_akce_pages_upload.php';
      const retryDelays = [0, 1000, 3000];
      let lastError = null;

      for (const delay of retryDelays) {
        if (delay > 0) {
          await new Promise((resolve) => window.setTimeout(resolve, delay));
        }

        const formData = new FormData();
        formData.append('page_image', blob, fileName);
        formData.append('offer_id', offerId);
        formData.append('csrf_token', csrfToken);
        formData.append('replace_pages', replacePages ? '1' : '0');

        try {
          const response = await window.fetch(endpoint, {
            method: 'POST',
            headers: {
              'X-CSRF-Token': csrfToken,
              'X-Offer-Id': offerId,
              'X-Replace-Pages': replacePages ? '1' : '0',
            },
            credentials: 'same-origin',
            body: formData,
          });
          const responseText = await response.text();
          if (response.ok) {
            return responseText;
          }

          const error = new Error(responseText || `Upload strany ${pageNumber} skončil chybou.`);
          error.status = response.status;
          if (response.status < 500 && ![408, 429].includes(response.status)) {
            throw error;
          }
          lastError = error;
        } catch (error) {
          if (
            error
            && Number(error.status) > 0
            && Number(error.status) < 500
            && ![408, 429].includes(Number(error.status))
          ) {
            throw error;
          }
          lastError = error;
        }
      }

      throw lastError || new Error(`Upload strany ${pageNumber} se nepodařil.`);
    };

    const beforeUnload = (event) => {
      if (!running) return;
      event.preventDefault();
      event.returnValue = '';
    };

    startButton.addEventListener('click', async () => {
      if (running) {
        return;
      }

      const pdfUrl = converter.dataset.pdfUrl || '';
      if (!pdfUrl) {
        setStatus('Nejdříve nahrajte PDF k této nabídce.', 'warning');
        return;
      }

      const replacePages = Boolean(replaceCheckbox && replaceCheckbox.checked);
      if (replacePages && !window.confirm('Stávající stránky budou po nahrání první nové strany nahrazeny. Pokračovat?')) {
        return;
      }

      let loadingTask = null;
      let pdf = null;
      progressBar.classList.remove('bg-success', 'bg-danger');
      progressBar.classList.add('progress-bar-striped');
      setRunning(true);
      usedBrowserImageFallback = false;
      setProgress(0, 1, '0 %');
      setStatus('Načítám PDF a připravuji převod v prohlížeči.', 'primary');
      window.addEventListener('beforeunload', beforeUnload);

      try {
        const pdfJs = await loadPdfJs();
        const pdfData = await downloadPdf(pdfUrl);
        loadingTask = pdfJs.getDocument({
          data: pdfData,
          cMapUrl: `${libBase}pdfjs/cmaps/`,
          cMapPacked: true,
          iccUrl: `${libBase}pdfjs/iccs/`,
          standardFontDataUrl: `${libBase}pdfjs/standard_fonts/`,
          wasmUrl: `${libBase}pdfjs/wasm/`,
        });
        pdf = await loadingTask.promise;

        const totalPages = Number(pdf.numPages || 0);
        if (totalPages < 1) {
          throw new Error('PDF neobsahuje žádné stránky.');
        }
        setProgress(0, totalPages, `0 / ${totalPages}`);

        for (let pageNumber = 1; pageNumber <= totalPages; pageNumber += 1) {
          setStatus(`Zpracovávám a nahrávám stranu ${pageNumber} z ${totalPages}.`, 'primary');
          const page = await pdf.getPage(pageNumber);
          const baseViewport = page.getViewport({ scale: 1 });
          const renderLongEdge = await pageRenderLongEdge(page, pdfJs);
          const scale = renderLongEdge / Math.max(baseViewport.width, baseViewport.height);
          const viewport = page.getViewport({ scale });
          const canvas = document.createElement('canvas');
          canvas.width = Math.max(1, Math.round(viewport.width));
          canvas.height = Math.max(1, Math.round(viewport.height));
          const context = canvas.getContext('2d', { alpha: false });
          if (!context) {
            throw new Error('Prohlížeč neposkytl kreslicí plochu pro převod PDF.');
          }

          context.fillStyle = '#ffffff';
          context.fillRect(0, 0, canvas.width, canvas.height);
          await page.render({
            canvas,
            canvasContext: context,
            viewport,
            background: 'rgb(255, 255, 255)',
          }).promise;

          const encodedPage = await canvasToTargetBlob(canvas, outputMime(), outputQuality(), outputTargetBytes());
          if (encodedPage.fallback && !usedBrowserImageFallback) {
            usedBrowserImageFallback = true;
            setStatus(`Prohlížeč nevytvořil ${outputFormat().toUpperCase()} přímo. Strany proto předám serveru bezeztrátově jako PNG; server je jednou uloží v nastaveném cílovém formátu.`, 'warning');
          }
          await uploadPage(encodedPage.blob, pageNumber, replacePages && pageNumber === 1, encodedPage.format);
          page.cleanup();
          canvas.width = 1;
          canvas.height = 1;
          setProgress(pageNumber, totalPages, `${pageNumber} / ${totalPages}`);
        }

        if (pageCount) {
          pageCount.textContent = `aktuálně ${totalPages.toLocaleString('cs-CZ')} stran`;
        }
        progressBar.classList.remove('progress-bar-striped');
        progressBar.classList.add('bg-success');
        setProgress(totalPages, totalPages, 'Hotovo');
        const fallbackNote = usedBrowserImageFallback
          ? ' Prohlížeč použil bezeztrátový pomocný PNG a server provedl jediný finální převod.'
          : '';
        setStatus(`Převod byl dokončen. Uloženo ${totalPages} stran ve formátu ${outputFormat().toUpperCase()}, s maximální kvalitou ${outputQuality()} a cílovou velikostí ${Math.round(outputTargetBytes() / 1024)} kB na stranu.${fallbackNote} Obnovte stránku pro načtení náhledů.`, 'success');
      } catch (error) {
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-danger');
        const message = error && error.message ? error.message : 'Převod PDF se nepodařil.';
        setStatus(`${message} Převod můžete spustit znovu.`, 'danger');
        console.warn('Převod PDF na stránky selhal.', error);
      } finally {
        if (pdf) {
          await pdf.cleanup().catch(() => {});
        }
        if (loadingTask) {
          await loadingTask.destroy().catch(() => {});
        }
        window.removeEventListener('beforeunload', beforeUnload);
        setRunning(false);
      }
    });

    document.addEventListener('rep-akce:pdf-updated', (event) => {
      const pdfUrl = event.detail && event.detail.url ? String(event.detail.url) : '';
      if (!pdfUrl) return;
      converter.dataset.pdfUrl = pdfUrl;
      startButton.disabled = running;
      setStatus('Nové PDF je připravené k převodu na obrázkové stránky.', 'success');
      progressBar.classList.remove('bg-success', 'bg-danger', 'progress-bar-animated');
      progressBar.classList.add('progress-bar-striped');
      setProgress(0, 1, '0 %');
    });

    const form = converter.closest('form');
    if (form) {
      form.addEventListener('submit', (event) => {
        if (!running) return;
        event.preventDefault();
        setStatus('Nejdříve počkejte na dokončení převodu PDF.', 'warning');
      });
    }

    setProgress(0, 1, '0 %');
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

  const updateActiveThumb = (viewer, pageIndex) => {
    viewer.querySelectorAll('[data-akce-viewer-thumb]').forEach((button) => {
      button.classList.toggle('is-active', Number(button.dataset.pageIndex) === pageIndex);
    });
  };

  const observeViewerThumbs = (container) => {
    const images = Array.from(container.querySelectorAll('img[data-src]'));
    const loadImage = (image) => {
      image.src = image.dataset.src || '';
      image.removeAttribute('data-src');
    };

    if (!('IntersectionObserver' in window)) {
      images.slice(0, 12).forEach(loadImage);
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }
        loadImage(entry.target);
        observer.unobserve(entry.target);
      });
    }, {
      root: container,
      rootMargin: '200px 0px',
    });

    images.forEach((image) => observer.observe(image));
  };

  const initViewer = (viewer) => {
    const pages = parsePages(viewer);
    const book = viewer.querySelector('[data-akce-viewer-book]');
    const stage = viewer.querySelector('[data-akce-viewer-stage]');
    const thumbs = viewer.querySelector('[data-akce-viewer-thumbs]');
    const pageLabel = viewer.querySelector('[data-akce-viewer-page]');
    const bookWrap = viewer.querySelector('.rep-akce-viewer__book-wrap');
    const zoomInButton = viewer.querySelector('[data-akce-viewer-action="zoom-in"]');

    if (!pages.length || !book || !stage || !thumbs || !pageLabel || !bookWrap) {
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
      image.dataset.src = page.thumb || page.src;
      image.alt = page.label || `Strana ${index + 1}`;
      image.decoding = 'async';
      const number = document.createElement('span');
      number.textContent = String(index + 1);
      button.append(image, number);
      thumbs.appendChild(button);
    });
    observeViewerThumbs(thumbs);

    let currentPageIndex = 0;
    let pagedImage = null;
    let pagedImageRequest = 0;
    let zoom = 1;
    const pagePreloadRequests = new Map();
    const preloadedPageUrls = new Set();
    const queuedPagePreloadUrls = new Set();
    const pagePreloadQueue = [];
    const maximumParallelPagePreloads = 2;

    const pageSource = (page, requestedZoom = zoom) => {
      const sources = Array.isArray(page.sources) ? [...page.sources] : [];
      if (!sources.some((source) => source && source.src === page.src)) {
        sources.push({ src: page.src, width: Number(page.width || 0) });
      }
      const usableSources = sources
        .filter((source) => source && source.src)
        .sort((first, second) => Number(first.width || 0) - Number(second.width || 0));
      if (!usableSources.length) {
        return page.src || '';
      }

      const pageWidth = Number(page.width || 0);
      const pageHeight = Number(page.height || 0);
      const displayedWidth = pageWidth > 0 && pageHeight > 0
        ? Math.min(bookWrap.clientWidth, bookWrap.clientHeight * pageWidth / pageHeight)
        : bookWrap.clientWidth;
      const requiredWidth = displayedWidth
        * Math.min(2.5, Math.max(1, Number(window.devicePixelRatio || 1)))
        * Math.max(1, requestedZoom);
      return (usableSources.find((source) => Number(source.width || 0) >= requiredWidth)
        || usableSources[usableSources.length - 1]).src;
    };

    const runPagePreloadQueue = () => {
      while (pagePreloadRequests.size < maximumParallelPagePreloads && pagePreloadQueue.length) {
        const url = pagePreloadQueue.shift();
        queuedPagePreloadUrls.delete(url);
        if (!url || pagePreloadRequests.has(url) || preloadedPageUrls.has(url)) {
          continue;
        }
        const image = document.createElement('img');
        const release = (loaded) => {
          if (loaded) {
            preloadedPageUrls.add(url);
          }
          image.onload = null;
          image.onerror = null;
          pagePreloadRequests.delete(url);
          runPagePreloadQueue();
        };
        pagePreloadRequests.set(url, image);
        image.decoding = 'async';
        image.onload = () => release(true);
        image.onerror = () => release(false);
        image.src = url;
      }
    };

    const preloadNearbyPages = (pageIndex) => {
      pagePreloadQueue.length = 0;
      queuedPagePreloadUrls.clear();
      const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
      const restrictedConnection = Boolean(
        connection
        && (connection.saveData || /(^|-)2g$/i.test(String(connection.effectiveType || '')))
      );
      const ahead = restrictedConnection ? 1 : 5;
      const indexes = [];
      for (let offset = 1; offset <= ahead; offset += 1) {
        indexes.push(pageIndex + offset);
      }
      if (!restrictedConnection) {
        indexes.push(pageIndex - 1);
      }

      indexes.forEach((index) => {
        if (index < 0 || index >= pages.length) return;
        const url = pageSource(pages[index], 1);
        if (!url || pagePreloadRequests.has(url) || preloadedPageUrls.has(url) || queuedPagePreloadUrls.has(url)) return;
        queuedPagePreloadUrls.add(url);
        pagePreloadQueue.push(url);
      });
      runPagePreloadQueue();
    };

    const updateUi = (pageIndex) => {
      const safeIndex = Math.max(0, Math.min(pageIndex, pages.length - 1));
      currentPageIndex = safeIndex;
      pageLabel.textContent = `Strana ${safeIndex + 1} / ${pages.length}`;
      setButtonState(viewer, safeIndex, pages.length);
      updateActiveThumb(viewer, safeIndex);
    };

    const loadPagedImage = (pageIndex, retry = 0, requestId = null) => {
      const safeIndex = Math.max(0, Math.min(pageIndex, pages.length - 1));
      const page = pages[safeIndex];
      const activeRequestId = requestId ?? ++pagedImageRequest;
      const sourceUrl = pageSource(page);

      if (!pagedImage || activeRequestId !== pagedImageRequest) {
        return;
      }

      pagedImage.alt = page.label || `Strana ${safeIndex + 1}`;
      pagedImage.onload = () => {
        if (activeRequestId === pagedImageRequest) {
          pagedImage.classList.remove('is-loading', 'is-error');
          preloadNearbyPages(safeIndex);
        }
      };
      pagedImage.onerror = () => {
        if (activeRequestId !== pagedImageRequest) {
          return;
        }
        if (retry < 3) {
          window.setTimeout(() => {
            if (activeRequestId !== pagedImageRequest) {
              return;
            }
            loadPagedImage(safeIndex, retry + 1, activeRequestId);
          }, 800 * (retry + 1));
          return;
        }
        pagedImage.classList.remove('is-loading');
        pagedImage.classList.add('is-error');
      };
      pagedImage.classList.add('is-loading');
      pagedImage.classList.remove('is-error');
      const separator = sourceUrl.includes('?') ? '&' : '?';
      pagedImage.src = retry > 0
        ? `${sourceUrl}${separator}viewer_retry=${Date.now()}`
        : sourceUrl;
      updateUi(safeIndex);
    };

    book.classList.add('rep-akce-viewer__book--paged');
    pagedImage = document.createElement('img');
    pagedImage.decoding = 'async';
    book.appendChild(pagedImage);
    loadPagedImage(0);

    const setZoom = (nextZoom) => {
      zoom = Math.max(0.75, Math.min(3, Math.round(nextZoom * 100) / 100));
      stage.style.setProperty('--rep-akce-viewer-scale', String(zoom));
      bookWrap.classList.toggle('is-zoomed', zoom > 1.01);
      if (zoomInButton) {
        zoomInButton.title = `Zvětšit (${Math.round(zoom * 100)} %)`;
      }
      if (pagedImage && pagedImage.src !== new URL(pageSource(pages[currentPageIndex]), window.location.href).href) {
        loadPagedImage(currentPageIndex);
      }
    };

    const movePage = (direction) => {
      loadPagedImage(currentPageIndex + direction);
    };

    viewer.addEventListener('click', (event) => {
      const actionButton = event.target.closest('[data-akce-viewer-action]');
      const thumbButton = event.target.closest('[data-akce-viewer-thumb]');

      if (thumbButton) {
        const pageIndex = Number(thumbButton.dataset.pageIndex || 0);
        loadPagedImage(pageIndex);
        return;
      }

      if (!actionButton) {
        return;
      }

      const action = actionButton.dataset.akceViewerAction;
      if (action === 'first') loadPagedImage(0);
      if (action === 'prev') movePage(-1);
      if (action === 'next') movePage(1);
      if (action === 'last') loadPagedImage(pages.length - 1);
      if (action === 'zoom-in') setZoom(zoom + 0.25);
      if (action === 'zoom-out') setZoom(zoom - 0.25);
      if (action === 'fullscreen' && viewer.requestFullscreen) viewer.requestFullscreen();
    });

    viewer.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowLeft') movePage(-1);
      if (event.key === 'ArrowRight') movePage(1);
    });

    viewer.tabIndex = 0;
  };

  initPagesUploader();
  initPdfUploader();
  initPdfPagesConverter();

  const viewers = document.querySelectorAll('[data-rep-akce-viewer]');
  viewers.forEach(initViewer);
})();
