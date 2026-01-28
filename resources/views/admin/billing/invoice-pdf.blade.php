<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }

        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }

        .header-left {
            float: left;
            width: 50%;
        }

        .header-right {
            float: right;
            width: 50%;
            text-align: right;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        h1 {
            color: #007bff;
            font-size: 24px;
            margin: 0 0 10px 0;
        }

        h2 {
            font-size: 18px;
            color: #333;
            margin: 20px 0 10px 0;
        }

        .invoice-info {
            margin-bottom: 30px;
        }

        .billing-section {
            margin-bottom: 30px;
        }

        .bill-to {
            float: left;
            width: 50%;
        }

        .payment-status {
            float: right;
            width: 50%;
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table thead {
            background-color: #f8f9fa;
        }

        table th,
        table td {
            padding: 10px;
            border: 1px solid #dee2e6;
            text-align: left;
        }

        table th {
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals {
            float: right;
            width: 40%;
            margin-top: 20px;
        }

        .totals table {
            margin: 0;
        }

        .totals table td {
            padding: 5px 10px;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #333;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 11px;
        }

        .notes {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 3px solid #007bff;
        }
    </style>
</head>

<body>
    <div class="header clearfix">
        <div class="header-left">
            <h1>{{ config('app.name') }}</h1>
            <p style="margin: 0;">Domain Registration Services</p>
        </div>
        <div class="header-right">
            <h2 style="margin: 0;">INVOICE</h2>
            <p style="margin: 5px 0;"><strong>Invoice #:</strong> {{ $order->order_number }}</p>
            <p style="margin: 5px 0;"><strong>Date:</strong> {{ $order->created_at->format('F d, Y') }}</p>
        </div>
    </div>

    <div class="billing-section clearfix">
        <div class="bill-to">
            <h2>Bill To:</h2>
            <p style="margin: 5px 0;"><strong>{{ $order->billing_name }}</strong></p>
            <p style="margin: 5px 0;">{{ $order->billing_email }}</p>
            @if ($order->billing_address)
                @if (is_array($order->billing_address))
                    <p style="margin: 5px 0;">{{ $order->billing_address['address_one'] ?? '' }}</p>
                    @if (!empty($order->billing_address['address_two']))
                        <p style="margin: 5px 0;">{{ $order->billing_address['address_two'] }}</p>
                    @endif
                    <p style="margin: 5px 0;">
                        {{ $order->billing_address['city'] ?? '' }},
                        {{ $order->billing_address['state_province'] ?? '' }}
                        {{ $order->billing_address['postal_code'] ?? '' }}
                    </p>
                    <p style="margin: 5px 0;">{{ $order->billing_address['country_code'] ?? '' }}</p>
                @endif
            @endif
        </div>
        <div class="payment-status">
            <h2>Payment Status:</h2>
            @if ($order->isPaid())
                <span class="badge badge-success">PAID</span>
                @if ($order->processed_at)
                    <p style="margin: 10px 0 0 0;">Paid on: {{ $order->processed_at->format('F d, Y H:i') }}</p>
                @endif
            @elseif ($order->isPending())
                <span class="badge badge-warning">PENDING</span>
            @elseif ($order->isFailed())
                <span class="badge badge-danger">FAILED</span>
            @elseif ($order->isCancelled())
                <span class="badge badge-secondary">CANCELLED</span>
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
                <p style="margin: 10px 0 0 0;"><strong>KPay Transaction ID:</strong><br>{{ $kpayTransactionId }}</p>
            @endif
        </div>
    </div>

    <div style="clear: both;"></div>

    <h2>Order Details</h2>
    @if ($order->orderItems->isEmpty())
        <p style="padding: 15px; background-color: #f8f9fa; border-left: 3px solid #007bff;">
            <strong>No items found in this order.</strong>
        </p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Type</th>
                    <th class="text-center">Period</th>
                    <th class="text-right">Price</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Total</th>
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
                                    <br><small>Domain: {{ $linkedDomain }}</small>
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
                                    <br><small>Subscription ID: {{ $subscriptionId }}</small>
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
                        <td class="text-right">@price($item->price, $item->currency)</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">@price($item->total_amount, $item->currency)</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="clearfix">
        @if ($order->notes)
            <div class="notes" style="width: 55%; float: left;">
                <h3 style="margin: 0 0 10px 0; font-size: 14px;">Notes:</h3>
                <p style="margin: 0;">{{ $order->notes }}</p>
            </div>
        @endif

        <div class="totals">
            <table>
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td class="text-right">@price($order->subtotal ?? $order->total_amount, $order->currency)</td>
                </tr>
                @if (($order->tax ?? 0) > 0)
                    <tr>
                        <td><strong>Tax:</strong></td>
                        <td class="text-right">@price($order->tax, $order->currency)</td>
                    </tr>
                @endif
                @if (($order->discount_amount ?? 0) > 0)
                    <tr>
                        <td><strong>Discount:</strong></td>
                        <td class="text-right">-@price($order->discount_amount, $order->currency)</td>
                    </tr>
                @endif
                <tr style="background-color: #f8f9fa;">
                    <td><strong>Total:</strong></td>
                    <td class="text-right"><strong>@price($order->total_amount, $order->currency)</strong></td>
                </tr>
            </table>
        </div>
    </div>

    <div style="clear: both;"></div>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>If you have any questions about this invoice, please contact us.</p>
    </div>
</body>

</html>
