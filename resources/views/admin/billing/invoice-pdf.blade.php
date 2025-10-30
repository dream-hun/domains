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
        </div>
    </div>

    <div style="clear: both;"></div>

    <h2>Order Details</h2>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Type</th>
                <th class="text-center">Years</th>
                <th class="text-right">Price</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->orderItems as $item)
                <tr>
                    <td>{{ $item->domain_name }}</td>
                    <td>{{ ucfirst($item->domain_type) }}</td>
                    <td class="text-center">{{ $item->years }}</td>
                    <td class="text-right">{{ $item->currency }} {{ number_format($item->price, 2) }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ $item->currency }} {{ number_format($item->total_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

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
                    <td class="text-right">{{ $order->currency }} {{ number_format($order->total_amount, 2) }}</td>
                </tr>
                <tr style="background-color: #f8f9fa;">
                    <td><strong>Total:</strong></td>
                    <td class="text-right"><strong>{{ $order->currency }}
                            {{ number_format($order->total_amount, 2) }}</strong></td>
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


