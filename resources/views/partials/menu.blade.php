<aside class="main-sidebar sidebar-dark-primary elevation-4" style="height:auto !important;">
    <!-- Brand Logo -->
    <a href="{{ route('dashboard') }}" class="brand-link">
        <img src="{{ asset('logo.webp') }}" alt="{{config('app.name')}}" class="brand-image img-circle elevation-3"
             style="opacity: .8">
        <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
    </a>
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="{{ Auth::user()?->gravatar }}" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block">{{ Auth::user()?->first_name }} {{ Auth::user()?->last_name }}</a>
            </div>
        </div>

        <!-- SidebarSearch Form -->
        <div class="form-inline">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" type="search" placeholder="Search"
                       aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-sidebar">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">

                <li class="nav-item">
                    <a href="{{ route('dashboard') }}"
                       class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer"></i>
                        <p>
                            Dashboard
                        </p>
                    </a>
                </li>
                @can('contact_access')
                    <x-admin.sidebar-link route="admin.contacts.index" icon="bi bi-person-lines-fill">
                        Contact Management
                    </x-admin.sidebar-link>
                @endcan
                @can('currency_access')
                    <x-admin.sidebar-link route="admin.currencies.index" icon="bi bi-cash-stack">
                        Currencies
                    </x-admin.sidebar-link>
                @endcan
                @if(auth()->user()->can('tld_access') || auth()->user()->can('tld_pricing_access') || auth()->user()->can('domain_access') || auth()->user()->can('failed_registration_access'))
                    <x-admin.sidebar-group 
                        title="Domain Management" 
                        icon="bi bi-globe"
                        :routes="['admin.tlds.*', 'admin.tld-pricings.*', 'admin.domains.*', 'admin.domain-price-history.*', 'admin.failed-registrations.*']">
                        @can('tld_access')
                            <x-admin.sidebar-link route="admin.tlds.index" icon="bi bi-globe">
                                TLD
                            </x-admin.sidebar-link>
                        @endcan
                        @can('tld_pricing_access')
                            <x-admin.sidebar-link route="admin.tld-pricings.index" icon="bi bi-currency-exchange">
                                TLD Pricing
                            </x-admin.sidebar-link>
                        @endcan
                        @can('domain_access')
                            <x-admin.sidebar-link route="admin.domains.index" icon="bi bi-globe2">
                                Domains
                            </x-admin.sidebar-link>
                        @endcan
                        @can('domain_access')
                            <x-admin.sidebar-link route="admin.domain-price-history.index" icon="bi bi-clock-history">
                                Domain Price History
                            </x-admin.sidebar-link>
                        @endcan
                        @can('failed_registration_access')
                            <x-admin.sidebar-link route="admin.failed-registrations.index" icon="bi bi-exclamation-triangle">
                                Failed Registrations
                            </x-admin.sidebar-link>
                        @endcan
                    </x-admin.sidebar-group>
                @endif
                @can('hosting_management_access')
                    <x-admin.sidebar-group 
                        title="Hosting Management" 
                        icon="bi bi-hdd"
                        :routes="['admin.hosting-categories.*', 'admin.hosting-plans.*', 'admin.hosting-plan-prices.*', 'admin.hosting-plan-price-history.*', 'admin.hosting-promotions.*', 'admin.hosting-features.*', 'admin.hosting-plan-features.*']">
                        <x-admin.sidebar-link route="admin.hosting-categories.index" icon="bi bi-hdd">
                            Hosting Categories
                        </x-admin.sidebar-link>
                        <x-admin.sidebar-link route="admin.hosting-plans.index" icon="bi bi-hdd">
                            Hosting Plans
                        </x-admin.sidebar-link>
                        <x-admin.sidebar-link route="admin.hosting-plan-prices.index" icon="bi bi-hdd">
                            Hosting Plan Prices
                        </x-admin.sidebar-link>
                        @can('hosting_plan_price_access')
                            <x-admin.sidebar-link route="admin.hosting-plan-price-history.index" icon="bi bi-clock-history">
                                Hosting Plan Price History
                            </x-admin.sidebar-link>
                        @endcan
                        @can('hosting_promotion_access')
                            <x-admin.sidebar-link route="admin.hosting-promotions.index" icon="bi bi-hdd">
                                Hosting Promotions
                            </x-admin.sidebar-link>
                        @endcan
                        <x-admin.sidebar-link route="admin.hosting-features.index" icon="bi bi-hdd">
                            Hosting Features
                        </x-admin.sidebar-link>
                        <x-admin.sidebar-link route="admin.hosting-plan-features.index" icon="bi bi-hdd">
                            Hosting Plan Features
                        </x-admin.sidebar-link>
                    </x-admin.sidebar-group>
                @endcan
                @can('subscription_access')
                    <li class="nav-item">
                        <a href="{{ route('admin.subscriptions.index') }}"
                           class="nav-link {{ request()->is('admin/subscriptions*') ? 'active' : '' }}">
                            <i class="bi bi-repeat"></i>
                            <p>
                                {{ trans('cruds.subscription.title') }}
                            </p>
                        </a>
                    </li>
                @endcan
                @can('setting_access')
                    <li class="nav-item">
                        <a href="{{ route('admin.settings.index') }}"
                           class="nav-link {{ request()->is('admin/settings') || request()->is('admin/settings/*') ? 'active' : '' }}">
                            <i class="bi bi-gear-fill"></i>
                            <p>
                                {{ trans('cruds.setting.title') }}
                            </p>
                        </a>
                    </li>
                @endcan
                @can('product_access')
                    <x-admin.sidebar-group 
                        title="My Products" 
                        icon="bi bi-globe2"
                        :routes="['admin.products.*']">
                        <x-admin.sidebar-link route="admin.products.domains" icon="bi bi-globe2">
                            Domains
                        </x-admin.sidebar-link>
                        <x-admin.sidebar-link route="admin.products.hosting" icon="bi bi-hdd">
                            Hosting
                        </x-admin.sidebar-link>
                    </x-admin.sidebar-group>
                @endcan
                @can('user_management_access')
                    <x-admin.sidebar-group 
                        :title="trans('cruds.userManagement.title')" 
                        icon="bi bi-people-fill"
                        :routes="['admin.permissions.*', 'admin.roles.*', 'admin.users.*']">
                        @can('permission_access')
                            <x-admin.sidebar-link route="admin.permissions.index" icon="bi bi-lock-fill">
                                {{ trans('cruds.permission.title') }}
                            </x-admin.sidebar-link>
                        @endcan
                        @can('role_access')
                            <x-admin.sidebar-link route="admin.roles.index" icon="bi bi-gear-wide-connected">
                                {{ trans('cruds.role.title') }}
                            </x-admin.sidebar-link>
                        @endcan
                        @can('user_access')
                            <x-admin.sidebar-link route="admin.users.index" icon="bi bi-people">
                                {{ trans('cruds.user.title') }}
                            </x-admin.sidebar-link>
                        @endcan
                    </x-admin.sidebar-group>
                @endcan
                @can('audit_log_access')
                    <li class="nav-item">
                        <a href="{{ route('admin.audit-logs.index') }}"
                           class="nav-link {{ request()->is('admin/audit-logs*') ? 'active' : '' }}">
                            <i class="bi bi-clipboard-data"></i>
                            <p>
                                Audit Activity
                            </p>
                        </a>
                    </li>
                @endcan
                <li class="nav-item">
                    <a href="{{ route('billing.index') }}"
                       class="nav-link {{ request()->is('billing.index') ? 'active' : '' }}">
                        <i class="bi bi-credit-card-fill"></i>
                        <p>
                            Billing
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('profile.edit') }}"
                       class="nav-link {{ request()->is('profile.edit') ? 'active' : '' }}">
                        <i class="bi bi-person"></i>
                        <p>
                            My Profile
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <form id="log-out" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <a href="#" class="nav-link"
                       onclick="event.preventDefault(); document.getElementById('log-out').submit();">
                        <i class="bi bi-box-arrow-right"></i>
                        <p>{{ trans('global.logout') }}</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
