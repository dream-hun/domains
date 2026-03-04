<x-admin-layout>
    @section('page-title')
        Hosting Plan Price History
    @endsection

    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Hosting Plan Price History</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Hosting Plan Price History</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Price Change History</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.hosting-plan-price-history.index') }}" class="mb-4" id="filters-form">
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label for="search">Search Plan</label>
                                <input type="text" 
                                       id="search" 
                                       name="search" 
                                       class="form-control realtime-filter"
                                       placeholder="e.g., Basic Plan, Pro Plan"
                                       value="{{ $filters['search'] }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="changed_by">Changed By</label>
                                <select id="changed_by" name="changed_by" class="form-control select2bs4 realtime-filter">
                                    <option value="">All users</option>
                                    @foreach ($userOptions as $user)
                                        <option value="{{ $user['id'] }}" @selected($filters['changed_by'] == $user['id'])>
                                            {{ $user['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="date_from">Date From</label>
                                <input type="date" 
                                       id="date_from" 
                                       name="date_from" 
                                       class="form-control realtime-filter"
                                       value="{{ $filters['date_from'] }}">
                            </div>
                            <div class="form-group col-md-2">
                                <label for="date_to">Date To</label>
                                <input type="date" 
                                       id="date_to" 
                                       name="date_to" 
                                       class="form-control realtime-filter"
                                       value="{{ $filters['date_to'] }}">
                            </div>
                            <div class="form-group col-md-2 d-flex align-items-end">
                                <a href="{{ route('admin.hosting-plan-price-history.index') }}" class="btn btn-secondary btn-block">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    @if ($histories->isEmpty())
                        <div class="alert alert-info">
                            <h5>No price history found</h5>
                            Try adjusting your filters or check back later.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Hosting Plan</th>
                                        <th>Currency</th>
                                        <th>Billing Cycle</th>
                                        <th>Regular Price</th>
                                        <th>Renewal Price</th>
                                        <th>Changed By</th>
                                        <th>Reason</th>
                                        <th>Date Changed</th>
                                        <th>Changes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($histories as $history)
                                        @php
                                            $currency = $history->hostingPlanPrice?->currency;
                                            $currencyCode = $currency?->code ?? 'USD';
                                            $plan = $history->hostingPlanPrice?->plan;
                                            $billingCycle = $history->hostingPlanPrice?->billing_cycle ?? '—';
                                        @endphp
                                        <tr>
                                            <td class="align-middle">
                                                <strong>{{ $plan?->name ?? '—' }}</strong>
                                            </td>
                                            <td class="align-middle">
                                                {{ $currencyCode }}
                                            </td>
                                            <td class="align-middle">
                                                {{ $billingCycle }}
                                            </td>
                                            <td class="align-middle">
                                                @if ($history->regular_price !== null)
                                                    @price($history->regular_price, $currencyCode)
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                @if ($history->renewal_price !== null)
                                                    @price($history->renewal_price, $currencyCode)
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                @if ($history->changedBy)
                                                    {{ trim(($history->changedBy->first_name ?? '') . ' ' . ($history->changedBy->last_name ?? '')) }}
                                                    <br>
                                                    <small class="text-muted">{{ $history->changedBy->email }}</small>
                                                @else
                                                    <span class="badge badge-light">System</span>
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                {{ $history->reason ?? '—' }}
                                            </td>
                                            <td class="align-middle">
                                                <div>{{ $history->created_at->format('M d, Y') }}</div>
                                                <small class="text-muted">{{ $history->created_at->format('H:i:s') }}</small>
                                            </td>
                                            <td class="align-middle" style="min-width: 200px;">
                                                @if ($history->changes || $history->old_values)
                                                    <details>
                                                        <summary class="text-primary" style="cursor: pointer;">View Changes</summary>
                                                        <div class="mt-2">
                                                            @if ($history->old_values)
                                                                <div class="mb-2">
                                                                    <strong class="text-danger">Old Values:</strong>
                                                                    <pre class="bg-light rounded p-2 small mb-0">{{ json_encode($history->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                                </div>
                                                            @endif
                                                            @if ($history->changes)
                                                                <div>
                                                                    <strong class="text-success">Changes:</strong>
                                                                    <pre class="bg-light rounded p-2 small mb-0">{{ json_encode($history->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </details>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap mt-3">
                            <div class="text-muted small mb-2">
                                Showing {{ $histories->firstItem() ?? 0 }} - {{ $histories->lastItem() ?? 0 }} of {{ $histories->total() }} results
                            </div>
                            <div>
                                {{ $histories->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @section('styles')
        @parent
        <style>
            .table td,
            .table th {
                vertical-align: middle;
            }
        </style>
    @endsection

    @section('scripts')
        @parent
        <script>
            $(document).ready(function() {
                $('#changed_by').select2({
                    theme: 'bootstrap4',
                    width: '100%'
                });

                let searchTimeout;

                // Real-time filtering for select inputs and date inputs
                $('.realtime-filter').on('change', function() {
                    $('#filters-form').submit();
                });

                // Real-time filtering for search input with debounce
                $('#search').on('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function() {
                        $('#filters-form').submit();
                    }, 500); // Wait 500ms after user stops typing
                });

                // Clear search timeout on form submit
                $('#filters-form').on('submit', function() {
                    clearTimeout(searchTimeout);
                });
            });
        </script>
    @endsection
</x-admin-layout>
