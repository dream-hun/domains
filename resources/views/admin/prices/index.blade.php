<x-admin-layout>
    @section('page-title')
        Domain Prices
    @endsection
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Domain Prices</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Domain Prices</li>
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
                        <a href="{{ route('admin.prices.create') }}" class="btn btn-success">
                            <i class="bi bi-plus-lg"></i> Add New Price
                        </a>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Manage Domain Prices</h3>
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
                                    No domain prices found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table
                                        class="table table-bordered table-striped table-hover datatable-DomainPrice w-100">
                                        <thead>
                                            <tr>
                                                <th style="width: 10%">TLD</th>
                                                <th style="width: 12%">Type</th>
                                                <th style="width: 15%">Register</th>
                                                <th style="width: 15%">Renewal</th>
                                                <th style="width: 15%">Transfer</th>
                                                <th style="width: 15%">Redemption</th>
                                                <th style="width: 18%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($prices as $price)
                                                <tr>
                                                    <td>{{ $price->tld }}</td>
                                                    <td>
                                                        @if (isset($price->type) && method_exists($price->type, 'label'))
                                                            <span
                                                                class="badge {{ $price->type->color() }}">{{ $price->type->label() }}</span>
                                                        @else
                                                            <span
                                                                class="badge badge-secondary">{{ ucfirst((string) $price->type) }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $price->getFormattedPrice('register_price') }}</td>
                                                    <td>{{ $price->getFormattedPrice('renewal_price') }}</td>
                                                    <td>{{ $price->getFormattedPrice('transfer_price') }}</td>
                                                    <td>
                                                        @if (!is_null($price->redemption_price))
                                                            {{ $price->getFormattedPrice('redemption_price') }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="{{ route('admin.prices.edit', $price->uuid) }}"
                                                                class="btn btn-warning btn-sm" title="Edit">
                                                                <i class="bi bi-pencil"></i> Edit
                                                            </a>

                                                            <form
                                                                action="{{ route('admin.prices.destroy', $price->uuid) }}"
                                                                method="POST" style="display:inline-block;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm"
                                                                    onclick="return confirm('Are you sure you want to delete this price?');"
                                                                    title="Delete">
                                                                    <span class="bi bi-trash"></span> Delete
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
            .datatable-DomainPrice {
                width: 100% !important;
                table-layout: fixed;
            }

            .datatable-DomainPrice th:nth-child(1) {
                width: 10%;
            }

            /* TLD */
            .datatable-DomainPrice th:nth-child(2) {
                width: 12%;
            }

            /* Type */
            .datatable-DomainPrice th:nth-child(3) {
                width: 15%;
            }

            /* Register */
            .datatable-DomainPrice th:nth-child(4) {
                width: 15%;
            }

            /* Renewal */
            .datatable-DomainPrice th:nth-child(5) {
                width: 15%;
            }

            /* Transfer */
            .datatable-DomainPrice th:nth-child(6) {
                width: 15%;
            }

            /* Redemption */
            .datatable-DomainPrice th:nth-child(7) {
                width: 18%;
            }

            /* Actions */

            .datatable-DomainPrice td:nth-child(1) {
                width: 10%;
            }

            .datatable-DomainPrice td:nth-child(2) {
                width: 12%;
            }

            .datatable-DomainPrice td:nth-child(3) {
                width: 15%;
            }

            .datatable-DomainPrice td:nth-child(4) {
                width: 15%;
            }

            .datatable-DomainPrice td:nth-child(5) {
                width: 15%;
            }

            .datatable-DomainPrice td:nth-child(6) {
                width: 15%;
            }

            .datatable-DomainPrice td:nth-child(7) {
                width: 18%;
            }
        </style>
    @endsection

    @section('scripts')
        @parent
        <script>
            $(function() {
                let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
                let table = $('.datatable-DomainPrice:not(.ajaxTable)').DataTable({
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
                        searchPlaceholder: "Search domain prices..."
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
