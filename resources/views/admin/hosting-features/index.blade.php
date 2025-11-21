<x-admin-layout>
    @section('page-title')
        Hosting Features
    @endsection

    <section class="content-header">
        <div class="container-fluid col-md-12">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Hosting Features</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Hosting Features</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="row mb-2 push-right">
            <div class="col-lg-12">
                @can('hosting_feature_create')
                    <a class="btn btn-success push-right" href="{{ route('admin.hosting-features.create') }}">
                        <i class="bi bi-plus-circle"></i> Create Feature
                    </a>
                @endcan
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Hosting Features</h3>
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

                @if ($hostingFeatures->isEmpty())
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Info!</h5>
                        No hosting features found.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover datatable-HostingFeature w-100">
                            <thead>
                                <tr>
                                    <th style="width: 20%">Name</th>
                                    <th style="width: 10%">Category</th>
                                    <th style="width: 10%">Value Type</th>
                                    <th style="width: 8%">Unit</th>
                                    <th style="width: 8%">Sort Order</th>
                                    <th style="width: 8%">Highlighted</th>
                                    <th style="width: 8%">Created At</th>
                                    <th style="width: 10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hostingFeatures as $feature)
                                    <tr>
                                        <td><strong>{{ $feature->name }}</strong></td>
                                        <td>
                                            @if ($feature->featureCategory)
                                                <span class="badge badge-info">{{ $feature->featureCategory->name }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($feature->value_type)
                                                <span class="badge badge-secondary">{{ ucfirst($feature->value_type) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $feature->unit ?? '-' }}</td>
                                        <td>{{ $feature->sort_order ?? 0 }}</td>
                                        <td>
                                            @if ($feature->is_highlighted)
                                                <span class="badge badge-success">Yes</span>
                                            @else
                                                <span class="badge badge-secondary">No</span>
                                            @endif
                                        </td>
                                        <td>{{ $feature->created_at ? $feature->created_at->format('Y-m-d H:i') : '-' }}</td>
                                        <td>
                                            <div class="btn-group">
                                                @can('hosting_feature_edit')
                                                    <a class="btn btn-sm btn-warning"
                                                        href="{{ route('admin.hosting-features.edit', $feature) }}"
                                                        title="Edit">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                @endcan
                                                @can('hosting_feature_delete')
                                                    <form action="{{ route('admin.hosting-features.destroy', $feature) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Are you sure you want to delete this feature? This action cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                @endcan
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
    </section>

    @section('styles')
        @parent
        <style>
            .datatable-HostingFeature {
                width: 100% !important;
                table-layout: fixed;
            }
        </style>
    @endsection

    @section('scripts')
        @parent
        <script>
            $(function() {
                let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons);
                let table = $('.datatable-HostingFeature:not(.ajaxTable)').DataTable({
                    buttons: dtButtons,

                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    lengthChange: true,
                    dom: 'Bfrtip',
                    autoWidth: false,
                    order: [
                        [0, 'asc']
                    ],
                    // There are 8 columns (0-7), with Actions as the last (index 7) - only Actions should not be sortable
                    columnDefs: [{
                        orderable: false,
                        targets: [7],
                        searchable: false
                    }],
                    pageLength: 25,
                    language: {
                        search: "Search:",
                        searchPlaceholder: "Search features..."
                    }
                })
            })
        </script>
    @endsection
</x-admin-layout>
