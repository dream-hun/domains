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
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscriptions as $subscription)
                                        <tr>
                                            <td>{{ $subscription->plan->name ?? 'N/A' }}</td>
                                            <td>{{ $subscription->domain }}</td>
                                            <td>{{ ucfirst($subscription->billing_cycle) }}</td>
                                            <td>
                                                <span class="badge bg-{{ $subscription->status === 'active' ? 'success' : ($subscription->status === 'cancelled' ? 'danger' : 'warning') }}">
                                                    {{ ucfirst($subscription->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $subscription->starts_at?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                                            <td>{{ $subscription->expires_at?->format('Y-m-d H:i') ?? 'N/A' }}</td>
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
