<x-admin-layout>
    @section('page-title')
        Domain Prices
    @endsection
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Domain Prices</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('dashboard')}}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Domain Prices</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid col-md-12">
            <div class="row">
                <div class="col-12">
                    <div class="py-lg-2">
                        <a href="{{ route('admin.prices.create') }}" class="btn btn-success">
                            <i class="bi bi-plus-lg"></i> Add New Price
                        </a>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Manage Domain Prices</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                    <h5><i class="icon fas fa-check"></i> Success!</h5>
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if($prices->isEmpty())
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> Info!</h5>
                                    No domain prices found.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover">
                                        <thead>
                                        <tr>
                                            <th>TLD</th>
                                            <th>Type</th>
                                            <th>Register</th>
                                            <th>Renewal</th>
                                            <th>Transfer</th>
                                            <th>Redemption</th>
                                            <th style="width: 150px">Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($prices as $price)
                                            <tr>
                                                <td>{{ $price->tld }}</td>
                                                <td>
                                                    @if(isset($price->type) && method_exists($price->type, 'label'))
                                                        <span class="badge {{ $price->type->color() }}">{{ $price->type->label() }}</span>
                                                    @else
                                                        <span class="badge badge-secondary">{{ ucfirst((string) $price->type) }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $price->getFormattedPrice('register_price') }}</td>
                                                <td>{{ $price->getFormattedPrice('renewal_price') }}</td>
                                                <td>{{ $price->getFormattedPrice('transfer_price') }}</td>
                                                <td>
                                                    @if(! is_null($price->redemption_price))
                                                        {{ $price->getFormattedPrice('redemption_price') }}
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="{{ route('admin.prices.edit', $price->uuid) }}"
                                                           class="btn btn-warning btn-sm"
                                                           title="Edit">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </a>

                                                        <form action="{{ route('admin.prices.destroy', $price->uuid) }}"
                                                              method="POST"
                                                              style="display:inline-block;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="btn btn-danger btn-sm"
                                                                    onclick="return confirm('Are you sure you want to delete this price?');"
                                                                    title="Delete">
                                                                <span class="bi bi-trash"></span> Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>


                            @endif
                        </div>

                    </div>
                    <div class="d-flex justify-content-center mt-3 float-right">
                        {{ $prices->links('vendor.pagination.adminlte') }}
                    </div>
                </div>

            </div>
        </div>
    </section>
</x-admin-layout>
