<x-admin-layout>
    @section('page-title')
        Billing Management
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Billing & Orders</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Billing</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Orders</h3>
                    </div>
                    <div class="card-body">
                        @if ($orders->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order Number</th>
                                            <th>Payment Reference</th>
                                            <th>Date</th>
                                            @if (Auth::user()->isAdmin())
                                                <th>Customer</th>
                                            @endif
                                            <th>Total Amount</th>
                                            <th>Payment Status</th>
                                            <th>Order Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($orders as $order)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('billing.show', $order) }}"
                                                        class="text-primary fw-bold">
                                                        {{ $order->order_number }}
                                                    </a>
                                                </td>
                                                <td>
                                                    @php

                                                        $payment = null;
                                                        if ($order->relationLoaded('payments') && $order->payments->isNotEmpty()) {

                                                            $payment = $order->payments->sortByDesc(function ($p) {
                                                                return ($p->attempt_number ?? 0) * 1000000 + ($p->id ?? 0);
                                                            })->first();
                                                        } else {
                                                            $payment = $order->latestPaymentAttempt();
                                                        }
                                                        $paymentRef = null;
                                                        if ($payment) {
                                                            if ($order->payment_method === 'kpay' && $payment->kpay_transaction_id) {
                                                                $paymentRef = $payment->kpay_transaction_id;
                                                            } elseif ($order->payment_method === 'stripe' && $payment->stripe_payment_intent_id) {
                                                                $paymentRef = $payment->stripe_payment_intent_id;
                                                            }
                                                        }
                                                        // Fallback to order-level payment references
                                                        if (!$paymentRef) {
                                                            if ($order->payment_method === 'kpay' && $order->stripe_payment_intent_id) {
                                                                $paymentRef = $order->stripe_payment_intent_id;
                                                            }
                                                        }
                                                    @endphp
                                                    {{ $paymentRef ?? 'N/A' }}
                                                </td>
                                                <td>{{ $order->created_at->format('M d, Y') }}</td>
                                                @if (Auth::user()->isAdmin())
                                                    <td>{{ $order->user->name ?? 'N/A' }}</td>
                                                @endif
                                                <td>
                                                    <strong>{{ $order->currency }}
                                                        {{ number_format($order->total_amount) }}</strong>
                                                </td>
                                                <td>
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
                                                </td>
                                                <td>
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
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('billing.show', $order) }}"
                                                            class="btn btn-sm btn-info" title="View Details">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="{{ route('billing.invoice', $order) }}"
                                                            class="btn btn-sm btn-primary" title="View Invoice">
                                                            <i class="bi bi-file-text"></i>
                                                        </a>
                                                        <a href="{{ route('billing.invoice.download', $order) }}"
                                                            class="btn btn-sm btn-success" title="Download PDF">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">
                                {{ $orders->links('vendor.pagination.adminlte') }}
                            </div>
                        @else
                            <div class="alert alert-info" role="alert">
                                <i class="bi bi-info-circle"></i> No orders found.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
