<x-admin-layout>
    @section('page-title')
        Currencies
    @endsection
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Add New Currency</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.currencies.index') }}">Currencies</a></li>
                        <li class="breadcrumb-item active">Add New Currency</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="col-md-12">
                <form class="card" method="POST" action="{{ route('admin.currencies.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="code">Currency Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="code"
                                class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}"
                                placeholder="USD" maxlength="3" style="text-transform:uppercase" required>
                            @error('code')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">ISO 4217 currency code (3 uppercase letters)</small>
                        </div>
                        <div class="form-group">
                            <label for="name">Currency Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                placeholder="US Dollar" required>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="symbol">Symbol <span class="text-danger">*</span></label>
                            <input type="text" name="symbol" id="symbol"
                                class="form-control @error('symbol') is-invalid @enderror" value="{{ old('symbol') }}"
                                placeholder="$" required>
                            @error('symbol')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                                value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Status (Active)
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_base" id="is_base" class="form-check-input" value="1"
                                {{ old('is_base') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_base">
                                Base Currency
                            </label>
                            <small class="form-text text-muted">Only one currency can be set as base</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy"></i> Create
                        </button>
                        <a href="{{ route('admin.currencies.index') }}" class="btn btn-secondary float-right">
                            <i class="bi bi-dash-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>

</x-admin-layout>
