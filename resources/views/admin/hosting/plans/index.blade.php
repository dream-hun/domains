<x-admin-layout>
    @section('page-title')
        Hosting Plans
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Hosting Plans</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Hosting Plans</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="py-lg-2">
                        @can('hosting_plan_create')
                            <a href="{{ route('admin.hosting-plans.create') }}" class="btn btn-success">
                                <i class="bi bi-plus-lg"></i> Add Hosting Plan
                            </a>
                        @endcan
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Available Plans</h3>
                        </div>
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert"
                                        aria-hidden="true">×</button>
                                    <h5><i class="icon fas fa-check"></i> Success!</h5>
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if ($plans->isEmpty())
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> Info!</h5>
                                    No hosting plans found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table
                                        class="table table-bordered table-striped table-hover datatable-HostingPlan w-100">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th>Status</th>
                                                <th>Popular</th>
                                                <th>Sort Order</th>
                                                <th>Created</th>
                                                <th class="text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($plans as $plan)
                                                <tr>
                                                    <td>{{ $plan->name }}</td>
                                                    <td>{{ $plan->category?->name ?? '—' }}</td>
                                                    <td>
                                                        @if ($plan->status)
                                                            <span
                                                                class="{{ $plan->status->badge() }}">{{ $plan->status->label() }}</span>
                                                        @else
                                                            <span class="badge bg-secondary">Unknown</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($plan->is_popular)
                                                            <span class="badge bg-success">Yes</span>
                                                        @else
                                                            <span class="badge bg-secondary">No</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $plan->sort_order }}</td>
                                                    <td>{{ $plan->created_at?->format('M d, Y') }}</td>
                                                    <td class="text-right">
                                                        <div class="btn-group">
                                                            @can('hosting_plan_edit')
                                                                <a href="{{ route('admin.hosting-plans.edit', $plan) }}"
                                                                    class="btn btn-sm btn-primary" title="Edit">
                                                                    <i class="bi bi-pencil"></i> Edit
                                                                </a>
                                                            @endcan
                                                            @can('hosting_plan_delete')
                                                                <form
                                                                    action="{{ route('admin.hosting-plans.destroy', $plan) }}"
                                                                    method="POST" class="d-inline"
                                                                    onsubmit="return confirm('Are you sure you want to delete this plan?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                                        title="Delete">
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
                    <div class="d-flex justify-content-center mt-3 float-right">
                        {{ $plans->links('vendor.pagination.adminlte') }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('styles')
        @parent
        <style>
            .datatable-HostingPlan {
                width: 100% !important;
                table-layout: fixed;
            }

            .datatable-HostingPlan th:nth-child(1),
            .datatable-HostingPlan td:nth-child(1) {
                width: 20%;
            }

            .datatable-HostingPlan th:nth-child(2),
            .datatable-HostingPlan td:nth-child(2) {
                width: 15%;
            }

            .datatable-HostingPlan th:nth-child(3),
            .datatable-HostingPlan td:nth-child(3) {
                width: 12%;
            }

            .datatable-HostingPlan th:nth-child(4),
            .datatable-HostingPlan td:nth-child(4) {
                width: 10%;
            }

            .datatable-HostingPlan th:nth-child(5),
            .datatable-HostingPlan td:nth-child(5) {
                width: 10%;
            }

            .datatable-HostingPlan th:nth-child(6),
            .datatable-HostingPlan td:nth-child(6) {
                width: 13%;
            }

            .datatable-HostingPlan th:nth-child(7),
            .datatable-HostingPlan td:nth-child(7) {
                width: 20%;
            }
        </style>
    @endsection

    @section('scripts')
        @parent
        <script>
            $(function() {
                let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
                let table = $('.datatable-HostingPlan:not(.ajaxTable)').DataTable({
                    buttons: dtButtons,
                    columnDefs: [{
                            targets: 0,
                            orderable: true
                        },
                        {
                            targets: -1,
                            orderable: false,
                            searchable: false
                        }
                    ],
                    paging: false, // Disable DataTable pagination to use Laravel pagination
                    searching: true, // Enable search
                    ordering: true, // Enable sorting
                    info: false, // Disable "Showing X to Y of Z entries" info
                    lengthChange: false, // Disable "Show X entries" dropdown
                    dom: 'Bfrtip', // B=buttons, f=filter(search), r=processing, t=table, i=info, p=pagination
                    autoWidth: false, // Disable auto width calculation
                    language: {
                        search: "Search:",
                        searchPlaceholder: "Search hosting plans..."
                    }
                })

                $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e) {
                    $($.fn.dataTable.tables(true)).DataTable()
                        .columns.adjust();
                });
            })
        </script>
    @endsection
</x-admin-layout>
