<!-- BEGIN: Vendor JS-->
<script>
window.paceOptions = {
    ajax: {
        trackWebSockets: false
    }
};
</script>
<script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
<!-- BEGIN Vendor JS-->

<!-- BEGIN: Page Vendor JS-->

<script src="{{ asset('app-assets/vendors/js/ui/jquery.sticky.js') }}"></script>
<script src="{{ asset('app-assets/vendors/js/extensions/tether.min.js') }}"></script>
<script src="{{ asset('app-assets/vendors/js/forms/select/select2.full.min.js') }}"></script>
<script>
window.fleetiqScannerAssets = {
    js: @json(asset('app-assets/vendors/js/scannerjs/scanner.js')),
    css: @json(asset('app-assets/vendors/js/scannerjs/scanner.css')),
    jspdf: @json(asset('app-assets/vendors/js/jspdf/jspdf.umd.min.js')),
    scanAppDownloadUrl: 'https://cdn.asprise.com/scanapp/scan-setup.exe'
};
window.scannerjs_scan_app_download_url = window.fleetiqScannerAssets.scanAppDownloadUrl;
</script>
{{--
<script src="{{ asset('app-assets/vendors/js/extensions/shepherd.min.js') }}"></script>
--}}
<!-- END: Page Vendor JS-->

<!-- BEGIN: Theme JS-->
<script src="{{ asset('app-assets/js/core/app-menu.js') }}"></script>
<script src="{{ asset('app-assets/js/core/app.js') }}"></script>
<script src="{{ asset('app-assets/js/scripts/components.js') }}"></script>
<script src="{{ asset('app-assets/js/scripts/navs/navs.js') }}"></script>
<script src="{{ asset('app-assets/js/scripts/extensions/toastr.js') }}"></script>
<script src="{{ asset('app-assets/js/scripts/fleetiq-scanner-uploads.js') }}"></script>
<!-- END: Theme JS-->

<script>
(function () {
    function scrollFromWheel(deltaY, deltaX, originEl) {
        var node = originEl.parentElement;
        while (node && node !== document.body) {
            var style = window.getComputedStyle(node);
            if ((style.overflowY === 'auto' || style.overflowY === 'scroll' || style.overflowY === 'overlay')
                && node.scrollHeight > node.clientHeight) {
                node.scrollTop += deltaY;
                if (deltaX) {
                    node.scrollLeft += deltaX;
                }
                return;
            }
            node = node.parentElement;
        }
        window.scrollBy(deltaX, deltaY);
    }

    document.addEventListener('wheel', function (e) {
        var el = document.activeElement;
        if (!el || el.tagName !== 'INPUT' || el.type !== 'number') {
            return;
        }
        e.preventDefault();
        scrollFromWheel(e.deltaY, e.deltaX, el);
    }, { passive: false });
})();
</script>

<script>
(function () {
    function initBackendSelect2(root) {
        if (!window.jQuery || !jQuery.fn.select2) {
            return;
        }

        const scope = root || document;
        jQuery(scope)
            .find('select.select-search')
            .not('.select2-hidden-accessible, .no-select2')
            .each(function () {
                const $select = jQuery(this);
                const placeholder = $select.find('option[value=""]').first().text() || 'Select';

                $select.select2({
                    width: '100%',
                    placeholder: placeholder,
                    allowClear: !$select.prop('required')
                });
            });
    }

    window.initBackendSelect2 = initBackendSelect2;

    document.addEventListener('DOMContentLoaded', function () {
        initBackendSelect2(document);

        if (window.MutationObserver) {
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) {
                            initBackendSelect2(node);
                        }
                    });
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    });
})();
</script>
