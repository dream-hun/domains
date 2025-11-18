<x-admin-layout>
    @section('page-title')
        Hosting Plan Prices
    @endsection
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Hosting Plan Prices</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Hosting Plan Prices</li>
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
                        @can('hosting_plan_price_create')
                            <a href="{{ route('admin.hosting-plan-prices.create') }}" class="btn btn-success">
                                <i class="bi bi-plus-lg"></i> Add New Price
                            </a>
                        @endcan
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Manage Hosting Plan Prices</h3>
                            </div>
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

                            @if ($prices->isEmpty())
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> Info!</h5>
                                    No hosting plan prices found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table
                                        class="table table-bordered table-striped table-hover datatable-HostingPlanPrice w-100">
                                        <thead>
                                            <tr>
                                                <th style="width: 15%">Plan</th>
                                                <th style="width: 12%">Billing Cycle</th>
                                                <th style="width: 12%">Regular Price</th>
                                                <th style="width: 12%">Promotional Price</th>
                                                <th style="width: 12%">Renewal Price</th>
                                                <th style="width: 10%">Discount %</th>
                                                <th style="width: 10%">Status</th>
                                                <th style="width: 17%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($prices as $price)
                                                <tr>
                                                    <td>{{ $price->plan->name ?? 'N/A' }}</td>
                                                    <td>
                                                        <span class="badge badge-info">
                                                            {{ ucfirst(str_replace('-', ' ', $price->billing_cycle)) }}
                                                        </span>
                                                    </td>
                                                    <td>${{ number_format($price->regular_price / 100, 2) }}</td>
                                                    <td>
                                                        @if (!is_null($price->promotional_price))
                                                            ${{ number_format($price->promotional_price / 100, 2) }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td>${{ number_format($price->renewal_price / 100, 2) }}</td>
                                                    <td>
                                                        @if (!is_null($price->discount_percentage))
                                                            {{ $price->discount_percentage }}%
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if (isset($price->status) && method_exists($price->status, 'label'))
                                                            <span
                                                                class="badge {{ $price->status->color() }}">{{ $price->status->label() }}</span>
                                                        @else
                                                            <span
                                                                class="badge badge-secondary">{{ ucfirst((string) $price->status) }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            @can('hosting_plan_price_edit')
                                                            <a href="{{ route('admin.hosting-plan-prices.edit', $price->uuid) }}"
                                                                class="btn btn-warning btn-sm" title="Edit">
                                                                <i class="bi bi-pencil"></i> Edit
                                                            </a>
                                                            @endcan
                                                            @can('hosting_plan_price_delete')
                                                            <form
                                                                action="{{ route('admin.hosting-plan-prices.destroy', $price->uuid) }}"
                                                                method="POST" style="display:inline-block;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm"
                                                                    onclick="return confirm('Are you sure you want to delete this price?');"
                                                                    title="Delete">
                                                                    <span class="bi bi-trash"></span> Delete
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
                        {{ $prices->links('vendor.pagination.adminlte') }}
                    </div>
                </div>

            </div>
        </div>
    </section>
    @section('styles')
        @parent
        <style>
            .datatable-HostingPlanPrice {
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
                let table = $('.datatable-HostingPlanPrice:not(.ajaxTable)').DataTable({
                    buttons: dtButtons,
                    paging: false, // Disable DataTable pagination to use Laravel pagination
                    searching: true, // Enable search
                    ordering: true, // Enable sorting
                    info: false, // Disable "Showing X to Y of Z entries" info
                    lengthChange: false, // Disable "Show X entries" dropdown
                    dom: 'Bfrtip', // B=buttons, f=filter(search), r=processing, t=table, i=info, p=pagination
                    autoWidth: false, // Disable auto width calculation
                    language: {
                        search: "Search:",
                        searchPlaceholder: "Search hosting plan prices..."
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
