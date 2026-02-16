<x-admin-layout>
    @section('page-title')
        Currencies
    @endsection
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Edit Currency</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.currencies.index') }}">Currencies</a></li>
                        <li class="breadcrumb-item active">Edit Currency</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="col-md-12">
                <form class="card" method="POST" action="{{ route('admin.currencies.update', $currency) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if ($currency->is_base)
                            <div class="alert alert-info">
                                <i class="icon fas fa-info-circle"></i>
                                <strong>Base Currency:</strong> This is the base currency for the system.
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="uuid">UUID</label>
                            <input type="text" name="uuid" id="uuid" class="form-control" value="{{ $currency->uuid }}" disabled>
                            <small class="form-text text-muted">Unique identifier (read-only)</small>
                        </div>
                        <div class="form-group">
                            <label for="code">Currency Code</label>
                            <input type="text" name="code" id="code" class="form-control"
                                value="{{ $currency->code }}" disabled>
                            <small class="form-text text-muted">Currency code cannot be changed</small>
                        </div>
                        <div class="form-group">
                            <label for="name">Currency Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $currency->name) }}" required>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="symbol">Symbol <span class="text-danger">*</span></label>
                            <input type="text" name="symbol" id="symbol"
                                class="form-control @error('symbol') is-invalid @enderror"
                                value="{{ old('symbol', $currency->symbol) }}" required>
                            @error('symbol')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                                value="1" {{ old('is_active', $currency->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Status (Active)
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_base" id="is_base" class="form-check-input" value="1"
                                {{ old('is_base', $currency->is_base) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_base">
                                Base Currency
                            </label>
                            <small class="form-text text-muted">Only one currency can be set as base</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy"></i> Update
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
