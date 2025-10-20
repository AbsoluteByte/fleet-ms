<!doctype html>
<html lang="en">
<head>
    <title>{{ $title ?? '' }} | {{ config('app.name') }}</title>
    <x-backend-head/>
    @stack('cssFiles')
    @yield('css')
    @stack('css')
</head>
<body class="vertical-layout vertical-menu-modern 2-columns  navbar-floating footer-static ecommerce-application" data-open="click"
      data-menu="vertical-menu-modern" data-col="2-columns">

<!-- BEGIN: Header-->
<x-backend-header/>
<!-- END: Header-->
<!-- BEGIN: Main Menu-->

<x-backend-sidebar/>
<!-- END: Main Menu-->
<!-- BEGIN: Content-->

<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper">
        <div class="content-header row">
        </div>
        <div class="content-body">
            <!-- Dashboard Analytics Start -->
            @yield('content')
            <!-- Dashboard Analytics end -->

        </div>
    </div>
</div>
<!-- END: Content-->
<div class="sidenav-overlay"></div>
<div class="drag-target"></div>

<!-- BEGIN: Footer-->
<x-backend-footer/><!-- END: Footer-->

<x-backend-scripts/>
@stack('jsFiles')
@yield('js')
@stack('js')
</body>
</html>
