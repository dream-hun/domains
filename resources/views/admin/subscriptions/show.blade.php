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

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Subscription #{{ $subscription->plan?->name }}</h3>
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
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Customer Information</h5>
                            <table class="table table-sm table-bordered">
                                <tbody>
                                    <tr>
                                        <th width="40%">Customer Name:</th>
                                        <td>{{ $subscription->user?->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Customer Email:</th>
                                        <td>{{ $subscription->user?->email ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Customer Code:</th>
                                        <td>{{ $subscription->user?->client_code ?? 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <h5 class="mb-3 mt-4">Plan Information</h5>
                            <table class="table table-sm table-bordered">
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
                                    @if($subscription->is_custom_price && $subscription->custom_price !== null)
                                        <tr>
                                            <th>Custom Price:</th>
                                            <td>
                                                <span class="badge badge-info">Custom</span>
                                                @price($subscription->custom_price, $subscription->custom_price_currency ?? 'USD')
                                            </td>
                                        </tr>
                                        @if($subscription->custom_price_notes)
                                            <tr>
                                                <th>Custom Price Notes:</th>
                                                <td>{{ $subscription->custom_price_notes }}</td>
                                            </tr>
                                        @endif
                                        @if($subscription->createdByAdmin)
                                            <tr>
                                                <th>Created By Admin:</th>
                                                <td>{{ $subscription->createdByAdmin->name }}</td>
                                            </tr>
                                        @endif
                                    @elseif($subscription->planPrice)
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
                            <table class="table table-sm table-bordered">
                                <tbody>
                                    <tr>
                                        <th width="40%">Start Date:</th>
                                        <td>{{ $subscription->starts_at?->format('F d, Y g:i A') ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Expiry Date:</th>
                                        <td>
                                            {{ $subscription->expires_at?->format('F d, Y g:i A') ?? 'N/A' }}
                                            @if($subscription->expires_at)
                                                <br>
                                                <small class="text-muted">{{ $subscription->expires_at->diffForHumans() }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($subscription->next_renewal_at)
                                        <tr>
                                            <th>Next Renewal:</th>
                                            <td>{{ $subscription->next_renewal_at->format('F d, Y g:i A') }}</td>
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
                                    @if($subscription->last_renewal_attempt_at)
                                        <tr>
                                            <th>Last Renewal Attempt:</th>
                                            <td>{{ $subscription->last_renewal_attempt_at->format('F d, Y g:i A') }}</td>
                                        </tr>
                                    @endif
                                    @if($subscription->last_invoice_generated_at)
                                        <tr>
                                            <th>Last Invoice Generated:</th>
                                            <td>{{ $subscription->last_invoice_generated_at->format('F d, Y g:i A') }}</td>
                                        </tr>
                                    @endif
                                    @if($subscription->next_invoice_due_at)
                                        <tr>
                                            <th>Next Invoice Due:</th>
                                            <td>{{ $subscription->next_invoice_due_at->format('F d, Y g:i A') }}</td>
                                        </tr>
                                    @endif
                                    @if($subscription->cancelled_at)
                                        <tr>
                                            <th>Cancelled At:</th>
                                            <td>{{ $subscription->cancelled_at->format('F d, Y g:i A') }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th>Created:</th>
                                        <td>{{ $subscription->created_at->format('F d, Y g:i A') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Last Updated:</th>
                                        <td>{{ $subscription->updated_at->format('F d, Y g:i A') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">Back to Subscriptions</a>
                        <div>
                            @can('subscription_edit')
                                <a href="{{ route('admin.subscriptions.edit', $subscription) }}" class="btn btn-primary">Edit Subscription</a>
                                @if($subscription->canBeRenewed())
                                    <form action="{{ route('admin.subscriptions.renew-now', $subscription) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to manually renew this subscription? This will extend the subscription period immediately.');">
                                        @csrf
                                        <button type="submit" class="btn btn-success">Renew Now</button>
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
