<x-admin-layout>
    @section('page-title')
        Domain Information
    @endsection
    <section class="content-header">
        <div class="container-fluid col-md-12">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Domain Information</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active"><a href="{{ route('admin.domains.index') }}">Domains</a></li>
                        <li class="breadcrumb-item active">{{ $domain->name }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        @php
                            $statusValue = $domain->status instanceof \BackedEnum
                                ? $domain->status->value
                                : (string) $domain->status;
                        @endphp
                        <table class="table table-bordered-0 table-sm">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Auto Renew</th>
                                <th>Expire At</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td style="width: 25%;">{{ $domain->name }}</td>
                                <td>
                                    <button
                                        class="btn btn-sm btn-{{ $statusValue === 'active' ? 'success' : 'warning' }}">
                                        <i class="bi bi-check-circle"></i> {{ ucfirst($statusValue) }}
                                    </button>
                                </td>
                                <td style="width: 25%;">
                                    @if($domain->auto_renew)
                                        <button class="btn btn-sm btn-success">Enabled</button>
                                    @else
                                        <button class="btn btn-sm btn-secondary">Disabled</button>
                                    @endif
                                </td>
                                <td style="width: 25%;">{{ $domain->expires_at->format('M d, Y') }}</td>

                            </tr>
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-admin-layout>
