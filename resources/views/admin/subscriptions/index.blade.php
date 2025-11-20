<x-admin-layout>
    @section('page-title')
        {{ trans('cruds.subscription.title') }}
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ trans('cruds.subscription.title') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">{{ trans('cruds.subscription.title') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ number_format($stats['total']) }}</h3>
                            <p>Total Subscriptions</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-clipboard"></i>
                        </div>
                        <a href="{{ route('admin.subscriptions.index') }}" class="small-box-footer">
                            View all <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ number_format($stats['active']) }}</h3>
                            <p>Active</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-checkmark-circled"></i>
                        </div>
                        <a href="{{ route('admin.subscriptions.index', ['status' => 'active']) }}"
                            class="small-box-footer">
                            View active <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ number_format($stats['expiring_soon']) }}</h3>
                            <p>Expiring (Next 30 days)</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-clock"></i>
                        </div>
                        <a href="{{ route('admin.subscriptions.index', ['status' => 'active']) }}"
                            class="small-box-footer">
                            Review renewals <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3>{{ number_format($stats['cancelled']) }}</h3>
                            <p>Cancelled</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-close-circled"></i>
                        </div>
                        <a href="{{ route('admin.subscriptions.index', ['status' => 'cancelled']) }}"
                            class="small-box-footer">
                            View cancelled <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap"
                    style="gap: 0.75rem;">
                    <h3 class="card-title mb-0">Subscription Monitor</h3>
                    <span class="text-muted small">Tracking {{ $subscriptions->total() }} records</span>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="mb-4">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="search">Search</label>
                                <input type="text" id="search" name="search" class="form-control"
                                    placeholder="Domain, customer or provider reference"
                                    value="{{ $filters['search'] }}">
                            </div>
                            <div class="form-group col-md-2">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
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
                                <select id="billing_cycle" name="billing_cycle" class="form-control">
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
                                <input type="date" id="starts_from" name="starts_from" class="form-control"
                                    value="{{ $filters['starts_from'] }}">
                            </div>
                            <div class="form-group col-md-2">
                                <label for="starts_to">Start Date (To)</label>
                                <input type="date" id="starts_to" name="starts_to" class="form-control"
                                    value="{{ $filters['starts_to'] }}">
                            </div>
                            <div class="form-group col-md-1">
                                <label for="per_page">Per Page</label>
                                <select id="per_page" name="per_page" class="form-control">
                                    @foreach ([10, 25, 50, 100] as $size)
                                        <option value="{{ $size }}" @selected((int) $filters['per_page'] === $size)>
                                            {{ $size }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center" style="gap: 0.75rem;">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="bi bi-search"></i> Apply Filters
                            </button>
                            <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
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
                        <div class="alert alert-info mb-0">
                            <h5><i class="bi bi-info-circle"></i> No subscriptions found</h5>
                            Try adjusting your filters or check back later.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Domain</th>
                                        <th>Plan</th>
                                        <th>Billing</th>
                                        <th>Status</th>
                                        <th>Start</th>
                                        <th>Expiry</th>
                                        <th>Next Renewal</th>
                                        <th>Provider Ref</th>
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
                                                <div class="font-weight-semibold">
                                                    {{ $customerName !== '' ? $customerName : 'N/A' }}
                                                </div>
                                                <small
                                                    class="text-muted">{{ $subscription->user?->email ?? '—' }}</small>
                                            </td>
                                            <td class="align-middle">
                                                <div class="font-weight-semibold">{{ $subscription->domain ?? '—' }}
                                                </div>
                                                <small class="text-muted">UUID: {{ $subscription->uuid }}</small>
                                            </td>
                                            <td class="align-middle">
                                                <div>{{ $subscription->plan?->name ?? 'N/A' }}</div>
                                                <small class="text-muted">
                                                    @if ($subscription->planPrice)
                                                        Reg:
                                                        {{ $subscription->planPrice->getFormattedPrice('regular_price') }}
                                                        · Ren:
                                                        {{ $subscription->planPrice->getFormattedPrice('renewal_price') }}
                                                    @else
                                                        Pricing unavailable
                                                    @endif
                                                </small>
                                            </td>
                                            <td class="align-middle">
                                                <span class="badge badge-light text-uppercase">
                                                    {{ $subscription->billing_cycle ?? '—' }}
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
                                                {{ optional($subscription->starts_at)->format('M d, Y') ?? '—' }}
                                            </td>
                                            <td class="align-middle">
                                                {{ optional($subscription->expires_at)->format('M d, Y') ?? '—' }}
                                            </td>
                                            <td class="align-middle">
                                                {{ optional($subscription->next_renewal_at)->format('M d, Y') ?? '—' }}
                                            </td>
                                            <td class="align-middle">
                                                <span class="text-monospace">
                                                    {{ $subscription->provider_resource_id ?? '—' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div class="text-muted small mb-2">
                                Showing
                                {{ $subscriptions->firstItem() ?? 0 }} -
                                {{ $subscriptions->lastItem() ?? 0 }}
                                of {{ $subscriptions->total() }} results
                            </div>
                            <div>
                                {{ $subscriptions->links('vendor.pagination.adminlte') }}
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
</x-admin-layout>
