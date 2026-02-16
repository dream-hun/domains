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
                    <div class="py-lg-2 d-flex justify-content-between align-items-center">
                        <div>
                            @can('hosting_plan_price_create')
                                <a href="{{ route('admin.hosting-plan-prices.create') }}" class="btn btn-success">
                                    <i class="bi bi-plus-lg"></i> Add New Price
                                </a>
                            @endcan
                        </div>
                        <form method="GET" action="{{ route('admin.hosting-plan-prices.index') }}"
                            class="d-flex align-items-center">
                            <div class="form-group mb-0 mr-2">
                                <label for="search" class="sr-only">Search</label>
                                <input type="text" name="search" id="search" class="form-control"
                                    placeholder="Search..." value="{{ $search }}">
                            </div>
                            <div class="form-group mb-0 mr-2">
                                <label for="category_id" class="sr-only">Filter by Category</label>
                                <select name="category_id" id="category_id" class="form-control">
                                    <option value="">All Categories</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->uuid }}"
                                            {{ $selectedCategoryUuid == $category->uuid ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0 mr-2">
                                <label for="plan_id" class="sr-only">Filter by Plan</label>
                                <select name="plan_id" id="plan_id" class="form-control">
                                    <option value="">All Plans</option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->uuid }}"
                                            {{ $selectedPlanUuid == $plan->uuid ? 'selected' : '' }}>
                                            {{ $plan->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0 mr-2">
                                <label for="currency_id" class="sr-only">Filter by Currency</label>
                                <select name="currency_id" id="currency_id_filter" class="form-control">
                                    <option value="">All Currencies</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ $selectedCurrencyId == $currency->id ? 'selected' : '' }}>
                                            {{ $currency->code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($selectedCategoryUuid || $selectedPlanUuid || $selectedCurrencyId || $search)
                                <a href="{{ route('admin.hosting-plan-prices.index') }}"
                                    class="btn btn-secondary btn-sm">
                                    <i class="bi bi-x-lg"></i> Clear Filter
                                </a>
                                <button type="submit" class="btn btn-primary btn-sm ml-2">
                                    <i class="bi bi-search"></i>
                                </button>
                            @else
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-search"></i>
                                </button>
                            @endif
                        </form>
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
                                                <th style="width: 12%">Category</th>
                                                <th style="width: 15%">Plan</th>
                                                <th style="width: 8%">Currency</th>
                                                <th style="width: 10%">Billing Cycle</th>
                                                <th style="width: 12%">Regular Price</th>
                                                <th style="width: 12%">Renewal Price</th>
                                                <th style="width: 7%">Status</th>
                                                <th style="width: 7%">Current</th>
                                                <th style="width: 9%">Effective Date</th>
                                                <th style="width: 8%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($prices as $price)
                                                <tr>
                                                    <td>{{ $price->plan?->category?->name ?? 'N/A' }}</td>
                                                    <td>{{ $price->plan?->name ?? 'N/A' }}</td>
                                                    <td>
                                                        <span class="badge badge-secondary">
                                                            {{ $price->currency?->code ?? 'N/A' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info">
                                                            {{ ucfirst(str_replace('-', ' ', $price->billing_cycle)) }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $price->getFormattedPrice('regular_price') }}</td>
                                                    <td>{{ $price->getFormattedPrice('renewal_price') }}</td>
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
                                                        <span class="badge badge-{{ $price->is_current ? 'success' : 'secondary' }}">
                                                            {{ $price->is_current ? 'Yes' : 'No' }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $price->effective_date?->format('M d, Y') ?? 'N/A' }}</td>
                                                    <td class="text-nowrap">
                                                        @can('hosting_plan_price_edit')
                                                            <a href="{{ route('admin.hosting-plan-prices.edit', $price->uuid) }}"
                                                                class="btn btn-warning btn-sm mr-1" title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                        @endcan
                                                        @can('hosting_plan_price_delete')
                                                            <form
                                                                action="{{ route('admin.hosting-plan-prices.destroy', $price->uuid) }}"
                                                                method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm"
                                                                    onclick="return confirm('Are you sure you want to delete this price?');"
                                                                    title="Delete">
                                                                    <span class="bi bi-trash"></span>
                                                                </button>
                                                            </form>
                                                        @endcan
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
                const filterForm = $('#category_id').closest('form');

                // Clear plan filter when category changes, then submit
                $('#category_id').on('change', function() {
                    $('#plan_id').val('');
                    filterForm.submit();
                });

                // Submit form when plan changes
                $('#plan_id').on('change', function() {
                    filterForm.submit();
                });

                // Submit form when currency changes
                $('#currency_id_filter').on('change', function() {
                    filterForm.submit();
                });

                let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
                let table = $('.datatable-HostingPlanPrice:not(.ajaxTable)').DataTable({
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
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: false,
                    lengthChange: false,
                    dom: 'Brtip',
                    autoWidth: false,
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
