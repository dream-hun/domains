<x-admin-layout>
    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <a class="btn btn-success" href="{{ route('domains.register') }}">
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

                            <td>

                                {{ $loop->iteration }}

                            </td>

                            <td>
                                {{ $domain->name ?? '' }}
                            </td>
                            <td>
                                {{ $domain->status ?? '' }}
                            </td>
                            <td>
                                {{ $domain->expires_at ?? '' }}
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    @can('domain_show')
                                        <a href="{{ route('admin.domains.info',$domain->uuid) }}"
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-info-circle"></i> Info
                                        </a>
                                    @endcan

                                    @can('domain_edit')
                                        <a href="{{ route('admin.domains.nameservers.edit', $domain->uuid) }}"
                                           class="btn btn-sm btn-warning">
                                            <i class="fas fa-server"></i> Nameservers
                                        </a>
                                    @endcan

                                    @can('domain_renew')
                                        <a href="{{ route('admin.domains.renew', $domain->uuid) }}"
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-redo"></i> Renew
                                        </a>
                                    @endcan

                                    @can('domain_transfer')
                                        <a href="{{ route('admin.domains.transfer', $domain->uuid) }}"
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-send-check"></i> Transfer Domain
                                        </a>
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
</x-admin-layout>
