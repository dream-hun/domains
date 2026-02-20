<x-admin-layout>
    @section('page-title')
        {{ trans('cruds.subscription.title') }}
    @endsection

    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ trans('cruds.subscription.title') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">{{ trans('cruds.subscription.title') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
             <div class="row mb-4">
                <div class="col-md-12">
                    @can('subscription_create')
                        <a href="{{ route('admin.subscriptions.create') }}" class="btn btn-primary btn-md">
                            Create Custom Subscription
                        </a>
                    @endcan
                </div>
            </div>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Subscription Monitor</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="mb-4" id="filters-form">
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label for="search">Search</label>
                                <input type="text" 
                                       id="search" 
                                       name="search" 
                                       class="form-control realtime-filter"
                                       placeholder="Customer or provider reference"
                                       value="{{ $filters['search'] }}">
                            </div>
                            <div class="form-group col-md-2">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control select2bs4 realtime-filter">
                                    <option value="">All statuses</option>
                                    @foreach ($statusOptions as $status)
                                        <option value="{{ $status }}" @selected($filters['status'] === $status)>
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="billing_cycle">Billing Cycle</label>
                                <select id="billing_cycle" name="billing_cycle" class="form-control select2bs4 realtime-filter">
                                    <option value="">All cycles</option>
                                    @foreach ($billingCycleOptions as $cycle)
                                        <option value="{{ $cycle }}" @selected($filters['billing_cycle'] === $cycle)>
                                            {{ ucfirst(str_replace('_', ' ', $cycle)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="starts_from">Start Date (From)</label>
                                <input type="date" 
                                       id="starts_from" 
                                       name="starts_from" 
                                       class="form-control realtime-filter"
                                       value="{{ $filters['starts_from'] }}">
                            </div>
                            <div class="form-group col-md-2">
                                <label for="starts_to">Start Date (To)</label>
                                <input type="date" 
                                       id="starts_to" 
                                       name="starts_to" 
                                       class="form-control realtime-filter"
                                       value="{{ $filters['starts_to'] }}">
                            </div>
                            <div class="form-group col-md-1 d-flex align-items-end">
                                <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary btn-block">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    @php
                        $statusColors = [
                            'active' => 'badge-success',
                            'pending' => 'badge-warning',
                            'trial' => 'badge-info',
                            'expired' => 'badge-danger',
                            'cancelled' => 'badge-secondary',
                        ];
                    @endphp

                    @if ($subscriptions->isEmpty())
                        <div class="alert alert-info">
                            <h5>No subscriptions found</h5>
                            Try adjusting your filters or check back later.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Customer Name</th>
                                        <th>Plan Name</th>
                                        <th>Regular Price</th>
                                        <th>Renewal Price</th>
                                        <th>Billing Cycle</th>
                                        <th>Status</th>
                                        <th>Start Date</th>
                                        <th>Expires At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subscriptions as $subscription)
                                        <tr>
                                            @php
                                                $customerName = trim(
                                                    ($subscription->user?->first_name ?? '') .
                                                        ' ' .
                                                        ($subscription->user?->last_name ?? ''),
                                                );
                                            @endphp
                                            <td class="align-middle">
                                                {{ $customerName !== '' ? $customerName : 'N/A' }}
                                            </td>
                                            <td class="align-middle">
                                                {{ $subscription->plan?->name ?? 'N/A' }}
                                            </td>
                                            <td class="align-middle">
                                                @if ($subscription->is_custom_price && $subscription->custom_price !== null)
                                                    <span class="badge badge-info" title="Custom Price">Custom</span>
                                                    @price($subscription->custom_price, $subscription->custom_price_currency ?? 'USD')
                                                @elseif ($subscription->planPrice)
                                                    {{ $subscription->planPrice->getFormattedPrice('regular_price') }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                @if ($subscription->is_custom_price && $subscription->custom_price !== null)
                                                    <span class="badge badge-info" title="Custom Price">Custom</span>
                                                    @price($subscription->custom_price, $subscription->custom_price_currency ?? 'USD')
                                                @elseif ($subscription->planPrice)
                                                    {{ $subscription->planPrice->getFormattedPrice('renewal_price') }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                <span class="badge badge-light">
                                                    {{ ucfirst(str_replace('_', ' ', $subscription->billing_cycle ?? '—')) }}
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                @php
                                                    $status = $subscription->status ?? 'unknown';
                                                @endphp
                                                <span class="badge {{ $statusColors[$status] ?? 'badge-dark' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                {{ $subscription->starts_at?->format('M d, Y') ?? '—' }}
                                            </td>
                                            <td class="align-middle">
                                                {{ $subscription->expires_at?->format('M d, Y') ?? '—' }}
                                            </td>
                                            <td class="align-middle">
                                                <div class="btn-group" role="group">
                                                    @can('subscription_show')
                                                        <a href="{{ route('admin.subscriptions.show', $subscription) }}"
                                                           class="btn btn-sm btn-info"
                                                           title="View Details">View</a>
                                                    @endcan
                                                    @can('subscription_edit')
                                                        <a href="{{ route('admin.subscriptions.edit', $subscription) }}"
                                                           class="btn btn-sm btn-primary"
                                                           title="Edit">Edit</a>
                                                    @endcan
                                                    <form action="{{ route('admin.products.subscription.renew', $subscription) }}"
                                                          method="POST"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn btn-sm btn-success"
                                                                title="Add Renewal to Cart">Renew</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap mt-3">
                            <div class="text-muted small mb-2">
                                Showing {{ $subscriptions->firstItem() ?? 0 }} - {{ $subscriptions->lastItem() ?? 0 }} of {{ $subscriptions->total() }} results
                            </div>
                            <div>
                                {{ $subscriptions->links() }}
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
            .font-weight-semibold {
                font-weight: 600;
            }

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
                $('#status, #billing_cycle').select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    minimumResultsForSearch: -1
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
