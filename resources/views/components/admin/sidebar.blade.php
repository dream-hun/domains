<aside class="main-sidebar sidebar-dark-primary elevation-4">

    <a href="{{route('dashboard')}}" class="brand-link">
        <img src="{{asset('logo.webp')}}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
             style="opacity: .8">
        <span class="brand-text font-weight-light">{{config('app.name')}}</span>
    </a>
    <div class="sidebar">

        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="{{ asset('logo.webp')}}" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block">{{auth()->user()->name}}</a>
            </div>
        </div>

        <div class="form-inline mt-3">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-sidebar">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            <div class="sidebar-search-results">
                <div class="list-group">
                    <a href="#" class="list-group-item">
                        <div class="search-title">
                            <strong class="text-light">No elements found!</strong>
                        </div>
                        <div class="search-path"></div>
                    </a>
                </div>
            </div>
        </div>


        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <x-admin.sidebar-link route="dashboard" icon="bi bi-speedometer2">
                    Dashboard
                </x-admin.sidebar-link>

                <x-admin.sidebar-link route="currencies.index" icon="bi bi-cash-stack">
                    Currencies
                </x-admin.sidebar-link>

                <x-admin.sidebar-link route="tlds.index" icon="bi bi-globe2">
                    TLDs
                </x-admin.sidebar-link>

                <x-admin.sidebar-link route="countries.index" icon="bi bi-flag">
                    Countries
                </x-admin.sidebar-link>
                @can('domain_access')
                    <x-admin.sidebar-link route="domains.index" icon="bi bi-globe">
                        Domains
                    </x-admin.sidebar-link>
                @endcan
                @can('domainPrice_access')
                    <x-admin.sidebar-link route="domainPricing.index" icon="bi bi-coin">
                        Domain Pricing
                    </x-admin.sidebar-link>
                @endcan

                <li class="nav-item {{ request()->routeIs(['hosting-categories.*', 'plan-pricing.*']) ? 'menu-open' : '' }}">
                    <a href="#"
                       class="nav-link {{ request()->routeIs(['hosting-categories.*', 'plan-pricing.*']) ? 'active' : '' }}">
                        <i class="nav-icon bi bi-server"></i>
                        <p>
                            Hosting Management
                            <i class="right nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <x-admin.sidebar-link route="hosting-categories.index" icon="bi bi-list">
                            Hosting Categories
                        </x-admin.sidebar-link>
                        <x-admin.sidebar-link route="plan-pricing.index" icon="bi bi-cash">
                            Hosting Prices
                        </x-admin.sidebar-link>
                    </ul>
                </li>
                @can('user_management_access')
                    <li class="nav-item {{ request()->routeIs(['users.*', 'roles.*', 'permissions.*']) ? 'menu-open' : '' }}">
                        <a href="#"
                           class="nav-link {{ request()->routeIs(['users.*', 'roles.*', 'permissions.*']) ? 'active' : '' }}">
                            <i class="nav-icon bi bi-lock"></i>
                            <p>
                                User Management
                                <i class="right nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('user_access')
                                <x-admin.sidebar-link route="users.index" icon="bi bi-people">
                                    Users
                                </x-admin.sidebar-link>
                            @endcan
                            @can('role_access')
                                <x-admin.sidebar-link route="roles.index" icon="bi bi-shield-check">
                                    Roles
                                </x-admin.sidebar-link>
                            @endcan
                            @can('permission_access')
                                <x-admin.sidebar-link route="permissions.index" icon="bi bi-key">
                                    Permissions
                                </x-admin.sidebar-link>
                            @endcan
                        </ul>
                    </li>
                @endcan
                @can('subscription_access')
                    <x-admin.sidebar-link route="admin.subscriptions.index" icon="bi bi-repeat">
                        Subscriptions
                    </x-admin.sidebar-link>
                @endcan
                @can('product_access')
                    <li class="nav-item {{ request()->routeIs(['admin.products.*']) ? 'menu-open' : '' }}">
                        <a href="#"
                           class="nav-link {{ request()->routeIs(['admin.products.*']) ? 'active' : '' }}">
                            <i class="nav-icon bi bi-box-seam"></i>
                            <p>
                                My Products
                                <i class="right nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <x-admin.sidebar-link route="admin.products.domains" icon="bi bi-globe">
                                My Domains
                            </x-admin.sidebar-link>
                            <x-admin.sidebar-link route="admin.products.hosting" icon="bi bi-server">
                                My Hosting
                            </x-admin.sidebar-link>
                        </ul>
                    </li>
                @endcan
                <x-admin.sidebar-link route="billing.index" icon="bi bi-credit-card">
                    Billing
                </x-admin.sidebar-link>
            </ul>
        </nav>
    </div>
</aside>
