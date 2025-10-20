<div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true">
    <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">
            <li class="nav-item mr-auto">
                <a class="navbar-brand" href="">
                    <div class="brand-logo"></div>
                </a>
            </li>
            <li class="nav-item nav-toggle">
                <a class="nav-link modern-nav-toggle pr-0" data-toggle="collapse">
                    <i class="icon-x d-block d-xl-none font-medium-4 primary toggle-icon feather icon-disc"></i>
                    <i class="toggle-icon font-medium-4 d-none d-xl-block collapse-toggle-icon primary fa fa-bars" data-ticon="icon-disc"></i>
                </a>
            </li>
        </ul>
    </div>
    <div class="shadow-bottom"></div>
    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
            <li>
                <hr>
            </li>
            <li class="nav-item {{ Request::is('admin/dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-dashboard"><path d="M12 13m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path><path d="M13.45 11.55l2.05 -2.05"></path><path d="M6.4 20a9 9 0 1 1 11.2 0z"></path></svg>
                    <span class="menu-title">Dashboard</span>
                </a>
            </li>
            @role('superuser')
            <li class="navigation-header"><span>User Management</span></li>
            <li class="nav-item {{ Request::is('admin/customers/*') ? 'active' : '' }} {{ Request::is('admin/customers') ? 'active' : '' }}">
                <a href="{{ route('customers.index') }}">
                    <i class="fa fa-user"></i>
                    <span class="menu-title">Customers</span>
                </a>
            </li>
            <li class="nav-item {{ Request::is('admin/roles/*') ? 'active' : '' }} {{ Request::is('admin/roles') ? 'active' : '' }}">
                <a href="{{ route('roles.index') }}">
                    <i class="fa fa-user"></i>
                    <span class="menu-title">Roles</span>
                </a>
            </li>
            <li class="nav-item {{ Request::is('admin/permissions/*') ? 'active' : '' }} {{ Request::is('admin/permissions') ? 'active' : '' }}">
                <a href="{{ route('permissions.index') }}">
                    <i class="fa fa-user"></i>
                    <span class="menu-title">Permission</span>
                </a>
            </li>
            @endrole
            @role('driver')
            <li class="navigation-header"><span>Main</span></li>
            <li class="nav-item {{ Request::is('driver/agreements/*') ? 'active' : '' }} {{ Request::is('driver/agreements') ? 'active' : '' }}">
                <a href="{{ route('driver.agreements') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-heart-handshake"><path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572"></path><path d="M12 6l-3.293 3.293a1 1 0 0 0 0 1.414l.543 .543c.69 .69 1.81 .69 2.5 0l1 -1a3.182 3.182 0 0 1 4.5 0l2.25 2.25"></path><path d="M12.5 15.5l2 2"></path><path d="M15 13l2 2"></path></svg>
                    <span class="menu-title">My Agreements</span>
                </a>
            </li>
            <li class="nav-item {{ Request::is('driver/payments/*') ? 'active' : '' }} {{ Request::is('driver/payments') ? 'active' : '' }}">
                <a href="{{ route('driver.payments') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-coin-pound"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M15 9a2 2 0 1 0 -4 0v5a2 2 0 0 1 -2 2h6"></path><path d="M9 12h4"></path></svg>
                    <span class="menu-title">Payments</span>
                </a>
            </li>
            @endrole
            @role('admin')
            <li class="navigation-header"><span>Main</span></li>
            <li class="nav-item {{ Request::is('admin/agreements/*') ? 'active' : '' }} {{ Request::is('admin/agreements') ? 'active' : '' }}">
                <a href="{{ route('agreements.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-heart-handshake"><path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572"></path><path d="M12 6l-3.293 3.293a1 1 0 0 0 0 1.414l.543 .543c.69 .69 1.81 .69 2.5 0l1 -1a3.182 3.182 0 0 1 4.5 0l2.25 2.25"></path><path d="M12.5 15.5l2 2"></path><path d="M15 13l2 2"></path></svg>
                    <span class="menu-title">Agreements</span>
                </a>
            </li>
            <li class="nav-item {{ Request::is('admin/cars/*') ? 'active' : '' }} {{ Request::is('admin/cars') ? 'active' : '' }}">
                <a href="{{ route('cars.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-car"><path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path><path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path><path d="M5 17h-2v-6l2 -5h9l4 5h1a2 2 0 0 1 2 2v4h-2m-4 0h-6m-6 -6h15m-6 0v-5"></path></svg>
                    <span class="menu-title">Fleets</span>
                </a>
            </li>
            <li class="nav-item {{ Request::is('admin/drivers/*') ? 'active' : '' }} {{ Request::is('admin/drivers') ? 'active' : '' }}">
                <a href="{{ route('drivers.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-steering-wheel"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M12 12m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path><path d="M12 14l0 7"></path><path d="M10 12l-6.75 -2"></path><path d="M14 12l6.75 -2"></path></svg>
                    <span class="menu-title">Drivers</span>
                </a>
            </li>
            <li class="navigation-header"><span>Expenses</span></li>
            <li class="nav-item {{ Request::is('admin/claims/*') ? 'active' : '' }} {{ Request::is('admin/claims') ? 'active' : '' }}">
                <a href="{{ route('claims.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-file-pencil"><path d="M14 3v4a1 1 0 0 0 1 1h4"></path><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path><path d="M10 18l5 -5a1.414 1.414 0 0 0 -2 -2l-5 5v2h2z"></path></svg>
                    <span class="menu-title">Claims</span>
                </a>
            </li>
            <li class="nav-item {{ Request::is('admin/penalties/*') ? 'active' : '' }} {{ Request::is('admin/penalties') ? 'active' : '' }}">
                <a href="{{ route('penalties.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-circle-letter-p"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M10 12h2a2 2 0 1 0 0 -4h-2v8"></path></svg>
                    <span class="menu-title">Penalties</span>
                </a>
            </li>
            <li class="nav-item {{ Request::is('admin/expenses/*') ? 'active' : '' }} {{ Request::is('admin/expenses') ? 'active' : '' }}">
                <a href="{{ route('expenses.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-coin-pound"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M15 9a2 2 0 1 0 -4 0v5a2 2 0 0 1 -2 2h6"></path><path d="M9 12h4"></path></svg>
                    <span class="menu-title">Expenses</span>
                </a>
            </li>

            <li class="navigation-header"><span>Settings</span></li>
            <li class="nav-item {{ Request::is('admin/payments/*') ? 'active' : '' }} {{ Request::is('admin/payments') ? 'active' : '' }}">
                <a href="{{ route('payments.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-coin-pound"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path><path d="M15 9a2 2 0 1 0 -4 0v5a2 2 0 0 1 -2 2h6"></path><path d="M9 12h4"></path></svg>
                    <span class="menu-title">Payments</span>
                </a>
            </li>
            <li class="nav-item {{ Request::is('admin/users/*') ? 'active' : '' }} {{ Request::is('admin/users') ? 'active' : '' }}">
                <a href="{{ route('users.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-users"><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"></path></svg>
                    <span class="menu-title">Users</span>
                </a>
            </li>
           {{-- <li class="nav-item {{ Request::is('admin/file-manager') ? 'active' : '' }}">
                <a href="{{ route('file-manager') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-file"><path d="M14 3v4a1 1 0 0 0 1 1h4"></path><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path></svg>
                    <span class="menu-title">File Manager</span>
                </a>
            </li>--}}
            <li class="nav-item {{ Request::is('admin/companies/*') ? 'active' : '' }} {{ Request::is('admin/companies') ? 'active' : '' }}">
                <a href="{{ route('companies.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-building"><path d="M3 21l18 0"></path><path d="M9 8l1 0"></path><path d="M9 12l1 0"></path><path d="M9 16l1 0"></path><path d="M14 8l1 0"></path><path d="M14 12l1 0"></path><path d="M14 16l1 0"></path><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16"></path></svg>
                    <span class="menu-title">Companies</span>
                </a>
            </li>
            <li class="nav-item {{ Request::is('admin/car-models/*') ? 'active' : '' }} {{ Request::is('admin/car-models') ? 'active' : '' }}">
                <a href="{{ route('car-models.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-car-garage"><path d="M5 20a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path><path d="M15 20a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path><path d="M5 20h-2v-6l2 -5h9l4 5h1a2 2 0 0 1 2 2v4h-2m-4 0h-6m-6 -6h15m-6 0v-5"></path><path d="M3 6l9 -4l9 4"></path></svg>
                    <span class="menu-title">Make/Model</span>
                </a>
            </li>
            <li class="nav-item {{ Request::is('admin/counsels/*') ? 'active' : '' }} {{ Request::is('admin/counsels') ? 'active' : '' }}">
                <a href="{{ route('counsels.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-file-barcode"><path d="M14 3v4a1 1 0 0 0 1 1h4"></path><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path><path d="M8 13h1v3h-1z"></path><path d="M12 13v3"></path><path d="M15 13h1v3h-1z"></path></svg>
                    <span class="menu-title">Counsels</span>
                </a>
            </li>
            <li class="nav-item {{ Request::is('admin/insurance-providers/*') ? 'active' : '' }} {{ Request::is('admin/insurance-providers') ? 'active' : '' }}">
                <a href="{{ route('insurance-providers.index') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.3rem" height="1.3rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-shield-check"><path d="M11.46 20.846a12 12 0 0 1 -7.96 -14.846a12 12 0 0 0 8.5 -3a12 12 0 0 0 8.5 3a12 12 0 0 1 -.09 7.06"></path><path d="M15 19l2 2l4 -4"></path></svg>
                    <span class="menu-title">Insurance Providers</span>
                </a>
            </li>
            @endrole

            <li class="nav-item"><a href="{{ route('logout') }}"><i
                        class="fa fa-power-off"></i><span class="menu-title">Logout</span></a>
            </li>

        </ul>
    </div>
</div>
