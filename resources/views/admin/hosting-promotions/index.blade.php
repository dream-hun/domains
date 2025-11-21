<x-admin-layout>
    @section('page-title')
        Hosting Promotions
    @endsection
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Hosting Promotions</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Hosting Promotions</li>
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
                        @can('hosting_promotion_create')
                            <a href="{{ route('admin.hosting-promotions.create') }}" class="btn btn-success">
                                <i class="bi bi-plus-lg"></i> New Promotion
                            </a>
                        @endcan
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Manage Hosting Promotions</h3>
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

                            @if ($promotions->isEmpty())
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> Info!</h5>
                                    No hosting promotions found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table
                                        class="table table-bordered table-striped table-hover datatable-HostingPromotions w-100">
                                        <thead>
                                            <tr>
                                                <th style="width: 20%">Plan</th>
                                                <th style="width: 15%">Category</th>
                                                <th style="width: 15%">Billing Cycle</th>
                                                <th style="width: 10%">Discount</th>
                                                <th style="width: 20%">Schedule</th>
                                                <th style="width: 10%">Status</th>
                                                <th style="width: 10%" class="no-sort">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $now = now();
                                            @endphp
                                            @foreach ($promotions as $promotion)
                                                @php
                                                    if ($now->between($promotion->starts_at, $promotion->ends_at)) {
                                                        $status = ['label' => 'Active', 'class' => 'badge-success'];
                                                    } elseif ($now->lt($promotion->starts_at)) {
                                                        $status = ['label' => 'Scheduled', 'class' => 'badge-info'];
                                                    } else {
                                                        $status = ['label' => 'Expired', 'class' => 'badge-secondary'];
                                                    }
                                                @endphp
                                                <tr>
                                                    <td>{{ $promotion->plan?->name ?? 'N/A' }}</td>
                                                    <td>{{ $promotion->plan?->category?->name ?? 'N/A' }}</td>
                                                    <td>
                                                        <span class="badge badge-primary">
                                                            {{ ucfirst(str_replace('-', ' ', $promotion->billing_cycle)) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong>{{ number_format($promotion->discount_percentage, 2) }}%</strong>
                                                    </td>
                                                    <td>
                                                        <div class="text-muted">
                                                            <div><i class="bi bi-clock"></i> Starts:
                                                                {{ $promotion->starts_at->format('M d, Y H:i') }}</div>
                                                            <div><i class="bi bi-flag"></i> Ends:
                                                                {{ $promotion->ends_at->format('M d, Y H:i') }}</div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge {{ $status['class'] }}">{{ $status['label'] }}</span>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        @can('hosting_promotion_edit')
                                                            <a href="{{ route('admin.hosting-promotions.edit', $promotion->uuid) }}"
                                                                class="btn btn-warning btn-sm mr-1" title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                        @endcan
                                                        @can('hosting_promotion_delete')
                                                            <form
                                                                action="{{ route('admin.hosting-promotions.destroy', $promotion->uuid) }}"
                                                                method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-sm"
                                                                    onclick="return confirm('Are you sure you want to delete this promotion?');"
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
                    <div class="d-flex justify-content-center mt-3 float-right">
                        {{ $promotions->links('vendor.pagination.adminlte') }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('styles')
        @parent
        <style>
            .datatable-HostingPromotions {
                width: 100% !important;
                table-layout: fixed;
            }
            /* Make cursor icon indicate disabled sorting on last column header */
            th.no-sort {
                pointer-events: none;
                cursor: default;
            }
        </style>
    @endsection

    @section('scripts')
        @parent
        <script>
            $(function() {
                let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons);
                let table = $('.datatable-HostingPromotions:not(.ajaxTable)').DataTable({
                    buttons: dtButtons,
                    paging: false,
                    searching: true,
                    ordering: true,
                    info: false,
                    lengthChange: false,
                    dom: 'Bfrtip',
                    autoWidth: false,
                    columnDefs: [
                        { orderable: false, targets: -1 } // Make last column (Actions) not orderable
                    ],
                    language: {
                        search: "Search:",
                        searchPlaceholder: "Search promotions..."
                    }
                });

                $('a[data-toggle="tab"]').on('shown.bs.tab click', function() {
                    $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
                });
            });
        </script>
    @endsection
</x-admin-layout>
