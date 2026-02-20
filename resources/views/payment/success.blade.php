<x-admin-layout>
    @section('page-title')
        Payment Successful
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Payment Successful</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Payment Success</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="callout callout-success">
                        <h5><i class="bi bi-info-circle-fill"></i> Thank you:</h5>
                        Payment Successful!
                    </div>
                    <div class="invoice p-3 mb-3">

                        <div class="row">
                            <div class="col-12">
                                <h4>
                                    <i class="bi bi-globe"></i> {{config('app.name')}}.
                                    <small
                                        class="float-right">Date: {{$order->created_at->format('F d, Y H:i')}}</small>
                                </h4>
                            </div>

                        </div>
                        <!-- info row -->
                        <div class="row invoice-info">
                            <div class="col-sm-4 invoice-col">
                                From
                                <address>
                                    <strong>{{config('app.name')}}.</strong><br>
                                    {{$settings->address}}<br>
                                    Phone:{{$settings->phone}}<br>
                                    Email: {{$settings->email}}
                                </address>
                            </div>
                            <!-- /.col -->
                            <div class="col-sm-4 invoice-col">
                                To
                                <address>
                                    @php
                                        $address = $order->user->address;
                                    @endphp
                                    @if($address)
                                        <strong>{{ $address->full_name }}</strong><br>
                                        {{ $address->address_line_one }}<br>
                                        @if($address->address_line_two)
                                            {{ $address->address_line_two }}<br>
                                        @endif
                                        {{ $address->city }}, {{ $address->state }}
                                        @if($address->postal_code)
                                            {{ $address->postal_code }}
                                        @endif<br>
                                        @if($address->country_code)
                                            {{ $address->country_code }}<br>
                                        @endif
                                        Phone: {{ $address->phone_number }}<br>
                                        Email: {{ $address->email }}
                                    @else
                                        <strong>{{ $order->billing_name ?? $order->user->name }}</strong><br>
                                        Email: {{ $order->billing_email ?? $order->user->email }}
                                    @endif
                                </address>
                            </div>
                            <!-- /.col -->
                            <div class="col-sm-4 invoice-col">
                                <b>Invoice #{{$order->order_number}}</b><br>
                                <br>
                                <b>Order ID:</b> {{$order->order_number}}<br>
                                <b>Payment Due:</b> {{$order->isPending()}}<br>
                                <b>Account:</b> 968-34567
                            </div>
                            <!-- /.col -->
                        </div>
                        <!-- /.row -->

                        <!-- Table row -->
                        <div class="row">
                            <div class="col-12 table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Service Type</th>
                                        <th>Period</th>
                                        <th>Subtotal</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($order->orderItems as $item)
                                        <tr>
                                            <td>{{$item->domain_name}}</td>
                                            <td>{{ ucfirst($item->domain_type) }}</td>
                                            <td>{{ $item->years }} {{ $item->years == 1 ? 'Year' : 'Years' }}</td>

                                            <td>@price($item->total_amount, $order->currency)</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-6">
                                <p class="lead">Payment Method:</p>
                                @if($order->payment_method=='stripe')
                                    <img src="{{asset('Stripe_Logo,_revised_2016.svg.png')}}" alt="stripe" style="height: auto; width: 5rem;">
                                @else
                                    Momo
                                @endif

                            </div>

                            <div class="col-6">
                                <div class="table-responsive">
                                    <table class="table">
                                        <tbody>
                                        <tr>
                                            <th style="width:50%">Subtotal:</th>
                                            <td>@price($order->subtotal, $order->currency)</td>
                                        </tr>

                                        <tr>
                                            <th>Total:</th>
                                            <td>@price($order->total_amount, $order->currency)</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- /.col -->
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</x-admin-layout>
