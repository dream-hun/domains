<x-admin-layout>
    @section('page-title')
        Subscription Details
    @endsection
    @section('breadcrumb')
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.products.hosting') }}">My Hosting</a></li>
            <li class="breadcrumb-item active">Subscription Details</li>
        </ol>
    @endsection

    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Subscription Details</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.products.hosting') }}">My Hosting</a></li>
                        <li class="breadcrumb-item active">Subscription Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    {{ session('info') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Subscription Details</h3>
                            @php
                                $statusBadgeClass = match($subscription->status) {
                                    'active' => 'badge-success',
                                    'expired' => 'badge-danger',
                                    'cancelled' => 'badge-secondary',
                                    'suspended' => 'badge-warning',
                                    default => 'badge-info'
                                };
                            @endphp
                            <span class="badge {{ $statusBadgeClass }}">
                                {{ ucfirst($subscription->status) }}
                            </span>
                        </div>
                        <div class="card-body">
                            @if($subscription->isExpiringSoon())
                                <div class="alert alert-warning">
                                    <strong>Expiring Soon!</strong> This subscription will expire on {{ $subscription->expires_at->format('F d, Y') }}.
                                    Please renew to avoid service interruption.
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Plan Information</h5>
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <th width="40%">Plan Name:</th>
                                                <td>{{ $subscription->plan?->name ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Billing Cycle:</th>
                                                <td>{{ ucfirst(str_replace('_', ' ', $subscription->billing_cycle)) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Linked Domain:</th>
                                                <td>{{ $subscription->domain ?? 'N/A' }}</td>
                                            </tr>
                                            @if($subscription->planPrice)
                                                <tr>
                                                    <th>Regular Price:</th>
                                                    <td>{{ $subscription->planPrice->getFormattedPrice('regular_price') }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Renewal Price:</th>
                                                    <td>{{ $subscription->planPrice->getFormattedPrice('renewal_price') }}</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <h5 class="mb-3">Subscription Status</h5>
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <th width="40%">Start Date:</th>
                                                <td>{{ $subscription->starts_at?->format('F d, Y') ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Expiry Date:</th>
                                                <td>
                                                    {{ $subscription->expires_at?->format('F d, Y') ?? 'N/A' }}
                                                    @if($subscription->expires_at)
                                                        <br>
                                                        <small class="text-muted">{{ $subscription->expires_at->diffForHumans() }}</small>
                                                    @endif
                                                </td>
                                            </tr>
                                            @if($subscription->next_renewal_at)
                                                <tr>
                                                    <th>Next Renewal:</th>
                                                    <td>{{ $subscription->next_renewal_at->format('F d, Y') }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <th>Auto Renewal:</th>
                                                <td>
                                                    @if($subscription->auto_renew)
                                                        <span class="badge badge-success">Enabled</span>
                                                    @else
                                                        <span class="badge badge-secondary">Disabled</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @if($subscription->provider_resource_id)
                                                <tr>
                                                    <th>Provider Reference:</th>
                                                    <td><code>{{ $subscription->provider_resource_id }}</code></td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('admin.products.hosting') }}" class="btn btn-secondary">Back to My Hosting</a>
                                @if($subscription->canBeRenewed())
                                    <form action="{{ route('admin.products.subscription.renew', $subscription) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success">Add Renewal to Cart</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($subscription->product_snapshot)
                        <div class="card mt-3">
                            <div class="card-header">
                                <h3 class="card-title">Order Information</h3>
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>Original Plan:</strong> {{ $subscription->product_snapshot['plan']['name'] ?? 'N/A' }}</p>
                                <p class="mb-2"><strong>Original Billing Cycle:</strong> {{ ucfirst($subscription->product_snapshot['price']['billing_cycle'] ?? 'N/A') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-admin-layout>
