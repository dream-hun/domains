<x-admin-layout>
    @section('page-title')
        Currencies
    @endsection
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Currencies</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Currencies</li>
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
                        <a href="{{ route('admin.currencies.create') }}" class="btn btn-success">
                            <i class="bi bi-plus-lg"></i> Add New Currency
                        </a>
                        <form action="{{ route('admin.currencies.update-rates') }}" method="POST"
                              style="display:inline;" class="push-right"
                              onsubmit="return confirm('Are you sure? This will update exchange rates and clear all user carts.');">
                            @csrf
                            <button type="submit" class="btn btn-info">
                                <i class="bi bi-arrow-clockwise"></i> Update Exchange Rates
                            </button>
                        </form>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Manage Currencies</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert"
                                            aria-hidden="true">×
                                    </button>
                                    <h5><i class="icon fas fa-check"></i> Success!</h5>
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert"
                                            aria-hidden="true">×
                                    </button>
                                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                                    {{ session('error') }}
                                </div>
                            @endif

                            @if ($currencies->isEmpty())
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> Info!</h5>
                                    No currencies found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table
                                        class="table table-bordered table-striped table-hover datatable-Currency w-100">
                                        <thead>
                                        <tr>
                                            <th style="width: 10%">Code</th>
                                            <th style="width: 20%">Name</th>
                                            <th style="width: 10%">Symbol</th>
                                            <th style="width: 15%">Exchange Rate</th>
                                            <th style="width: 15%">Rate Updated</th>
                                            <th style="width: 10%">Is Base</th>
                                            <th style="width: 10%">Status</th>
                                            <th style="width: 10%">Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($currencies as $currency)
                                            <tr>
                                                <td><strong>{{ $currency->code }}</strong></td>
                                                <td>{{ $currency->name }}</td>
                                                <td>{{ $currency->symbol }}</td>
                                                <td>
                                                    @if($currency->is_base)
                                                        {{ $currency->formattedBaseRate() }}
                                                    @else
                                                        {{ $currency->formattedRate() }}

                                                    @endif

                                                </td>
                                                <td>
                                                    @if ($currency->rate_updated_at)
                                                        {{ $currency->rate_updated_at->format('Y-m-d H:i') }}
                                                    @else
                                                        <span class="text-muted">Never</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($currency->is_base)
                                                        <span class="badge badge-primary">Base</span>
                                                    @else
                                                        <span class="badge badge-secondary">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($currency->is_active)
                                                        <span class="badge badge-success">Active</span>
                                                    @else
                                                        <span class="badge badge-danger">Inactive</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="{{ route('admin.currencies.edit', $currency->id) }}"
                                                           class="btn btn-warning btn-sm" title="Edit">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </a>

                                                        @if (!$currency->is_base)
                                                            <form
                                                                action="{{ route('admin.currencies.destroy', $currency->id) }}"
                                                                method="POST" style="display:inline-block;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm"
                                                                        onclick="return confirm('Are you sure you want to delete this currency?');"
                                                                        title="Delete">
                                                                    <span class="bi bi-trash"></span> Delete
                                                                </button>
                                                            </form>
                                                        @endif
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
            .datatable-Currency {
                width: 100% !important;
                table-layout: fixed;
            }
        </style>
    @endsection

    @section('scripts')
        @parent
        <script>
            $(function () {
                let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
                let table = $('.datatable-Currency:not(.ajaxTable)').DataTable({
                    buttons: dtButtons,
                    paging: false,
                    searching: true,
                    ordering: true,
                    info: false,
                    lengthChange: false,
                    dom: 'Bfrtip',
                    autoWidth: false,
                    language: {
                        search: "Search:",
                        searchPlaceholder: "Search currencies..."
                    }
                })
            })
        </script>
    @endsection
</x-admin-layout>
