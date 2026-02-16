<x-admin-layout>
    @section('page-title')
        Hosting Plan Prices
    @endsection
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Hosting Plan Prices</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('dashboard')}}">Dashboard</a></li>
                        <li class="breadcrumb-item active"><a href="{{route('admin.hosting-plan-prices.index')}}">Hosting Plan Prices</a>
                        </li>
                        <li class="breadcrumb-item active">Add New Price</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="col-md-12">
                @include('admin.hosting-plan-prices._form', [
                    'price' => null,
                    'categories' => $categories,
                    'plans' => $plans,
                    'currencies' => $currencies,
                    'action' => route('admin.hosting-plan-prices.store'),
                    'method' => 'POST',
                    'submitLabel' => 'Create',
                ])
            </div>
        </div>
    </section>

    @section('scripts')
        @include('admin.hosting-plan-prices.partials.dependent-plan-script')
    @endsection

</x-admin-layout>
