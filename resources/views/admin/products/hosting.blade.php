<x-admin-layout>
    @section('page-title')
        Hosting
    @endsection

    <div class="container-fluid">
        <div class="row">
            <div class="col-12 mt-5">
                <div class="card">
                    <div class="card-header">
                        <h4>Hosting</h4>
                    </div>
                    <div class="card-body">
                        @if(!$plans->isEmpty())
                            <form method="GET" action="{{ route('admin.products.hosting') }}" class="mb-3">
                                <div class="row align-items-end">
                                    <div class="col-md-4">
                                        <div class="form-group mb-0">
                                            <label for="plan_id">Filter by Plan:</label>
                                            <select name="plan_id" id="plan_id" class="form-control" onchange="this.form.submit()">
                                                <option value="">All Plans</option>
                                                @foreach($plans as $plan)
                                                    <option value="{{ $plan->id }}" {{ $selectedPlanId == $plan->id ? 'selected' : '' }}>
                                                        {{ $plan->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    @if($selectedPlanId)
                                        <div class="col-md-auto">
                                            <a href="{{ route('admin.products.hosting') }}" class="btn btn-secondary btn-sm">
                                                <i class="bi bi-x-lg"></i> Clear Filter
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </form>
                        @endif
                        @if($subscriptions->isEmpty())
                            <div class="alert alert-info">
                                <h5><i class="bi bi-info-circle"></i> No Subscriptions Found</h5>
                                No hosting subscriptions found. Subscriptions will appear here once customers purchase hosting plans.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover datatable datatable-Subscription w-100">
                                    <thead>
                                        <tr>
                                            <th>Plan</th>
                                            <th>Billing Cycle</th>
                                            <th>Status</th>
                                            <th>Starts At</th>
                                            <th>Expires At</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($subscriptions as $subscription)
                                            @php
                                                $statusBadgeClass = match($subscription->status) {
                                                    'active' => 'success',
                                                    'expired' => 'danger',
                                                    'cancelled' => 'secondary',
                                                    'suspended' => 'warning',
                                                    default => 'info'
                                                };

                                                $isExpiringSoon = $subscription->isExpiringSoon();
                                            @endphp
                                            <tr>
                                                <td>
                                                    <strong>{{ $subscription->plan->name ?? 'N/A' }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light">
                                                        {{ ucfirst(str_replace('_', ' ', $subscription->billing_cycle)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $statusBadgeClass }}">
                                                        {{ ucfirst($subscription->status) }}
                                                    </span>
                                                    @if($isExpiringSoon)
                                                        <span class="badge badge-warning ml-1">
                                                            <i class="bi bi-exclamation-triangle"></i> Expiring Soon
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>{{ $subscription->starts_at?->format('M d, Y') ?? 'N/A' }}</td>
                                                <td>
                                                    {{ $subscription->expires_at?->format('M d, Y') ?? 'N/A' }}
                                                    @if($subscription->expires_at)
                                                        <br>
                                                        <small class="text-muted">
                                                            {{ $subscription->expires_at->diffForHumans() }}
                                                        </small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('admin.products.subscription.show', $subscription) }}"
                                                       class="btn btn-sm btn-info"
                                                       title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                        View Details
                                                    </a>
                                                    @if($subscription->canBeRenewed())
                                                        <form action="{{ route('admin.products.subscription.renew', $subscription) }}"
                                                              method="POST"
                                                              class="d-inline ms-2">
                                                            @csrf
                                                            <button type="submit"
                                                                    class="btn btn-sm btn-success"
                                                                    title="Add Renewal to Cart">
                                                                <i class="bi bi-cart-plus"></i> Renew
                                                            </button>
                                                        </form>
                                                    @endif
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
</x-admin-layout>
