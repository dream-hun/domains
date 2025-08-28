<aside class="main-sidebar sidebar-dark-primary elevation-4" style="height:auto !important;">
    <!-- Brand Logo -->
    <a href="{{ route('dashboard') }}" class="brand-link">
        <img src="{{ asset('logo.webp') }}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3"
             style="opacity: .8">
        <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
    </a>
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="{{ Auth::user()?->getGravatarAttribute() }}" class="img-circle elevation-2" alt="User Image">
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
                    <li class="nav-item">
                        <a href="{{ route('admin.contacts.index') }}"
                           class="nav-link {{ request()->is('admin/contacts*') ? 'active' : '' }}">
                            <i class="bi bi-person-lines-fill"></i>
                            <p>
                                {{trans('cruds.contact.title')}}
                            </p>
                        </a>
                    </li>
                @endcan
                @can('domain_access')
                    <li class="nav-item">
                        <a href="{{ route('admin.domains.index') }}"
                           class="nav-link {{ request()->is('admin/domains*') ? 'active' : '' }}">
                            <i class="bi bi-globe2"></i>
                            <p>
                                {{ trans('cruds.domain.title') }}
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
                @can('user_management_access')
                    <li class="nav-item {{ (request()->is('admin/permissions*') || request()->is('admin/roles*') || request()->is('admin/users*')) ? 'menu-open' : '' }}">
                        <a href="#"
                           class="nav-link {{ (request()->is('admin/permissions*') || request()->is('admin/roles*') || request()->is('admin/users*')) ? 'active' : '' }}">
                            <i class="bi bi-people-fill"></i>
                            <p>
                                {{ trans('cruds.userManagement.title') }}
                                <i class="bi bi-chevron-down right"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview"
                            style="{{ (request()->is('admin/permissions*') || request()->is('admin/roles*') || request()->is('admin/users*')) ? 'display: block;' : 'display: none;' }}">
                            @can('permission_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.permissions.index') }}"
                                       class="nav-link {{ request()->is('admin/permissions*') ? 'active' : '' }}">
                                        <i class="bi bi-lock-fill"></i>
                                        <p>{{ trans('cruds.permission.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                            @can('role_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.roles.index') }}"
                                       class="nav-link {{ request()->is('admin/roles*') ? 'active' : '' }}">
                                        <i class="bi bi-gear-wide-connected"></i>
                                        <p>{{ trans('cruds.role.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                            @can('user_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.users.index') }}"
                                       class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}">
                                        <i class="bi bi-people"></i>
                                        <p>{{ trans('cruds.user.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan
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
                {{-- @can('domain_pricing_access')
                     <li class="nav-item">
                         <a href="{{ route('admin.domain-pricings.index') }}"
                            class="nav-link {{ request()->is('admin/domain-pricings*') ? 'active' : '' }}">
                             <i class="bi bi-cash-coin"></i>
                             <p>
                                 Domain Pricing
                             </p>
                         </a>
                     </li>
                 @endcan



                 @can('hosting_access')
                     <li class="nav-item">
                         <a href="{{ route('admin.hostings.index') }}"
                            class="nav-link {{ request()->is('admin/hostings*') ? 'active' : '' }}">
                             <i class="bi bi-hdd"></i>
                             <p>
                                 Hosting Plans
                             </p>
                         </a>
                     </li>
                 @endcan




                 --}}
            </ul>
        </nav>
    </div>
</aside>
