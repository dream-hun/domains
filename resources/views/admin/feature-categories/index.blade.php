<x-admin-layout>
    @section('page-title')
        Feature Categories
    @endsection

    <section class="content-header">
        <div class="container-fluid col-md-12">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Feature Categories</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Feature Categories</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="row mb-2 push-right">
            <div class="col-lg-12">
                <a class="btn btn-success push-right" href="{{ route('admin.feature-categories.create') }}">
                    <i class="bi bi-plus-circle"></i> Create Category
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Feature Categories</h3>
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

                @if ($featureCategories->isEmpty())
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Info!</h5>
                        No feature categories found.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover datatable-FeatureCategory w-100">
                            <thead>
                                <tr>
                                    <th style="width: 5%">ID</th>
                                    <th style="width: 20%">Name</th>
                                    <th style="width: 15%">Slug</th>
                                    <th style="width: 10%">Icon</th>
                                    <th style="width: 10%">Status</th>
                                    <th style="width: 8%">Sort Order</th>
                                    <th style="width: 10%">Features Count</th>
                                    <th style="width: 12%">Created At</th>
                                    <th style="width: 10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($featureCategories as $category)
                                    <tr>
                                        <td>{{ $category->id }}</td>
                                        <td><strong>{{ $category->name }}</strong></td>
                                        <td><code>{{ $category->slug }}</code></td>
                                        <td>
                                            @if ($category->icon)
                                                <i class="{{ $category->icon }}"></i> {{ $category->icon }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($category->status)
                                                <span class="badge {{ $category->status->color() }}">
                                                    {{ $category->status->label() }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $category->sort_order ?? 0 }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $category->hosting_features_count ?? 0 }}</span>
                                        </td>
                                        <td>{{ $category->created_at ? $category->created_at->format('Y-m-d H:i') : '-' }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-warning"
                                                    href="{{ route('admin.feature-categories.edit', $category) }}"
                                                    title="Edit">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <form action="{{ route('admin.feature-categories.destroy', $category) }}" method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this category? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
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
    </section>

    @section('styles')
        @parent
        <style>
            .datatable-FeatureCategory {
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
                let table = $('.datatable-FeatureCategory:not(.ajaxTable)').DataTable({
                    buttons: dtButtons,
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    lengthChange: true,
                    dom: 'Bfrtip',
                    autoWidth: false,
                    order: [[5, 'asc']],
                    pageLength: 25,
                    language: {
                        search: "Search:",
                        searchPlaceholder: "Search categories..."
                    }
                })
            })
        </script>
    @endsection
</x-admin-layout>

