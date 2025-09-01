<x-admin-layout>

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Domain Information</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.domains.index') }}">Domains</a></li>
                        <li class="breadcrumb-item active">Domain Info</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            @if(isset($error))
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon bi bi-triangle"></i> Warning!</h5>
                    {{ $error }}
                </div>
            @endif

            <div class="row">
                <!-- Domain Details Card -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-globe mr-2"></i>
                                Domain Details
                            </h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%">Domain Name</th>
                                    <td>{{ $domainInfo->name }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @if($registrarInfo && isset($registrarInfo['status']))
                                            @foreach($registrarInfo['status'] as $status)
                                                <span class="badge badge-{{ $status === 'active' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($status) }}
                                                </span>
                                            @endforeach
                                        @else

                                            <span class="badge badge-{{ $domainInfo->status === 'active' ? 'success' : 'warning' }}">
                                                {{ ucfirst($domainInfo->status) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Lock Status</th>
                                    <td>
                                        @php
                                            $isLocked = $registrarInfo ? $registrarInfo['locked'] : $domainInfo->is_locked;
                                        @endphp
                                        <span class="badge badge-{{ $isLocked ? 'success' : 'warning' }} mr-2">
                                            {{ $isLocked ? 'Locked' : 'Unlocked' }}
                                        </span>

                                        @can('domain_transfer')
                                            <form action="{{ route('admin.domains.lock', $domainInfo) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="lock" value="{{ !$isLocked }}">
                                                <input type="hidden" name="domain_id" value="{{ $domainInfo->id }}">
                                                <button type="submit" class="btn btn-sm btn-{{ $isLocked ? 'danger' : 'success' }}">
                                                    <i class="bi bi-{{ $isLocked ? 'unlock' : 'lock' }} mr-1"></i>
                                                    {{ $isLocked ? 'Unlock Domain' : 'Lock Domain' }}
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                                <tr>
                                    <th>Registration Date</th>
                                    <td>{{ $registrarInfo['created_date'] ?? $domainInfo->registered_at->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Expiration Date</th>
                                    <td>{{ $registrarInfo['expiry_date'] ?? $domainInfo->expires_at->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Provider</th>
                                    <td>{{ $domainInfo->provider ?? 'Not set' }}</td>
                                </tr>
                                <tr>
                                    <th>Auto Renew</th>
                                    <td>
                                        @php
                                            $autoRenew = $registrarInfo ? $registrarInfo['auto_renew'] : $domainInfo->auto_renew;
                                        @endphp
                                        <span class="badge badge-{{ $autoRenew ? 'success' : 'danger' }}">
                                            {{ $autoRenew ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            @can('domain_show')
                                <form action="{{ route('admin.domains.refresh-info', $domainInfo) }}" method="POST" class="mt-3">
                                    @csrf
                                    <button type="submit" class="btn btn-info">
                                        <i class="fas fa-sync-alt mr-1"></i> Refresh Domain Info
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>

                <!-- Nameservers Card -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-server mr-2"></i>
                                Nameservers
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            @if($domainInfo->nameservers->count() > 0)
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>Hostname</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($domainInfo->nameservers as $nameserver)
                                        <tr>
                                            <td>{{ $nameserver->name }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="p-3">
                                    <p class="text-muted mb-0">No nameservers configured</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Domain Contacts -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-address-card mr-2"></i>
                                Domain Contacts
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            @if($domainInfo->contacts->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Organization</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($domainInfo->contacts as $contact)
                                            <tr>
                                                <td>
                                                            <span class="badge badge-info">
                                                                {{ ucfirst($contact->contact_type->label()) }}
                                                            </span>
                                                </td>
                                                <td>{{ $contact->first_name }} {{ $contact->last_name }}</td>
                                                <td>{{ $contact->email }}</td>
                                                <td>{{ $contact->phone }}</td>
                                                <td>{{ $contact->organization }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="p-3">
                                    <p class="text-muted mb-0">No contacts found</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

</x-admin-layout>
