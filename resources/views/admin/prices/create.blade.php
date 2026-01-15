@php use App\Enums\DomainType; @endphp
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
                        <li class="breadcrumb-item active"><a href="{{route('admin.prices.index')}}">Domain Prices</a>
                        </li>
                        <li class="breadcrumb-item active">Add New Tld</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="col-md-12">
                <form class="card" method="POST" action="{{route('admin.prices.store')}}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="tld">TLD <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="tld"
                                   id="tld"
                                   class="form-control @error('tld') is-invalid @enderror"
                                   value="{{ old('tld') }}"
                                   required>
                            @error('tld')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="type">Type <span class="text-danger">*</span></label>
                            <select name="type"
                                    id="type"
                                    class="form-control @error('type') is-invalid @enderror"
                                    required>
                                @foreach(DomainType::cases() as $type)
                                    <option
                                        value="{{ $type->value }}" {{ old('type') === $type->value ? 'selected' : '' }}>
                                        {{ $type->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select name="status"
                                    id="status"
                                    class="form-control @error('status') is-invalid @enderror">
                                <option
                                    value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option
                                    value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>
                                    Inactive
                                </option>
                            </select>
                            @error('status')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="register_price" class="form-label required">Register Price </label>
                            <input type="number"
                                   name="register_price"
                                   id="register_price"
                                   class="form-control @error('register_price') is-invalid @enderror"
                                   value="{{ old('register_price') }}"
                                   required>
                            @error('register_price')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="renewal_price">Renewal Price (cents) <span
                                    class="text-danger">*</span></label>
                            <input type="number"
                                   name="renewal_price"
                                   id="renewal_price"
                                   class="form-control @error('renewal_price') is-invalid @enderror"
                                   value="{{ old('renewal_price') }}"
                                   required>
                            @error('renewal_price')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="transfer_price">Transfer Price (cents) <span
                                    class="text-danger">*</span></label>
                            <input type="number"
                                   name="transfer_price"
                                   id="transfer_price"
                                   class="form-control @error('transfer_price') is-invalid @enderror"
                                   value="{{ old('transfer_price') }}"
                                   required>
                            @error('transfer_price')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="redemption_price">Redemption Price (cents)</label>
                            <input type="number"
                                   name="redemption_price"
                                   id="redemption_price"
                                   class="form-control @error('redemption_price') is-invalid @enderror"
                                   value="{{ old('redemption_price') }}">
                            @error('redemption_price')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="min_years">Min Years</label>
                            <input type="number"
                                   name="min_years"
                                   id="min_years"
                                   class="form-control @error('min_years') is-invalid @enderror"
                                   value="{{ old('min_years', 1) }}">
                            @error('min_years')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="max_years">Max Years</label>
                            <input type="number"
                                   name="max_years"
                                   id="max_years"
                                   class="form-control @error('max_years') is-invalid @enderror"
                                   value="{{ old('max_years', 10) }}">
                            @error('max_years')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <input type="text"
                                   name="description"
                                   id="description"
                                   class="form-control @error('description') is-invalid @enderror"
                                   value="{{ old('description') }}">
                            @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy"></i> Create
                        </button>
                        <a href="{{ route('admin.prices.index') }}" class="btn btn-secondary float-right">
                            <i class="bi bi-dash-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>

</x-admin-layout>
