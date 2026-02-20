<x-admin-layout page-title="TLD Pricing">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">TLD Pricing</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">TLD Pricing</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid col-md-12">
            <div class="row">
                <div class="col-12">
                    <div class="py-lg-2">
                        <a href="{{ route('admin.tld-pricings.create') }}" class="btn btn-success">
                            <i class="bi bi-plus-lg"></i> Add New TLD Pricing
                        </a>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Manage TLD Pricing</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                    <h5><i class="icon fas fa-check"></i> Success!</h5>
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                                    {{ session('error') }}
                                </div>
                            @endif

                            @if ($errors->isNotEmpty())
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                    <h5><i class="icon fas fa-ban"></i> Validation Error</h5>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($tldPricings->isEmpty())
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> Info!</h5>
                                    No TLD pricing records found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table
                                        class="table table-bordered table-striped table-hover datatable-TldPricing w-100">
                                        <thead>
                                            <tr>
                                                <th style="width: 12%">TLD</th>
                                                <th style="width: 10%">Currency</th>
                                                <th style="width: 12%">Register</th>
                                                <th style="width: 12%">Renew</th>
                                                <th style="width: 10%">Current</th>
                                                <th style="width: 12%">Effective date</th>
                                                <th style="width: 12%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($tldPricings as $tldPricing)
                                                <tr>
                                                    <td>{{ $tldPricing->tld?->name ?? '—' }}</td>
                                                    <td>{{ $tldPricing->currency?->code ?? '—' }}</td>
                                                    <td>{{ number_format($tldPricing->register_price) }}</td>
                                                    <td>{{ number_format($tldPricing->renew_price) }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $tldPricing->is_current ? 'success' : 'secondary' }}">
                                                            {{ $tldPricing->is_current ? 'Yes' : 'No' }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $tldPricing->effective_date?->format('Y-m-d') }}</td>
                                                    <td class="text-nowrap">
                                                        <div class="btn-group">
                                                            <a href="{{ route('admin.tld-pricings.edit', $tldPricing) }}"
                                                                class="btn btn-warning btn-sm" title="Edit">
                                                                <i class="bi bi-pencil"></i> Edit
                                                            </a>
                                                            <form action="{{ route('admin.tld-pricings.destroy', $tldPricing) }}"
                                                                method="POST" style="display:inline-block;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm"
                                                                    onclick="return confirm('Are you sure you want to delete this TLD pricing?');"
                                                                    title="Delete">
                                                                    <i class="bi bi-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @section('styles')
        @parent
        <style>
            .datatable-TldPricing {
                width: 100% !important;
                table-layout: fixed;
            }
        </style>
    @endsection

    @section('scripts')
        @parent
        <script>
            $(function() {
                let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
                let table = $('.datatable-TldPricing:not(.ajaxTable)').DataTable({
                    buttons: dtButtons,
                    columnDefs: [
                        { targets: 0, orderable: true },
                        { targets: -1, orderable: false, searchable: false }
                    ],
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: false,
                    lengthChange: false,
                    dom: 'Bfrtip',
                    autoWidth: false,
                    language: {
                        search: "Search:",
                        searchPlaceholder: "Search TLD pricing..."
                    }
                })

                $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e) {
                    $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
                });
            })
        </script>
    @endsection
</x-admin-layout>
