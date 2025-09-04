<x-admin-layout>
    @section('page-title')
        Domains
    @endsection
    <section class="content-header">
        <div class="container-fluid col-md-12">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Manage Domains</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Domains</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <a class="btn btn-success" href="{{ route('domains') }}">
                Register Domain
            </a>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            Domains
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class=" table table-bordered table-striped table-hover datatable datatable-Domain">
                    <thead>
                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Domain Name
                        </th>
                        <th>
                            Status
                        </th>
                        <th>
                            Expiry Date
                        </th>
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($domains as $key => $domain)
                        <tr data-entry-id="{{ $domain->id }}">

                            <td style="width: 15%;">

                                {{ $loop->iteration }}

                            </td>

                            <td style="width: 30%;">
                                {{ $domain->name ?? '' }}
                            </td>
                            <td style="width: 15%;">
                                {{ $domain->status ?? '' }}
                            </td>
                            <td style="width: 20%;">
                                {{ $domain->expires_at ?? '' }}
                            </td>
                            <td style="width: 20%;">
                                <div class="btn-group" role="group">
                                    @can('domain_edit')
                                        <a href="{{ route('admin.domains.edit', $domain->uuid) }}"
                                           class="btn btn-sm btn-warning">
                                            <i class="bi bi-server"></i> Manage
                                        </a>
                                    @endcan

                                    @can('domain_renew')
                                        <a href="{{ route('admin.domains.renew', $domain->uuid) }}"
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-redo"></i> Renew
                                        </a>
                                    @endcan
                                    @can('domain_edit')
                                        @if($domain->status === 'expired')
                                            <form action="{{ route('admin.domains.reactivate', $domain->uuid) }}"
                                                  method="POST" style="display: inline-block;">
                                                @csrf
                                                <input type="hidden" name="domain" value="{{ $domain->name }}">
                                                <button type="submit" class="btn btn-sm btn-warning"
                                                        onclick="return confirm('Are you sure you want to reactivate this domain? Additional fees may apply.')">
                                                    <i class="bi bi-arrow-clockwise"></i> Reactivate
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Pagination Controls --}}
            <div class="d-flex justify-content-center mt-3">
                {{ $domains->links('vendor.pagination.adminlte') }}
            </div>
        </div>
    </div>
</x-admin-layout>
