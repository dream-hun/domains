<x-admin-layout>
    @section('page-title')
        Invoice - {{ $order->order_number }}
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Invoice</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('billing.index') }}">Billing</a></li>
                        <li class="breadcrumb-item active">Invoice</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-sm-6">
                                <h3>{{ config('app.name') }}</h3>
                                <p class="mb-1">Domain Registration Services</p>
                            </div>
                            <div class="col-sm-6 text-end">
                                <h3>INVOICE</h3>
                                <p class="mb-1"><strong>Invoice #:</strong> {{ $order->order_number }}</p>
                                <p class="mb-1"><strong>Date:</strong> {{ $order->created_at->format('F d, Y') }}</p>
                            </div>
                        </div>

                        <hr>

                        <div class="row mb-4">
                            <div class="col-sm-6">
                                <h5>Bill To:</h5>
                                <p class="mb-1">{{ $order->billing_name }}</p>
                                <p class="mb-1">{{ $order->billing_email }}</p>
                                @if ($order->billing_address)
                                    @if (is_array($order->billing_address))
                                        <p class="mb-1">{{ $order->billing_address['address_one'] ?? '' }}</p>
                                        @if (!empty($order->billing_address['address_two']))
                                            <p class="mb-1">{{ $order->billing_address['address_two'] }}</p>
                                        @endif
                                        <p class="mb-1">
                                            {{ $order->billing_address['city'] ?? '' }},
                                            {{ $order->billing_address['state_province'] ?? '' }}
                                            {{ $order->billing_address['postal_code'] ?? '' }}
                                        </p>
                                        <p class="mb-1">{{ $order->billing_address['country_code'] ?? '' }}</p>
                                    @endif
                                @endif
                            </div>
                            <div class="col-sm-6 text-end">
                                <h5>Payment Status:</h5>
                                @if ($order->isPaid())
                                    <span class="badge bg-success fs-6">PAID</span>
                                    @if ($order->processed_at)
                                        <p class="mt-2">Paid on: {{ $order->processed_at->format('F d, Y H:i') }}</p>
                                    @endif
                                @elseif ($order->isPending())
                                    <span class="badge bg-warning fs-6">PENDING</span>
                                @elseif ($order->isFailed())
                                    <span class="badge bg-danger fs-6">FAILED</span>
                                @elseif ($order->isCancelled())
                                    <span class="badge bg-secondary fs-6">CANCELLED</span>
                                @endif
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
                                    <p class="mt-2 mb-0"><strong>KPay Transaction ID:</strong><br><code>{{ $kpayTransactionId }}</code></p>
                                @endif
                            </div>
                        </div>

                        @if ($order->orderItems->isEmpty())
                            <div class="alert alert-info" role="alert">
                                <i class="bi bi-info-circle"></i> No items found in this order.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Description</th>
                                            <th>Type</th>
                                            <th class="text-center">Period</th>
                                            <th class="text-end">Price</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Total</th>
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

                                                            // Try to get plan name from subscription
                                                            if ($subscriptionId) {
                                                                $subscription = \App\Models\Subscription::query()->find($subscriptionId);
                                                                if ($subscription && $subscription->product_snapshot) {
                                                                    $planName = $subscription->product_snapshot['plan']['name'] ?? null;
                                                                }
                                                                // Fallback: try to get from plan relationship
                                                                if (!$planName && $subscription && $subscription->plan) {
                                                                    $planName = $subscription->plan->name;
                                                                }
                                                            }

                                                            // Fallback: try to get from hosting_plan_id in metadata
                                                            if (!$planName && isset($metadata['hosting_plan_id'])) {
                                                                $plan = \App\Models\HostingPlan::query()->find($metadata['hosting_plan_id']);
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
                                                <td class="text-center">
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
                                                <td class="text-end">@price($item->price, $item->currency)</td>
                                                <td class="text-center">{{ $item->quantity }}</td>
                                                <td class="text-end">@price($item->total_amount, $item->currency)</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="row mt-4">
                            <div class="col-sm-6">
                                @if ($order->notes)
                                    <h5>Notes:</h5>
                                    <p>{{ $order->notes }}</p>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <table class="table">
                                    <tr>
                                        <td class="text-end"><strong>Subtotal:</strong></td>
                                        <td class="text-end">@price($order->subtotal ?? $order->total_amount, $order->currency)</td>
                                    </tr>
                                    @if (($order->tax ?? 0) > 0)
                                        <tr>
                                            <td class="text-end"><strong>Tax:</strong></td>
                                            <td class="text-end">@price($order->tax, $order->currency)</td>
                                        </tr>
                                    @endif
                                    @if (($order->discount_amount ?? 0) > 0)
                                        <tr>
                                            <td class="text-end"><strong>Discount:</strong></td>
                                            <td class="text-end">-@price($order->discount_amount, $order->currency)</td>
                                        </tr>
                                    @endif
                                    <tr class="table-light">
                                        <td class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end"><strong>@price($order->total_amount, $order->currency)</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <hr>

                        <div class="row mt-4">
                            <div class="col-12 text-center">
                                <p class="text-muted">Thank you for your business!</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12">
                                <a href="{{ route('billing.index') }}" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Back to Orders
                                </a>
                                <a href="{{ route('billing.invoice.download', $order) }}" class="btn btn-success">
                                    <i class="bi bi-download"></i> Download PDF
                                </a>
                                <a href="{{ route('billing.invoice.view-pdf', $order) }}" class="btn btn-info"
                                    target="_blank">
                                    <i class="bi bi-eye"></i> View PDF
                                </a>
                                <button onclick="window.print()" class="btn btn-primary">
                                    <i class="bi bi-printer"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('styles')
        <style>
            @media print {

                .content-header,
                .card-footer,
                .breadcrumb,
                aside,
                nav {
                    display: none !important;
                }

                .card {
                    border: none !important;
                    box-shadow: none !important;
                }
            }
        </style>
    @endsection
</x-admin-layout>
