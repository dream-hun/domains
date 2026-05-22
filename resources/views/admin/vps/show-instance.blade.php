<x-admin-layout>
    @section('page-title')
        Instance #{{ $instanceId }}
    @endsection

    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-server mr-2"></i>Instance #{{ $instanceId }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.vps.index') }}">VPS Instances</a></li>
                        <li class="breadcrumb-item active">Instance #{{ $instanceId }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-md-12">
                    <a href="{{ route('admin.vps.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Instances
                    </a>
                    @can('vps_assign')
                        <a href="{{ route('admin.vps.assign') }}" class="btn btn-primary btn-sm ml-2">
                            <i class="fas fa-link"></i> Assign Instance
                        </a>
                    @endcan
                </div>
            </div>

            @if ($errorMessage)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> {{ $errorMessage }}
                </div>
            @elseif (empty($instance))
                <div class="alert alert-info">No instance data available.</div>
            @else
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-server mr-1"></i>
                            {{ $instance['displayName'] ?? $instance['name'] ?? 'Instance #'.$instanceId }}
                            <small class="text-muted ml-2">ID: {{ $instance['instanceId'] }}</small>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        <tr>
                                            <th style="width: 40%">Status</th>
                                            <td>{{ $instance['status'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Product</th>
                                            <td>{{ $instance['productType'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Default User</th>
                                            <td>{{ $instance['defaultUser'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>OS Type</th>
                                            <td>{{ $instance['osType'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Created</th>
                                            <td>{{ $instance['createdDate'] ?? 'N/A' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        <tr>
                                            <th style="width: 40%">IPv4</th>
                                            <td><code>{{ $instance['ipConfig']['v4']['ip'] ?? 'N/A' }}</code></td>
                                        </tr>
                                        <tr>
                                            <th>IPv6</th>
                                            <td><code>{{ $instance['ipConfig']['v6']['ip'] ?? 'N/A' }}</code></td>
                                        </tr>
                                        <tr>
                                            <th>CPU Cores</th>
                                            <td>{{ $instance['cpuCores'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>RAM</th>
                                            <td>{{ $instance['ramMb'] ? number_format($instance['ramMb'] / 1024, 1).' GB' : 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Disk</th>
                                            <td>{{ $instance['diskMb'] ? number_format($instance['diskMb'] / 1024, 0).' GB' : 'N/A' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
</x-admin-layout>
