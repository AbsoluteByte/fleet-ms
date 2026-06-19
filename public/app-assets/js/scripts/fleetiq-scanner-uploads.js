(function () {
    'use strict';

    var activeInput = null;
    var activeScanCombinePdf = false;
    var scannedFiles = [];
    var scanCounter = 1;
    var scannerLoading = false;
    var scannerLoaded = false;
    var scannerLoadQueue = [];
    var scanBatchImages = [];
    var scanFinalizeTimer = null;
    var scanSessionId = 0;

    var EXCLUDED_NAMES = [
        'logo',
        'profile_image',
        'upload_zip',
        'upload_files',
        'own_insurance_proof_document_zip'
    ];

    function byId(id) {
        return document.getElementById(id);
    }

    function scannerAssets() {
        return window.fleetiqScannerAssets || {};
    }

    function scannerAvailable() {
        return Boolean(window.scanner && typeof window.scanner.scan === 'function');
    }

    function jspdfAvailable() {
        return Boolean(window.jspdf && window.jspdf.jsPDF);
    }

    function ensureScannerConfig() {
        var assets = scannerAssets();
        var downloadUrl = assets.scanAppDownloadUrl || 'https://cdn.asprise.com/scanapp/scan-setup.exe';

        window.scannerjs_scan_app_download_url = downloadUrl;
        window.scannerjs_config = window.scannerjs_config || {};
        window.scannerjs_config.scan_app_download_url = downloadUrl;
    }

    function ensureScannerStyles() {
        if (document.getElementById('fleetiq-scanner-css')) {
            return;
        }

        var assets = scannerAssets();
        var href = assets.css;

        if (!href) {
            return;
        }

        var link = document.createElement('link');
        link.id = 'fleetiq-scanner-css';
        link.rel = 'stylesheet';
        link.type = 'text/css';
        link.href = href;
        document.head.appendChild(link);
    }

    function loadScannerLibrary(callback) {
        if (scannerAvailable()) {
            callback(true);
            return;
        }

        if (scannerLoaded) {
            callback(true);
            return;
        }

        if (scannerLoading) {
            scannerLoadQueue.push(callback);
            return;
        }

        var assets = scannerAssets();
        var src = assets.js;

        if (!src) {
            callback(false);
            return;
        }

        scannerLoading = true;
        ensureScannerConfig();
        ensureScannerStyles();

        var script = document.createElement('script');
        script.id = 'fleetiq-scanner-js';
        script.src = src;
        script.async = true;

        script.onload = function () {
            scannerLoaded = true;
            scannerLoading = false;
            callback(true);

            scannerLoadQueue.splice(0).forEach(function (queuedCallback) {
                queuedCallback(true);
            });
        };

        script.onerror = function () {
            scannerLoading = false;
            callback(false);

            scannerLoadQueue.splice(0).forEach(function (queuedCallback) {
                queuedCallback(false);
            });
        };

        document.body.appendChild(script);
    }

    function ensureJsPdfReady(callback) {
        if (jspdfAvailable()) {
            callback(true);
            return;
        }

        var src = scannerAssets().jspdf;

        if (!src) {
            callback(false);
            return;
        }

        if (document.getElementById('fleetiq-jspdf-js')) {
            callback(jspdfAvailable());
            return;
        }

        var script = document.createElement('script');
        script.id = 'fleetiq-jspdf-js';
        script.src = src;
        script.async = true;
        script.onload = function () {
            callback(jspdfAvailable());
        };
        script.onerror = function () {
            callback(false);
        };
        document.body.appendChild(script);
    }

    function isMac() {
        return /mac/i.test(navigator.platform || navigator.userAgent || '');
    }

    function isWindowsDesktop() {
        var ua = navigator.userAgent || '';
        var platform = navigator.platform || '';
        var isWindows = /win/i.test(platform) || /windows/i.test(ua);
        var isMobile = /android|iphone|ipad|ipod|mobile|tablet/i.test(ua);

        return isWindows && !isMobile;
    }

    function shouldCombinePdf() {
        if (activeScanCombinePdf) {
            return true;
        }

        if (isCombinePdfSelected()) {
            return true;
        }

        return Boolean(activeInput && activeInput.dataset.fleetiqScanCombinePdf === '1');
    }

    function status(message, type) {
        var el = byId('fleetiqScannerStatus');

        if (!el) {
            return;
        }

        el.className = 'alert alert-' + (type || 'info') + ' mb-2';
        el.innerHTML = message;
    }

    function showModal() {
        var modal = byId('fleetiqScannerModal');

        if (!modal) {
            return;
        }

        if (window.jQuery && typeof jQuery.fn.modal === 'function') {
            jQuery(modal).modal('show');
            return;
        }

        modal.style.display = 'block';
        modal.classList.add('show');
        modal.removeAttribute('aria-hidden');
    }

    function hideModal() {
        var modal = byId('fleetiqScannerModal');

        if (!modal) {
            return;
        }

        if (window.jQuery && typeof jQuery.fn.modal === 'function') {
            jQuery(modal).modal('hide');
            return;
        }

        modal.style.display = 'none';
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    }

    function lowerValue(input) {
        return String(input || '').toLowerCase();
    }

    function isExcluded(input) {
        var key = lowerValue((input.name || '') + ' ' + (input.id || ''));

        return EXCLUDED_NAMES.some(function (excluded) {
            return key.indexOf(excluded) !== -1;
        });
    }

    function isEligibleFileInput(input) {
        if (!input || input.type !== 'file' || input.dataset.scannerEnhanced === '1') {
            return false;
        }

        if (isExcluded(input)) {
            return false;
        }

        var accept = lowerValue(input.getAttribute('accept') || '');
        var key = lowerValue((input.name || '') + ' ' + (input.id || ''));
        var acceptsDocument = accept.indexOf('.pdf') !== -1
            || accept.indexOf('pdf') !== -1
            || accept.indexOf('.jpg') !== -1
            || accept.indexOf('.jpeg') !== -1
            || accept.indexOf('.png') !== -1
            || accept.indexOf('image/') !== -1;

        var documentNamed = key.indexOf('document') !== -1
            || key.indexOf('doc') !== -1
            || key.indexOf('proof') !== -1
            || key.indexOf('license') !== -1
            || key.indexOf('licence') !== -1
            || key.indexOf('log_book') !== -1
            || key.indexOf('summary') !== -1
            || key.indexOf('sorn') !== -1;

        return acceptsDocument && documentNamed;
    }

    function enhanceFileInput(input) {
        if (!isWindowsDesktop()) {
            return;
        }

        if (!isEligibleFileInput(input)) {
            return;
        }

        input.dataset.scannerEnhanced = '1';

        var wrapper = document.createElement('div');
        wrapper.className = 'fleetiq-scanner-upload-wrap d-flex align-items-center';

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-outline-primary fleetiq-scan-button ml-1';
        button.innerHTML = '<i class="fa fa-print"></i>';
        button.title = 'Scan document';
        button.setAttribute('aria-label', 'Scan document');

        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        wrapper.appendChild(button);

        button.addEventListener('click', function () {
            openScannerForInput(input);
        });
    }

    function enhanceFileInputs(root) {
        var scope = root || document;
        var inputs = [];

        if (scope.matches && scope.matches('input[type="file"]')) {
            inputs.push(scope);
        }

        if (scope.querySelectorAll) {
            inputs = inputs.concat(Array.prototype.slice.call(scope.querySelectorAll('input[type="file"]')));
        }

        inputs.forEach(enhanceFileInput);
    }

    function openScannerForInput(input) {
        activeInput = input;
        activeScanCombinePdf = false;
        scannedFiles = [];
        scanBatchImages = [];
        clearTimeout(scanFinalizeTimer);
        scanFinalizeTimer = null;

        var preview = byId('fleetiqScannerPreview');

        if (preview) {
            preview.innerHTML = '';
        }

        status('Click <strong>Start Scan</strong> to scan for <strong>' + (input.name || input.id || 'selected field') + '</strong>.', 'info');
        showModal();
    }

    function isCombinePdfSelected() {
        var checkbox = byId('fleetiqScannerCombinePdf');

        return Boolean(checkbox && checkbox.checked);
    }

    function dataUrlToFile(dataUrl, filename) {
        var parts = dataUrl.split(',');
        var meta = parts[0] || '';
        var mimeMatch = meta.match(/data:([^;]+);base64/i);
        var mime = mimeMatch ? mimeMatch[1] : 'image/jpeg';
        var binary = atob(parts[1] || '');
        var bytes = new Uint8Array(binary.length);

        for (var i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }

        return new File([bytes], filename, { type: mime });
    }

    function fileToDataUrl(file) {
        return new Promise(function (resolve, reject) {
            var reader = new FileReader();
            reader.onload = function () {
                resolve(String(reader.result || ''));
            };
            reader.onerror = function () {
                reject(new Error('Could not read scanned file.'));
            };
            reader.readAsDataURL(file);
        });
    }

    function scannedImageDataUrl(scannedImage) {
        if (!scannedImage) {
            return '';
        }

        var src = String(scannedImage.src || '');

        if (src.indexOf('data:') === 0) {
            return src;
        }

        if (src.indexOf('http://') === 0 || src.indexOf('https://') === 0) {
            return src;
        }

        var mime = scannedImage.mimeType || 'image/jpeg';

        if (typeof scannedImage.getBase64NoPrefix === 'function') {
            var base64 = scannedImage.getBase64NoPrefix();

            if (base64) {
                if (String(base64).indexOf('data:') === 0) {
                    return String(base64);
                }

                return 'data:' + mime + ';base64,' + base64;
            }
        }

        if (src && scannedImage.srcIsBase64) {
            return 'data:' + mime + ';base64,' + src;
        }

        return src;
    }

    function dataUrlFormat(dataUrl) {
        var mimeMatch = String(dataUrl || '').match(/^data:([^;]+);/i);

        if (!mimeMatch) {
            return 'JPEG';
        }

        var mime = mimeMatch[1].toLowerCase();

        if (mime.indexOf('png') !== -1) {
            return 'PNG';
        }

        if (mime.indexOf('pdf') !== -1) {
            return 'PDF';
        }

        return 'JPEG';
    }

    function loadImageSize(dataUrl) {
        return new Promise(function (resolve, reject) {
            var img = new Image();
            img.onload = function () {
                resolve({
                    width: img.naturalWidth || img.width,
                    height: img.naturalHeight || img.height
                });
            };
            img.onerror = function () {
                reject(new Error('Could not read scanned image dimensions.'));
            };
            img.src = dataUrl;
        });
    }

    function pageSizeForDataUrl(dataUrl, scannedImage) {
        var width = scannedImage && typeof scannedImage.getWidth === 'function' ? scannedImage.getWidth() : 0;
        var height = scannedImage && typeof scannedImage.getHeight === 'function' ? scannedImage.getHeight() : 0;

        if (width && height) {
            return Promise.resolve({ width: width, height: height });
        }

        return loadImageSize(dataUrl);
    }

    function buildPdfFromPageData(pages) {
        return new Promise(function (resolve, reject) {
            if (!pages.length) {
                reject(new Error('No scanned pages to combine.'));
                return;
            }

            if (!jspdfAvailable()) {
                reject(new Error('PDF library is not available.'));
                return;
            }

            var JsPDF = window.jspdf.jsPDF;
            var doc = null;
            var chain = Promise.resolve();

            pages.forEach(function (page, index) {
                chain = chain.then(function () {
                    return pageSizeForDataUrl(page.dataUrl, page.scannedImage).then(function (size) {
                        var format = page.format || dataUrlFormat(page.dataUrl);
                        var orientation = size.width >= size.height ? 'landscape' : 'portrait';

                        if (!doc) {
                            doc = new JsPDF({
                                unit: 'pt',
                                format: [size.width, size.height],
                                orientation: orientation
                            });
                        } else {
                            doc.addPage([size.width, size.height], orientation);
                        }

                        doc.addImage(page.dataUrl, format, 0, 0, size.width, size.height, undefined, 'FAST');
                    });
                });
            });

            chain.then(function () {
                var blob = doc.output('blob');
                resolve(new File(
                    [blob],
                    'scan-' + Date.now() + '-' + (scanCounter++) + '.pdf',
                    { type: 'application/pdf' }
                ));
            }).catch(reject);
        });
    }

    function buildPdfFromScannedImages(images) {
        var pages = images.map(function (scannedImage) {
            return {
                scannedImage: scannedImage,
                dataUrl: scannedImageDataUrl(scannedImage),
                format: scannedImageFormat(scannedImage)
            };
        }).filter(function (page) {
            return page.dataUrl !== '';
        });

        return buildPdfFromPageData(pages);
    }

    function buildPdfFromImageFiles(files) {
        var chain = Promise.all(files.map(function (file) {
            return fileToDataUrl(file).then(function (dataUrl) {
                return {
                    dataUrl: dataUrl,
                    format: dataUrlFormat(dataUrl)
                };
            });
        }));

        return chain.then(function (pages) {
            return buildPdfFromPageData(pages);
        });
    }

    function scannedImageFormat(scannedImage) {
        return dataUrlFormat(scannedImageDataUrl(scannedImage));
    }

    function scannedItemToFile(scannedImage, index) {
        var dataUrl = scannedImageDataUrl(scannedImage);

        if (!dataUrl) {
            return null;
        }

        return dataUrlToFile(
            dataUrl,
            'scan-' + Date.now() + '-' + (scanCounter++) + '-' + index + '.jpg'
        );
    }

    function filesFromScannedImages(images, combineAsPdf) {
        if (!images.length) {
            return Promise.resolve([]);
        }

        if (!combineAsPdf) {
            return Promise.resolve(images.map(function (image, index) {
                return scannedItemToFile(image, index);
            }).filter(Boolean));
        }

        if (images.length === 1) {
            var singleDataUrl = scannedImageDataUrl(images[0]);

            if (singleDataUrl.indexOf('application/pdf') !== -1 || scannedImageFormat(images[0]) === 'PDF') {
                return Promise.resolve([
                    dataUrlToFile(
                        singleDataUrl,
                        'scan-' + Date.now() + '-' + (scanCounter++) + '.pdf'
                    )
                ]);
            }
        }

        return buildPdfFromScannedImages(images).then(function (pdfFile) {
            return [pdfFile];
        });
    }

    function renderPreview(files) {
        var preview = byId('fleetiqScannerPreview');

        if (!preview) {
            return;
        }

        preview.innerHTML = '';

        files.forEach(function (file) {
            var item = document.createElement('div');
            item.className = 'border rounded p-1 mr-1 mb-1 text-center';
            item.style.width = '120px';

            if (file.type === 'application/pdf' || /\.pdf$/i.test(file.name)) {
                var icon = document.createElement('div');
                icon.className = 'd-flex align-items-center justify-content-center text-danger';
                icon.style.height = '90px';
                icon.innerHTML = '<i class="fa fa-file-pdf-o fa-3x"></i>';
                item.appendChild(icon);
            } else {
                var img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = file.name;
                img.style.maxWidth = '100%';
                img.style.maxHeight = '90px';
                item.appendChild(img);
            }

            var caption = document.createElement('div');
            caption.className = 'small text-muted text-truncate mt-50';
            caption.textContent = file.name;

            item.appendChild(caption);
            preview.appendChild(item);
        });
    }

    function assignFilesToInput(input, files, replaceExisting, forceSingleFile) {
        if (!input || !files.length) {
            return false;
        }

        if (typeof DataTransfer === 'undefined') {
            status('Your browser cannot attach scanned files automatically. Please use normal file upload.', 'warning');
            return false;
        }

        var filesToAssign = files.slice();

        if (forceSingleFile) {
            var pdfFile = filesToAssign.find(function (file) {
                return file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
            });

            filesToAssign = [pdfFile || filesToAssign[0]];
        }

        var transfer = new DataTransfer();

        if (input.multiple && !replaceExisting) {
            Array.prototype.slice.call(input.files || []).forEach(function (file) {
                transfer.items.add(file);
            });
        }

        filesToAssign.forEach(function (file) {
            transfer.items.add(file);
        });

        if (!input.multiple) {
            while (transfer.items.length > 1) {
                transfer.items.remove(0);
            }
        }

        input.files = transfer.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));

        return true;
    }

    function scanRequest() {
        return {
            output_settings: [
                {
                    type: 'return-base64',
                    format: 'jpg'
                }
            ]
        };
    }

    function finalizeScannedBatch(images, sessionId) {
        if (sessionId !== scanSessionId) {
            return;
        }

        var combineAsPdf = shouldCombinePdf();

        status(combineAsPdf ? 'Combining ' + images.length + ' scanned page(s) into one PDF...' : 'Attaching scanned files...', 'info');

        var prepareFiles = function () {
            if (!combineAsPdf) {
                return filesFromScannedImages(images, false);
            }

            return ensureJsPdfReadyAsync().then(function (pdfReady) {
                if (!pdfReady) {
                    throw new Error('PDF combiner could not be loaded. Please refresh the page and try again.');
                }

                return filesFromScannedImages(images, true).then(function (files) {
                    if (files.length <= 1) {
                        return files;
                    }

                    var imageFiles = files.filter(function (file) {
                        return file.type !== 'application/pdf' && !/\.pdf$/i.test(file.name);
                    });

                    if (!imageFiles.length) {
                        return [files[0]];
                    }

                    return buildPdfFromImageFiles(imageFiles);
                });
            });
        };

        prepareFiles().then(function (files) {
            if (sessionId !== scanSessionId) {
                return;
            }

            scannedFiles = files;

            if (!scannedFiles.length) {
                status('No scanned page was returned by the scanner.', 'warning');
                return;
            }

            renderPreview(scannedFiles);

            if (assignFilesToInput(activeInput, scannedFiles, true, combineAsPdf)) {
                var pageCount = images.length;
                var successMessage = combineAsPdf
                    ? 'Combined ' + pageCount + ' scanned page(s) into a single PDF and attached to the file field.'
                    : 'Scanned document attached to the selected file field.';
                status(successMessage, 'success');
                window.setTimeout(hideModal, 700);
            }
        }).catch(function (error) {
            if (sessionId !== scanSessionId) {
                return;
            }

            status('Could not prepare scanned files: ' + (error && error.message ? error.message : 'Unknown error.'), 'danger');
        });
    }

    function ensureJsPdfReadyAsync() {
        return new Promise(function (resolve) {
            if (jspdfAvailable()) {
                resolve(true);
                return;
            }

            ensureJsPdfReady(resolve);
        });
    }

    function scannerCallback(successful, mesg, response) {
        if (!successful) {
            status('Scanner failed: ' + (mesg || 'Unknown scanner error.'), 'danger');
            scanBatchImages = [];
            return;
        }

        if (mesg && String(mesg).toLowerCase().indexOf('user cancel') >= 0) {
            status('Scan cancelled.', 'warning');
            scanBatchImages = [];
            return;
        }

        var images = [];
        var sessionId = scanSessionId;

        try {
            images = window.scanner.getScannedImages(response, true, false) || [];
        } catch (e) {
            status('Scan completed, but scanned images could not be read.', 'danger');
            scanBatchImages = [];
            return;
        }

        if (!images.length) {
            status('No scanned page was returned by the scanner.', 'warning');
            scanBatchImages = [];
            return;
        }

        scanBatchImages = scanBatchImages.concat(images);

        clearTimeout(scanFinalizeTimer);
        scanFinalizeTimer = window.setTimeout(function () {
            var batch = scanBatchImages.slice();
            scanBatchImages = [];
            scanFinalizeTimer = null;
            finalizeScannedBatch(batch, sessionId);
        }, 1200);
    }

    function runScan() {
        if (!scannerAvailable()) {
            status('Scanner.js is not available or the scanner client/bridge is not installed on this computer. Please install Scanner.js client or use normal file upload.', 'warning');
            return;
        }

        activeScanCombinePdf = isCombinePdfSelected();
        scanSessionId += 1;
        scanBatchImages = [];
        clearTimeout(scanFinalizeTimer);
        scanFinalizeTimer = null;

        if (activeInput) {
            activeInput.dataset.fleetiqScanCombinePdf = activeScanCombinePdf ? '1' : '0';
        }

        if (activeScanCombinePdf) {
            status('Opening scanner... scanned pages will be merged into one PDF.', 'info');
        } else {
            status('Opening scanner...', 'info');
        }

        try {
            if (typeof window.scanner.initialize === 'function') {
                window.scanner.initialize();
            }

            var started = window.scanner.scan(scannerCallback, scanRequest());

            if (started === false) {
                status(
                    'Scanner app is not ready. ' +
                    (isMac()
                        ? 'On macOS, Scanner.js needs a compatible local scanner bridge/app and a browser-supported scanner setup. If it is not installed or supported, use normal file upload.'
                        : 'Please install/start the Scanner.js client/bridge, then try again.'),
                    'warning'
                );
            }
        } catch (e) {
            status('Unable to start scanner: ' + e.message, 'danger');
        }
    }

    function startScan() {
        if (!activeInput) {
            status('Please choose a document field first.', 'warning');
            return;
        }

        if (!isWindowsDesktop()) {
            status('Scanner is only available on Windows desktop/laptop. Please use normal file upload.', 'warning');
            return;
        }

        status('Loading scanner...', 'info');

        var begin = function () {
            loadScannerLibrary(function (loaded) {
                if (!loaded) {
                    status('Scanner.js could not be loaded. Please use normal file upload.', 'warning');
                    return;
                }

                runScan();
            });
        };

        if (isCombinePdfSelected()) {
            ensureJsPdfReady(function (pdfLoaded) {
                if (!pdfLoaded) {
                    status('PDF combiner could not be loaded. Please refresh the page and try again.', 'warning');
                    return;
                }

                begin();
            });
            return;
        }

        begin();
    }

    function init() {
        enhanceFileInputs(document);

        document.addEventListener('click', function (event) {
            var button = event.target.closest ? event.target.closest('#fleetiqScannerStart') : null;

            if (!button) {
                return;
            }

            event.preventDefault();
            startScan();
        });

        if (window.MutationObserver) {
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) {
                            enhanceFileInputs(node);
                        }
                    });
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
