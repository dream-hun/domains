<div>
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                        class="bi bi-columns-gap"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('dashboard') }}" class="nav-link">Home</a>
            </li>

        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item position-relative">
                <a class="nav-link" href="{{ route('cart.index') }}">
                    <i class="bi bi-cart"></i>
                    <span class="badge badge-danger navbar-badge"
                        style="font-size: 13px;">{{ $this->cartItemsCount }}</span>

                </a>
            </li>

            <!-- Notifications Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" wire:click="$refresh">
                    <i class="bi bi-bell-fill"></i>
                    @if ($this->unreadNotificationsCount > 0)
                        <span class="badge badge-warning navbar-badge">{{ $this->unreadNotificationsCount }}</span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-header">
                        {{ $this->unreadNotificationsCount }} Unread Notifications
                        @if ($this->unreadNotificationsCount > 0)
                            <button wire:click="markAllNotificationsAsRead"
                                class="btn btn-sm btn-outline-secondary ml-2">
                                Mark All Read
                            </button>
                        @endif
                    </span>
                    <div class="dropdown-divider"></div>

                    @forelse($this->recentNotifications as $notification)
                        <a href="#" class="dropdown-item {{ $notification->read_at ? '' : 'font-weight-bold' }}"
                            wire:click="markNotificationAsRead('{{ $notification->id }}')">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    @if ($notification->type === 'App\Notifications\DomainRegisteredNotification')
                                        <i class="fas fa-globe mr-2 text-success"></i>
                                        <strong>Domain Registered:</strong>
                                        {{ $notification->data['domain_name'] ?? 'Unknown' }}
                                    @elseif($notification->type === 'App\Notifications\DomainImportedNotification')
                                        <i class="fas fa-download mr-2 text-info"></i>
                                        <strong>Domain Imported:</strong>
                                        {{ $notification->data['domain_name'] ?? 'Unknown' }}
                                    @else
                                        <i class="fas fa-bell mr-2"></i>
                                        {{ $notification->data['message'] ?? 'New notification' }}
                                    @endif
                                </div>
                                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                    @empty
                        <a href="#" class="dropdown-item text-center text-muted">
                            <i class="fas fa-bell-slash mr-2"></i>
                            No notifications yet
                        </a>
                    @endforelse

                    <a href="{{ route('admin.notifications.index') }}" class="dropdown-item dropdown-footer">See All
                        Notifications</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                    <i class="bi bi-ui-checks-grid"></i>
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

</div>
