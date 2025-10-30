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
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Description</th>
                                        <th>Type</th>
                                        <th class="text-center">Years</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->orderItems as $item)
                                        <tr>
                                            <td>{{ $item->domain_name }}</td>
                                            <td>{{ ucfirst($item->domain_type) }}</td>
                                            <td class="text-center">{{ $item->years }}</td>
                                            <td class="text-end">{{ $item->currency }}
                                                {{ number_format($item->price, 2) }}</td>
                                            <td class="text-center">{{ $item->quantity }}</td>
                                            <td class="text-end">{{ $item->currency }}
                                                {{ number_format($item->total_amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

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
                                        <td class="text-end">{{ $order->currency }}
                                            {{ number_format($order->total_amount, 2) }}</td>
                                    </tr>
                                    <tr class="table-light">
                                        <td class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end"><strong>{{ $order->currency }}
                                                {{ number_format($order->total_amount, 2) }}</strong></td>
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


