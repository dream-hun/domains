<x-admin-layout>
    @section('page-title')
        VPS Instances
    @endsection

    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>VPS Instances</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">VPS Instances</li>
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

            <div class="row mb-4">
                <div class="col-md-12">
                    @can('vps_assign')
                        <a href="{{ route('admin.vps.assign') }}" class="btn btn-primary btn-md">
                            <i class="fas fa-link"></i> Assign VPS Instance
                        </a>
                    @endcan
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-server mr-1"></i>
                        All VPS Instances
                    </h3>
                </div>
                <div class="card-body">
                    @if ($errorMessage)
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> {{ $errorMessage }}
                        </div>
                    @elseif ($instances->isEmpty())
                        <div class="text-center py-5">
                            <i class="bi bi-server bi-3x text-muted mb-3"></i>
                            <p class="text-muted">No VPS instances found.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Instance</th>
                                        <th>Status</th>
                                        <th>IP Address</th>
                                        <th>Product</th>
                                        <th>Assignment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($instances as $inst)
                                        <tr>
                                            <td class="align-middle">
                                                @if ($inst['assigned'])
                                                    <a href="{{ route('admin.vps.show', $inst['subscription_uuid']) }}">
                                                        <strong>{{ $inst['display_name'] ?: $inst['name'] }}</strong>
                                                    </a>
                                                @else
                                                    <strong>{{ $inst['display_name'] ?: $inst['name'] }}</strong>
                                                @endif
                                                <br>
                                                <small class="text-muted">ID: {{ $inst['instance_id'] }}</small>
                                            </td>
                                            <td class="align-middle">
                                                <span class="badge {{ $inst['status_color'] }}">
                                                    <i class="{{ $inst['status_icon'] }}"></i>
                                                    {{ $inst['status_label'] }}
                                                </span>
                                            </td>
                                            <td class="align-middle"><code>{{ $inst['ip_address'] }}</code></td>
                                            <td class="align-middle">{{ $inst['product_type'] }}</td>
                                            <td class="align-middle">
                                                @if ($inst['assigned'])
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check"></i> Assigned
                                                    </span>
                                                    <br>
                                                    <small class="text-muted">{{ $inst['user_name'] }} — {{ $inst['plan_name'] }}</small>
                                                @else
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-minus"></i> Unassigned
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                <div class="btn-group btn-group-sm">
                                                    @if ($inst['assigned'])
                                                        @if ($inst['status'] === 'stopped')
                                                            @can('vps_start')
                                                                <form method="POST" action="{{ route('admin.vps.start', $inst['subscription_uuid']) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to start this instance?')">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-outline-success btn-sm" title="Start">
                                                                        <i class="fas fa-play"></i>
                                                                    </button>
                                                                </form>
                                                            @endcan
                                                        @endif
                                                        @if ($inst['status'] === 'running')
                                                            @can('vps_restart')
                                                                <form method="POST" action="{{ route('admin.vps.restart', $inst['subscription_uuid']) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to restart this instance?')">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-outline-warning btn-sm" title="Restart">
                                                                        <i class="fas fa-redo"></i>
                                                                    </button>
                                                                </form>
                                                            @endcan
                                                            @can('vps_shutdown')
                                                                <form method="POST" action="{{ route('admin.vps.shutdown', $inst['subscription_uuid']) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to shut down this instance?')">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Shutdown">
                                                                        <i class="fas fa-power-off"></i>
                                                                    </button>
                                                                </form>
                                                            @endcan
                                                        @endif
                                                        <a href="{{ route('admin.vps.show', $inst['subscription_uuid']) }}"
                                                           class="btn btn-outline-info btn-sm"
                                                           title="Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    @else
                                                        @can('vps_assign')
                                                            <a href="{{ route('admin.vps.assign') }}"
                                                               class="btn btn-outline-primary btn-sm"
                                                               title="Assign to subscription">
                                                                <i class="fas fa-link"></i> Assign
                                                            </a>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap mt-3">

                            <div>
                                {{ $instances->links('pagination.adminlte') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-admin-layout>
