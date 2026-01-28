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
            @php
                $cards = [
                    [
                        'value' => number_format($stats['total']),
                        'label' => 'Total Subscriptions',
                        'color' => 'bg-info',
                        'icon' => 'fas fa-box',
                        'link' => route('admin.subscriptions.index'),
                    ],
                    [
                        'value' => number_format($stats['active']),
                        'label' => 'Active',
                        'color' => 'bg-success',
                        'icon' => 'fas fa-check-circle',
                        'link' => route('admin.subscriptions.index', ['status' => 'active']),
                    ],
                    [
                        'value' => number_format($stats['expiring_soon']),
                        'label' => 'Expiring (Next 30 days)',
                        'color' => 'bg-warning',
                        'icon' => 'fas fa-exclamation-triangle',
                        'link' => route('admin.subscriptions.index', ['status' => 'active']),
                    ],
                    [
                        'value' => number_format($stats['cancelled']),
                        'label' => 'Cancelled',
                        'color' => 'bg-danger',
                        'icon' => 'fas fa-times-circle',
                        'link' => route('admin.subscriptions.index', ['status' => 'cancelled']),
                    ],
                ];
            @endphp

            <div class="row">
                @foreach ($cards as $card)
                    <div class="col-lg-3 col-6">
                        <div class="small-box {{ $card['color'] }}">
                            <div class="inner">
                                <h3>{{ $card['value'] }}</h3>
                                <p>{{ $card['label'] }}</p>
                            </div>
                            <div class="icon">
                                <i class="{{ $card['icon'] }}"></i>
                            </div>
                            <a href="{{ $card['link'] }}" class="small-box-footer">
                                More info <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Subscription Monitor
                    </h3>
                    <div class="card-tools">
                        @can('subscription_create')
                            <a href="{{ route('admin.subscriptions.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Create Custom Subscription
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-filter"></i> Filters
                                    </h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form method="GET" action="{{ route('admin.subscriptions.index') }}" id="filters-form">
                                        <div class="row">
                                            <div class="form-group col-md-3">
                                                <label for="search">
                                                    <i class="fas fa-search"></i> Search
                                                </label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                    </div>
                                                    <input type="text" 
                                                           id="search" 
                                                           name="search" 
                                                           class="form-control realtime-filter"
                                                           placeholder="Customer or provider reference"
                                                           value="{{ $filters['search'] }}">
                                                </div>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="status">
                                                    <i class="fas fa-tag"></i> Status
                                                </label>
                                                <select id="status" name="status" class="form-control realtime-filter">
                                                    <option value="">All statuses</option>
                                                    @foreach ($statusOptions as $status)
                                                        <option value="{{ $status }}" @selected($filters['status'] === $status)>
                                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="billing_cycle">
                                                    <i class="fas fa-sync-alt"></i> Billing Cycle
                                                </label>
                                                <select id="billing_cycle" name="billing_cycle" class="form-control realtime-filter">
                                                    <option value="">All cycles</option>
                                                    @foreach ($billingCycleOptions as $cycle)
                                                        <option value="{{ $cycle }}" @selected($filters['billing_cycle'] === $cycle)>
                                                            {{ ucfirst(str_replace('_', ' ', $cycle)) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="starts_from">
                                                    <i class="fas fa-calendar-alt"></i> Start Date (From)
                                                </label>
                                                <input type="date" 
                                                       id="starts_from" 
                                                       name="starts_from" 
                                                       class="form-control realtime-filter"
                                                       value="{{ $filters['starts_from'] }}">
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="starts_to">
                                                    <i class="fas fa-calendar-alt"></i> Start Date (To)
                                                </label>
                                                <input type="date" 
                                                       id="starts_to" 
                                                       name="starts_to" 
                                                       class="form-control realtime-filter"
                                                       value="{{ $filters['starts_to'] }}">
                                            </div>
                                            <div class="form-group col-md-1 d-flex align-items-end">
                                                <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary btn-block">
                                                    <i class="fas fa-redo"></i> Reset
                                                </a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

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
                            <h5><i class="icon fas fa-info"></i> No subscriptions found</h5>
                            Try adjusting your filters or check back later.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th><i class="fas fa-user"></i> Customer Name</th>
                                        <th><i class="fas fa-box"></i> Plan Name</th>
                                        <th><i class="fas fa-dollar-sign"></i> Regular Price</th>
                                        <th><i class="fas fa-redo"></i> Renewal Price</th>
                                        <th><i class="fas fa-sync-alt"></i> Billing Cycle</th>
                                        <th><i class="fas fa-tag"></i> Status</th>
                                        <th><i class="fas fa-calendar-check"></i> Start Date</th>
                                        <th><i class="fas fa-calendar-times"></i> Expires At</th>
                                        <th><i class="fas fa-cog"></i> Actions</th>
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
                                                <strong>{{ $customerName !== '' ? $customerName : 'N/A' }}</strong>
                                            </td>
                                            <td class="align-middle">
                                                {{ $subscription->plan?->name ?? 'N/A' }}
                                            </td>
                                            <td class="align-middle">
                                                @if ($subscription->is_custom_price && $subscription->custom_price !== null)
                                                    <span class="badge badge-info" title="Custom Price">
                                                        <i class="fas fa-star"></i> Custom
                                                    </span>
                                                    <br>
                                                    <strong>{{ number_format($subscription->custom_price, 2) }} {{ $subscription->custom_price_currency ?? 'USD' }}</strong>
                                                @elseif ($subscription->planPrice)
                                                    {{ $subscription->planPrice->getFormattedPrice('regular_price') }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                @if ($subscription->is_custom_price && $subscription->custom_price !== null)
                                                    <span class="badge badge-info" title="Custom Price">
                                                        <i class="fas fa-star"></i> Custom
                                                    </span>
                                                    <br>
                                                    <strong>{{ number_format($subscription->custom_price, 2) }} {{ $subscription->custom_price_currency ?? 'USD' }}</strong>
                                                @elseif ($subscription->planPrice)
                                                    <strong class="text-primary">{{ $subscription->planPrice->getFormattedPrice('renewal_price') }}</strong>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                <span class="badge badge-light badge-lg">
                                                    {{ ucfirst(str_replace('_', ' ', $subscription->billing_cycle ?? '—')) }}
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                @php
                                                    $status = $subscription->status ?? 'unknown';
                                                @endphp
                                                <span class="badge {{ $statusColors[$status] ?? 'badge-dark' }} badge-lg">
                                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                <i class="far fa-calendar"></i> {{ $subscription->starts_at?->format('M d, Y') ?? '—' }}
                                            </td>
                                            <td class="align-middle">
                                                <i class="far fa-calendar"></i> {{ $subscription->expires_at?->format('M d, Y') ?? '—' }}
                                            </td>
                                            <td class="align-middle">
                                                <div class="btn-group" role="group">
                                                    @can('subscription_show')
                                                        <a href="{{ route('admin.subscriptions.show', $subscription) }}"
                                                           class="btn btn-sm btn-info"
                                                           title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    @endcan
                                                    @can('subscription_edit')
                                                        <a href="{{ route('admin.subscriptions.edit', $subscription) }}"
                                                           class="btn btn-sm btn-primary"
                                                           title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                    <form action="{{ route('admin.products.subscription.renew', $subscription) }}"
                                                          method="POST"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn btn-sm btn-success"
                                                                title="Add Renewal to Cart">
                                                            <i class="fas fa-shopping-cart"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-info-circle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Showing</span>
                                        <span class="info-box-number">
                                            {{ $subscriptions->firstItem() ?? 0 }} - {{ $subscriptions->lastItem() ?? 0 }} of {{ $subscriptions->total() }} results
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex justify-content-end align-items-center">
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
