<x-admin-layout>
    @section('page-title')
        Order Details - {{ $order->order_number }}
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Order Details</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('billing.index') }}">Billing</a></li>
                        <li class="breadcrumb-item active">{{ $order->order_number }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Order Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Order Number:</strong>
                            </div>
                            <div class="col-sm-8">
                                {{ $order->order_number }}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Order Date:</strong>
                            </div>
                            <div class="col-sm-8">
                                {{ $order->created_at->format('F d, Y H:i') }}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Payment Status:</strong>
                            </div>
                            <div class="col-sm-8">
                                @if ($order->isPaid())
                                    <span class="badge bg-success">Paid</span>
                                @elseif ($order->isPending())
                                    <span class="badge bg-warning">Pending</span>
                                @elseif ($order->isFailed())
                                    <span class="badge bg-danger">Failed</span>
                                @elseif ($order->isCancelled())
                                    <span class="badge bg-secondary">Cancelled</span>
                                @else
                                    <span class="badge bg-info">{{ $order->payment_status }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Order Status:</strong>
                            </div>
                            <div class="col-sm-8">
                                @if ($order->isCompleted())
                                    <span class="badge bg-success">Completed</span>
                                @elseif ($order->isProcessing())
                                    <span class="badge bg-info">Processing</span>
                                @elseif ($order->requiresAttention())
                                    <span class="badge bg-danger">Requires Attention</span>
                                @elseif ($order->isPartiallyCompleted())
                                    <span class="badge bg-warning">Partially Completed</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($order->status) }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Payment Method:</strong>
                            </div>
                            <div class="col-sm-8">
                                {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                            </div>
                        </div>

                        @php
                            $payment = $order->payments->sortByDesc(function ($p) {
                                return ($p->attempt_number ?? 0) * 1000000 + ($p->id ?? 0);
                            })->first();
                            $kpayTransactionId = null;
                            if ($payment && $order->payment_method === 'kpay') {
                                $kpayTransactionId = $payment->kpay_transaction_id;
                            }
                        @endphp
                        @if ($kpayTransactionId)
                            <div class="row mb-3">
                                <div class="col-sm-4">
                                    <strong>KPay Transaction ID:</strong>
                                </div>
                                <div class="col-sm-8">
                                    <code>{{ $kpayTransactionId }}</code>
                                </div>
                            </div>
                        @endif

                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Total Amount:</strong>
                            </div>
                            <div class="col-sm-8">
                                <strong>{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</strong>
                            </div>
                        </div>

                        @if ($order->notes)
                            <div class="row mb-3">
                                <div class="col-sm-4">
                                    <strong>Notes:</strong>
                                </div>
                                <div class="col-sm-8">
                                    {{ $order->notes }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Order Items</h3>
                    </div>
                    <div class="card-body">
                        @if ($order->orderItems->isEmpty())
                            <div class="alert alert-info" role="alert">
                                <i class="bi bi-info-circle"></i> No items found in this order.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th>Type</th>
                                            <th>Period</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->orderItems as $item)
                                            <tr>
                                                <td>
                                                    @if ($item->domain_type === 'hosting')
                                                        @php
                                                            $metadata = $item->metadata ?? [];
                                                            $planName = $metadata['plan']['name'] ?? 'Hosting Plan';
                                                            $linkedDomain = $metadata['linked_domain'] ?? null;
                                                        @endphp
                                                        <strong>{{ $planName }}</strong>
                                                        @if ($linkedDomain)
                                                            <br><small class="text-muted">Domain: {{ $linkedDomain }}</small>
                                                        @endif
                                                    @elseif ($item->domain_type === 'subscription_renewal')
                                                        @php
                                                            $metadata = $item->metadata ?? [];
                                                            $subscriptionId = $metadata['subscription_id'] ?? null;
                                                            $planName = null;

                                                            // Use preloaded subscription to avoid N+1 queries
                                                            $subscription = $item->getAttribute('_preloaded_subscription');
                                                            if ($subscription) {
                                                                if ($subscription->product_snapshot) {
                                                                    $planName = $subscription->product_snapshot['plan']['name'] ?? null;
                                                                }
                                                                // Fallback: try to get from plan relationship
                                                                if (!$planName && $subscription->plan) {
                                                                    $planName = $subscription->plan->name;
                                                                }
                                                            }

                                                            // Fallback: try to get from preloaded hosting plan
                                                            if (!$planName) {
                                                                $plan = $item->getAttribute('_preloaded_plan');
                                                                if ($plan) {
                                                                    $planName = $plan->name;
                                                                }
                                                            }
                                                        @endphp
                                                        <strong>{{ $planName ?: 'Subscription Renewal' }}</strong>
                                                        @if ($subscriptionId)
                                                            <br><small class="text-muted">Subscription ID: {{ $subscriptionId }}</small>
                                                        @endif
                                                    @else
                                                        {{ $item->domain_name }}
                                                    @endif
                                                </td>
                                                <td>{{ ucfirst(str_replace('_', ' ', $item->domain_type)) }}</td>
                                                <td>
                                                    @if (in_array($item->domain_type, ['hosting', 'subscription_renewal'], true))
                                                        @php
                                                            $metadata = $item->metadata ?? [];
                                                            $billingCycle = $metadata['billing_cycle'] ?? null;
                                                        @endphp
                                                        @if ($billingCycle)
                                                            {{ ucfirst($billingCycle) }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    @else
                                                        {{ $item->years }} {{ $item->years == 1 ? 'year' : 'years' }}
                                                    @endif
                                                </td>
                                                <td>{{ $item->currency }} {{ number_format($item->price, 2) }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>{{ $item->currency }} {{ number_format($item->total_amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="5" class="text-end">Total:</th>
                                            <th>{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Billing Information</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong><br>{{ $order->billing_name }}</p>
                        <p><strong>Email:</strong><br>{{ $order->billing_email }}</p>

                        @if ($order->billing_address)
                            <p>
                                <strong>Address:</strong><br>
                                @if (is_array($order->billing_address))
                                    {{ $order->billing_address['address_one'] ?? '' }}<br>
                                    @if (!empty($order->billing_address['address_two']))
                                        {{ $order->billing_address['address_two'] }}<br>
                                    @endif
                                    {{ $order->billing_address['city'] ?? '' }},
                                    {{ $order->billing_address['state_province'] ?? '' }}
                                    {{ $order->billing_address['postal_code'] ?? '' }}<br>
                                    {{ $order->billing_address['country_code'] ?? '' }}
                                @else
                                    {{ $order->billing_address }}
                                @endif
                            </p>
                        @endif
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Actions</h3>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('billing.invoice', $order) }}" class="btn btn-primary btn-block mb-2">
                            <i class="bi bi-file-text"></i> View Invoice
                        </a>
                        <a href="{{ route('billing.invoice.download', $order) }}"
                            class="btn btn-success btn-block mb-2">
                            <i class="bi bi-download"></i> Download Invoice (PDF)
                        </a>
                        <a href="{{ route('billing.invoice.view-pdf', $order) }}" class="btn btn-info btn-block mb-2"
                            target="_blank">
                            <i class="bi bi-eye"></i> View PDF in Browser
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
