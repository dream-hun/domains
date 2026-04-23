<x-admin-layout>
    @section('page-title')
        Assign VPS Instance
    @endsection

    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-link mr-2"></i>Assign VPS Instance</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.vps.index') }}">VPS Instances</a></li>
                        <li class="breadcrumb-item active">Assign</li>
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

            @if ($errorMessage)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> {{ $errorMessage }}
                </div>
            @else
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Link a Contabo Instance to a Subscription</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.vps.assign.store') }}">
                            @csrf
                            <div class="row">
                                {{-- Subscription Selection --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="subscription_id">Select Subscription (Unassigned)</label>
                                        @if (empty($unassignedSubscriptions))
                                            <div class="alert alert-info">No unassigned subscriptions available.</div>
                                        @else
                                            <select name="subscription_id" id="subscription_id" class="form-control @error('subscription_id') is-invalid @enderror">
                                                <option value="">-- Select a subscription --</option>
                                                @foreach ($unassignedSubscriptions as $sub)
                                                    <option value="{{ $sub['id'] }}" {{ old('subscription_id') == $sub['id'] ? 'selected' : '' }}>
                                                        {{ $sub['plan_name'] }} - {{ $sub['domain'] }} ({{ $sub['user_name'] }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif
                                        @error('subscription_id')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Instance Selection --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="instance_id">Select Contabo Instance (Unassigned)</label>
                                        @if (empty($unassignedInstances))
                                            <div class="alert alert-info">No unassigned Contabo instances available.</div>
                                        @else
                                            <select name="instance_id" id="instance_id" class="form-control @error('instance_id') is-invalid @enderror">
                                                <option value="">-- Select an instance --</option>
                                                @foreach ($unassignedInstances as $inst)
                                                    <option value="{{ $inst['instanceId'] }}" {{ old('instance_id') == $inst['instanceId'] ? 'selected' : '' }}>
                                                        {{ $inst['displayName'] ?: $inst['name'] }} (ID: {{ $inst['instanceId'] }}) - {{ $inst['ipAddress'] }} [{{ $inst['status'] }}]
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif
                                        @error('instance_id')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <button type="submit"
                                            class="btn btn-primary"
                                            @if(empty($unassignedSubscriptions) || empty($unassignedInstances)) disabled @endif>
                                        <i class="fas fa-link"></i> Assign Instance
                                    </button>
                                    <a href="{{ route('admin.vps.index') }}" class="btn btn-secondary ml-2">
                                        <i class="fas fa-arrow-left"></i> Back to Instances
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </section>
</x-admin-layout>
