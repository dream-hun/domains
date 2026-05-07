<div class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="bi bi-list"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('home') }}" class="nav-link">Home</a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="#" class="nav-link">Contact</a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Navbar Search -->
        <li class="nav-item">

            <a class="nav-link" href="{{ route('cart.index') }}" title="Cart Total: {{ $formattedTotal }}"
                style="position: relative; display: inline-flex; align-items: center;">
                <span style="position: relative; display: inline-block;">
                    <i class="bi bi-cart-fill"></i>
                    @if ($this->cartItemsCount > 0)
                        <span class="badge badge-danger"
                            style="position: absolute; top: -8px; right: -8px; display: inline-flex; justify-content: center; align-items: center; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; padding: 0;">
                            {{ $this->cartItemsCount }}
                        </span>
                    @endif
                </span>
                <small class="ml-2">{{ $formattedTotal }}</small>
            </a>

        </li>

        <!-- Notifications Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="bi bi-bell"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">Notifications</span>
                <div class="dropdown-divider"></div>
                <span class="dropdown-item text-muted text-sm">No new notifications</span>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="bi bi-arrows-fullscreen"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-widget="control-sidebar" data-controlsidebar-slide="true" href="#"
                role="button">
                <i class="bi bi-grid-3x3-gap"></i>
            </a>
        </li>
    </ul>
</div>
