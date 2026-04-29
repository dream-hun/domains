<x-admin-layout>
    @section('page-title')
        VPS Instance - {{ $instance['display_name'] ?? $instance['name'] ?? 'Details' }}
    @endsection

    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-server mr-2"></i>VPS Instance Details</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('user.vps.index') }}">My VPS Instances</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $instance['display_name'] ?? 'Details' }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            @if (session('pending_refresh'))
                <div class="alert alert-info alert-dismissible fade show" id="refresh-alert">
                    <i class="fas fa-sync-alt fa-spin mr-1"></i>
                    The power action has been sent. Page will refresh in <strong id="refresh-countdown">8</strong> seconds to show the updated status.
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            {{-- Subscription Info Card (always shown) --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-contract mr-1"></i> Subscription</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <th style="width: 30%;" class="pl-3">Plan</th>
                            <td>{{ $subscription->plan?->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">Status</th>
                            <td>
                                @php
                                    $statusBadgeClass = match($subscription->status) {
                                        'active' => 'badge-success',
                                        'expired' => 'badge-danger',
                                        'cancelled' => 'badge-secondary',
                                        'suspended' => 'badge-warning',
                                        default => 'badge-info'
                                    };
                                @endphp
                                <span class="badge {{ $statusBadgeClass }}">{{ ucfirst($subscription->status) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th class="pl-3">Billing Cycle</th>
                            <td>{{ $subscription->billing_cycle->label() }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">Start Date</th>
                            <td>{{ $subscription->starts_at?->format('F d, Y') ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">Expiry Date</th>
                            <td>
                                {{ $subscription->expires_at?->format('F d, Y') ?? 'N/A' }}
                                @if ($subscription->expires_at)
                                    <small class="text-muted ml-1">({{ $subscription->expires_at->diffForHumans() }})</small>
                                @endif
                            </td>
                        </tr>
                        @if (! $subscription->provider_resource_id)
                            <tr>
                                <th class="pl-3">VPS Instance</th>
                                <td><span class="badge badge-warning">Pending Assignment</span></td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            @if ($errorMessage)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> {{ $errorMessage }}
                </div>
            @elseif (!empty($instance))
                {{-- Overview Card --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Overview</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <th style="width: 40%;">Instance ID</th>
                                        <td>{{ $instance['instance_id'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Name</th>
                                        <td>{{ $instance['name'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Display Name</th>
                                        <td>{{ $instance['display_name'] ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge {{ $instance['status_color'] }}">
                                                <i class="{{ $instance['status_icon'] }}"></i>
                                                {{ $instance['status_label'] }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Default User</th>
                                        <td><code>{{ $instance['default_user'] }}</code></td>
                                    </tr>
                                    <tr>
                                        <th>OS Type</th>
                                        <td>{{ $instance['os_type'] }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <th style="width: 40%;">IPv4</th>
                                        <td><code>{{ $instance['ip_v4'] }}</code></td>
                                    </tr>
                                    <tr>
                                        <th>IPv6</th>
                                        <td><code>{{ $instance['ip_v6'] }}</code></td>
                                    </tr>
                                    @if (!empty($instance['cancel_date']))
                                        <tr>
                                            <th>Cancellation Date</th>
                                            <td><span class="badge badge-danger">{{ $instance['cancel_date'] }}</span></td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th>Product Type</th>
                                        <td>{{ $instance['product_type'] }}</td>
                                    </tr>
                                    <tr>
                                        <th>Resources</th>
                                        <td>{{ $instance['cpu_cores'] }} vCPU / {{ round(($instance['ram_mb'] ?? 0) / 1024, 1) }} GB RAM / {{ round(($instance['disk_mb'] ?? 0) / 1024, 0) }} GB Disk</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions Panel --}}
                @include('user.vps.partials.actions-panel')

                {{-- Snapshots Panel --}}
                @can('vps_snapshot_access')
                    @include('user.vps.partials.snapshots-panel')
                @endcan

                {{-- Backups Panel --}}
                @can('vps_backup_access')
                    @include('user.vps.partials.backups-panel', ['backupError' => $backupError ?? null])
                @endcan
            @endif
        </div>
    </section>
    @if (session('pending_refresh'))
        @section('scripts')
            @parent
            <script>
                (function () {
                    let seconds = 8;
                    const el = document.getElementById('refresh-countdown');
                    const interval = setInterval(function () {
                        seconds--;
                        if (el) {
                            el.textContent = seconds;
                        }
                        if (seconds <= 0) {
                            clearInterval(interval);
                            location.reload();
                        }
                    }, 1000);
                })();
            </script>
        @endsection
    @endif
</x-admin-layout>
