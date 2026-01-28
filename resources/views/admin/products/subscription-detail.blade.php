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
                    <h5><i class="icon fas fa-check"></i> Success!</h5>
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <h5><i class="icon fas fa-info"></i> Information</h5>
                    {{ session('info') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle"></i> Subscription Information
                            </h3>
                            <div class="card-tools">
                                @php
                                    $statusBadgeClass = match($subscription->status) {
                                        'active' => 'badge-success',
                                        'expired' => 'badge-danger',
                                        'cancelled' => 'badge-secondary',
                                        'suspended' => 'badge-warning',
                                        default => 'badge-info'
                                    };
                                @endphp
                                <span class="badge {{ $statusBadgeClass }} badge-lg">
                                    {{ ucfirst($subscription->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            @if($subscription->isExpiringSoon())
                                <div class="alert alert-warning alert-dismissible">
                                    <h5><i class="icon fas fa-exclamation-triangle"></i> Expiring Soon!</h5>
                                    This subscription will expire on <strong>{{ $subscription->expires_at->format('F d, Y') }}</strong>.
                                    Please renew to avoid service interruption.
                                </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card card-outline card-info">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-box"></i> Plan Information
                                            </h3>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-striped">
                                                <tbody>
                                                    <tr>
                                                        <th width="40%"><i class="fas fa-tag"></i> Plan Name:</th>
                                                        <td><strong>{{ $subscription->plan?->name ?? 'N/A' }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-sync-alt"></i> Billing Cycle:</th>
                                                        <td>
                                                            <span class="badge badge-info">
                                                                {{ ucfirst(str_replace('_', ' ', $subscription->billing_cycle)) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-globe"></i> Linked Domain:</th>
                                                        <td>
                                                            @if($subscription->domain)
                                                                <code>{{ $subscription->domain }}</code>
                                                            @else
                                                                <span class="text-muted">N/A</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @if($subscription->planPrice)
                                                        <tr>
                                                            <th><i class="fas fa-dollar-sign"></i> Regular Price:</th>
                                                            <td><strong class="text-success">{{ $subscription->planPrice->getFormattedPrice('regular_price') }}</strong></td>
                                                        </tr>
                                                        <tr>
                                                            <th><i class="fas fa-redo"></i> Renewal Price:</th>
                                                            <td><strong class="text-primary">{{ $subscription->planPrice->getFormattedPrice('renewal_price') }}</strong></td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card card-outline card-success">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-calendar-alt"></i> Subscription Status
                                            </h3>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-striped">
                                                <tbody>
                                                    <tr>
                                                        <th width="40%"><i class="fas fa-play-circle"></i> Start Date:</th>
                                                        <td>{{ $subscription->starts_at?->format('F d, Y') ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th><i class="fas fa-stop-circle"></i> Expiry Date:</th>
                                                        <td>
                                                            {{ $subscription->expires_at?->format('F d, Y') ?? 'N/A' }}
                                                            @if($subscription->expires_at)
                                                                <br>
                                                                <small class="text-muted">
                                                                    <i class="far fa-clock"></i> {{ $subscription->expires_at->diffForHumans() }}
                                                                </small>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @if($subscription->next_renewal_at)
                                                        <tr>
                                                            <th><i class="fas fa-calendar-check"></i> Next Renewal:</th>
                                                            <td>{{ $subscription->next_renewal_at->format('F d, Y') }}</td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <th><i class="fas fa-sync"></i> Auto Renewal:</th>
                                                        <td>
                                                            @if($subscription->auto_renew)
                                                                <span class="badge badge-success badge-lg">
                                                                    <i class="fas fa-check-circle"></i> Enabled
                                                                </span>
                                                            @else
                                                                <span class="badge badge-secondary badge-lg">
                                                                    <i class="fas fa-times-circle"></i> Disabled
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @if($subscription->provider_resource_id)
                                                        <tr>
                                                            <th><i class="fas fa-key"></i> Provider Reference:</th>
                                                            <td>
                                                                <code class="text-primary">{{ $subscription->provider_resource_id }}</code>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('admin.products.hosting') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to My Hosting
                                </a>
                                @if($subscription->canBeRenewed())
                                    <form action="{{ route('admin.products.subscription.renew', $subscription) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-shopping-cart"></i> Add Renewal to Cart
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($subscription->product_snapshot)
                        <div class="card card-outline card-warning">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-file-invoice"></i> Order Information
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong><i class="fas fa-box"></i> Original Plan:</strong>
                                            <span class="text-muted">{{ $subscription->product_snapshot['plan']['name'] ?? 'N/A' }}</span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong><i class="fas fa-sync-alt"></i> Original Billing Cycle:</strong>
                                            <span class="text-muted">{{ ucfirst($subscription->product_snapshot['price']['billing_cycle'] ?? 'N/A') }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-admin-layout>
