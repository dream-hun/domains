<x-admin-layout>
    @section('page-title')
        Retry Payment - {{ $order->order_number }}
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Retry Payment</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('billing.index') }}">Billing</a></li>
                        <li class="breadcrumb-item"><a
                                href="{{ route('billing.show', $order) }}">{{ $order->order_number }}</a></li>
                        <li class="breadcrumb-item active">Retry Payment</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Order Summary</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Order Number:</strong></div>
                            <div class="col-sm-8">{{ $order->order_number }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Order Date:</strong></div>
                            <div class="col-sm-8">{{ $order->created_at->format('F d, Y H:i') }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Total Amount:</strong></div>
                            <div class="col-sm-8"><strong>@price($order->total_amount, $order->currency)</strong></div>
                        </div>

                        @if ($order->orderItems->isNotEmpty())
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th>Type</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->orderItems as $item)
                                            <tr>
                                                <td>{{ $item->domain_name }}</td>
                                                <td>{{ ucfirst(str_replace('_', ' ', $item->domain_type)) }}</td>
                                                <td>@price($item->total_amount, $item->currency)</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Select Payment Method</h3>
                    </div>
                    <div class="card-body">
                        @if (empty($paymentMethods))
                            <div class="alert alert-warning">
                                No payment methods are currently available. Please contact support.
                            </div>
                        @else
                            <form action="{{ route('billing.retry-payment.process', $order) }}" method="POST">
                                @csrf

                                <div class="row g-3 mb-4">
                                    @foreach ($paymentMethods as $method)
                                        <div class="col-12">
                                            <label class="d-block" style="cursor: pointer;">
                                                <div class="card border" id="card-{{ $method['id'] }}"
                                                    style="transition: all 0.2s;">
                                                    <div class="card-body d-flex align-items-center p-3">
                                                        <input type="radio" name="payment_method"
                                                            value="{{ $method['id'] }}" class="form-check-input me-3"
                                                            id="payment_{{ $method['id'] }}"
                                                            {{ $loop->first ? 'checked' : '' }}>
                                                        @if ($method['id'] === 'stripe')
                                                            <img src="{{ asset('credit-card.png') }}" alt="Credit card"
                                                                class="me-3" style="max-height: 3rem;">
                                                        @else
                                                            <img src="{{ asset('Momo.png') }}" alt="Mobile Money"
                                                                class="me-3" style="max-height: 3rem;">
                                                        @endif
                                                        <span
                                                            class="fw-bold">{{ $method['name'] }}</span>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>

                                @error('payment_method')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror

                                <button type="submit" class="btn btn-warning btn-block w-100 mb-2">
                                    <i class="bi bi-credit-card me-2"></i> Pay Now
                                </button>
                                <a href="{{ route('billing.show', $order) }}"
                                    class="btn btn-outline-secondary btn-block w-100">
                                    <i class="bi bi-arrow-left me-2"></i> Back to Order
                                </a>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
