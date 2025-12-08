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
                        @if($subscriptions->isEmpty())
                            <div class="alert alert-info">
                                <h5><i class="bi bi-info-circle"></i> No Subscriptions Found</h5>
                                No hosting subscriptions found. Subscriptions will appear here once customers purchase hosting plans.
                            </div>
                        @else
                            <table class="table table-bordered table-striped table-hover datatable datatable-Subscription w-100">
                                <thead>
                                    <tr>
                                        <th>Plan</th>
                                        <th>Domain</th>
                                        <th>Billing Cycle</th>
                                        <th>Status</th>
                                        <th>Starts At</th>
                                        <th>Expires At</th>
                                        <th>Actions</th>
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
                                            <td>{{ $subscription->domain ?? 'N/A' }}</td>
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
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.products.subscription.show', $subscription) }}"
                                                       class="btn btn-sm btn-info"
                                                       title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    @if($subscription->canBeRenewed())
                                                        <form action="{{ route('admin.products.subscription.renew', $subscription) }}"
                                                              method="POST"
                                                              class="d-inline">
                                                            @csrf
                                                            <button type="submit"
                                                                    class="btn btn-sm btn-success"
                                                                    title="Add Renewal to Cart">
                                                                <i class="bi bi-cart-plus"></i> Renew
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
