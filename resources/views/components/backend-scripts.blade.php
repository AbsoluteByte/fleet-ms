<!-- BEGIN: Vendor JS-->
<script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
<!-- BEGIN Vendor JS-->

<!-- BEGIN: Page Vendor JS-->

<script src="{{ asset('app-assets/vendors/js/ui/jquery.sticky.js') }}"></script>
<script src="{{ asset('app-assets/vendors/js/extensions/tether.min.js') }}"></script>
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
