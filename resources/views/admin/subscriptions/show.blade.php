<x-admin-layout>
    @section('page-title')
        Subscription Details
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
                        <li class="breadcrumb-item"><a href="{{ route('admin.subscriptions.index') }}">Subscriptions</a></li>
                        <li class="breadcrumb-item active">Details</li>
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

            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Subscription #{{ $subscription->plan?->name }}
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
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-user"></i> Customer Information
                                    </h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-striped">
                                        <tbody>
                                            <tr>
                                                <th width="40%"><i class="fas fa-user-circle"></i> Customer Name:</th>
                                                <td><strong>{{ $subscription->user?->name ?? 'N/A' }}</strong></td>
                                            </tr>
                                            <tr>
                                                <th><i class="fas fa-envelope"></i> Customer Email:</th>
                                                <td>{{ $subscription->user?->email ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th><i class="fas fa-id-card"></i> Customer Code:</th>
                                                <td><code>{{ $subscription->user?->client_code ?? 'N/A' }}</code></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="card card-outline card-warning mt-3">
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
                                            @if($subscription->is_custom_price && $subscription->custom_price !== null)
                                                <tr>
                                                    <th><i class="fas fa-star"></i> Custom Price:</th>
                                                    <td>
                                                        <span class="badge badge-info">Custom</span>
                                                        <strong class="text-success">{{ number_format($subscription->custom_price, 2) }} {{ $subscription->custom_price_currency ?? 'USD' }}</strong>
                                                    </td>
                                                </tr>
                                                @if($subscription->custom_price_notes)
                                                    <tr>
                                                        <th><i class="fas fa-sticky-note"></i> Custom Price Notes:</th>
                                                        <td>{{ $subscription->custom_price_notes }}</td>
                                                    </tr>
                                                @endif
                                                @if($subscription->createdByAdmin)
                                                    <tr>
                                                        <th><i class="fas fa-user-shield"></i> Created By Admin:</th>
                                                        <td>{{ $subscription->createdByAdmin->name }}</td>
                                                    </tr>
                                                @endif
                                            @elseif($subscription->planPrice)
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
                                                <td>{{ $subscription->starts_at?->format('F d, Y g:i A') ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th><i class="fas fa-stop-circle"></i> Expiry Date:</th>
                                                <td>
                                                    {{ $subscription->expires_at?->format('F d, Y g:i A') ?? 'N/A' }}
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
                                                    <td>{{ $subscription->next_renewal_at->format('F d, Y g:i A') }}</td>
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
                                            @if($subscription->last_renewal_attempt_at)
                                                <tr>
                                                    <th><i class="fas fa-redo-alt"></i> Last Renewal Attempt:</th>
                                                    <td>{{ $subscription->last_renewal_attempt_at->format('F d, Y g:i A') }}</td>
                                                </tr>
                                            @endif
                                            @if($subscription->last_invoice_generated_at)
                                                <tr>
                                                    <th><i class="fas fa-file-invoice"></i> Last Invoice Generated:</th>
                                                    <td>{{ $subscription->last_invoice_generated_at->format('F d, Y g:i A') }}</td>
                                                </tr>
                                            @endif
                                            @if($subscription->next_invoice_due_at)
                                                <tr>
                                                    <th><i class="fas fa-calendar-alt"></i> Next Invoice Due:</th>
                                                    <td>{{ $subscription->next_invoice_due_at->format('F d, Y g:i A') }}</td>
                                                </tr>
                                            @endif
                                            @if($subscription->cancelled_at)
                                                <tr>
                                                    <th><i class="fas fa-ban"></i> Cancelled At:</th>
                                                    <td>{{ $subscription->cancelled_at->format('F d, Y g:i A') }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <th><i class="fas fa-plus-circle"></i> Created:</th>
                                                <td>{{ $subscription->created_at->format('F d, Y g:i A') }}</td>
                                            </tr>
                                            <tr>
                                                <th><i class="fas fa-edit"></i> Last Updated:</th>
                                                <td>{{ $subscription->updated_at->format('F d, Y g:i A') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Subscriptions
                        </a>
                        <div>
                            @can('subscription_edit')
                                <a href="{{ route('admin.subscriptions.edit', $subscription) }}" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit Subscription
                                </a>
                                @if($subscription->canBeRenewed())
                                    <form action="{{ route('admin.subscriptions.renew-now', $subscription) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to manually renew this subscription? This will extend the subscription period immediately.');">
                                        @csrf
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-sync-alt"></i> Renew Now
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-admin-layout>
