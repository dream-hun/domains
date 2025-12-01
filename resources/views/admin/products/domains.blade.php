<x-admin-layout>
    @section('page-title')
        Domains
    @endsection

    <div class="container-fluid">
        <div class="row">
            <div class="col-12 mt-5">
                <div class="card">
                    <div class="card-header">
                        <h4>Domains</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped table-hover datatable datatable-Domain w-100">
                            <thead>
                                <tr>
                                    <th>Domain Name</th>
                                    <th>Status</th>
                                    <th>Registered At</th>
                                    <th>Expires At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($domains as $domain)
                                    <tr>
                                        <td>{{ $domain->name }}</td>
                                        <td>
                                        <span class="btn btn-sm {{ $domain->status->color() }}">
                                            <i class="bi bi-{{ $domain->status->icon() }}"></i>
                                            {{ $domain->status->label() }}
                                        </span>
                                        </td>
                                        <td>{{ $domain->registeredAt() }}</td>
                                        <td>{{ $domain->expiresAt() }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @can('domain_edit')
                                                    <a href="{{ route('admin.domains.edit', $domain->uuid) }}"
                                                        class="btn btn-sm btn-warning">
                                                        <i class="bi bi-server"></i> Manage
                                                    </a>
                                                @endcan
                                                @can('domain_renew')
                                                    @if ($domain->status !== 'expired')
                                                        <button onclick="addRenewalToCart('{{ $domain->uuid }}', '{{ $domain->name }}', {{ $domain->id }})"
                                                            class="btn btn-sm btn-success">
                                                            <i class="bi bi-cart-plus"></i> Renew
                                                        </button>
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
