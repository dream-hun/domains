<x-admin-layout>
    @section('page-title')
        Edit Domain Registration
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Edit Domain Registration</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.domains.index') }}">Domains</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.domains.info', $domain) }}">{{ $domain->name }}</a></li>
                        <li class="breadcrumb-item active">Edit Registration</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <form action="{{ route('admin.domains.update-registration', $domain) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Domain Registration Info</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Domain Name</label>
                                    <input type="text"
                                           class="form-control"
                                           value="{{ $domain->name }}"
                                           disabled>
                                    <small class="form-text text-muted">Domain name cannot be changed</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="owner_id" class="required">Owner</label>
                                    <select class="form-control select2bs4 @error('owner_id') is-invalid @enderror"
                                            id="owner_id"
                                            name="owner_id"
                                            required>
                                        <option value="">Select a user...</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" @selected(old('owner_id', $domain->owner_id) == $user->id)>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('owner_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="years" class="required">Registration Period (Years)</label>
                                    <input type="number"
                                           class="form-control @error('years') is-invalid @enderror"
                                           id="years"
                                           name="years"
                                           value="{{ old('years', $domain->years) }}"
                                           min="1"
                                           max="10"
                                           required>
                                    @error('years')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status" class="required">Status</label>
                                    <select class="form-control select2bs4 @error('status') is-invalid @enderror"
                                            id="status"
                                            name="status"
                                            required>
                                        @foreach($domainStatuses as $status)
                                            <option value="{{ $status->value }}"
                                                    @selected(old('status', $domain->status?->value) === $status->value)>
                                                {{ $status->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="auto_renew"
                                               name="auto_renew"
                                               value="1"
                                               @checked(old('auto_renew', $domain->auto_renew))>
                                        <label class="form-check-label" for="auto_renew">
                                            Enable automatic renewal
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="registered_at" class="required">Registered At</label>
                                    <input type="date"
                                           class="form-control @error('registered_at') is-invalid @enderror"
                                           id="registered_at"
                                           name="registered_at"
                                           value="{{ old('registered_at', $domain->registered_at?->format('Y-m-d')) }}"
                                           required>
                                    @error('registered_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expires_at" class="required">Expires At</label>
                                    <input type="date"
                                           class="form-control @error('expires_at') is-invalid @enderror"
                                           id="expires_at"
                                           name="expires_at"
                                           value="{{ old('expires_at', $domain->expires_at?->format('Y-m-d')) }}"
                                           required>
                                    @error('expires_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Custom Pricing (Optional)</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Set a custom price for this domain. Leave empty to use standard TLD pricing.</p>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="custom_price">Custom Price</label>
                                    <input type="number"
                                           class="form-control @error('custom_price') is-invalid @enderror"
                                           id="custom_price"
                                           name="custom_price"
                                           value="{{ old('custom_price', $domain->custom_price) }}"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00">
                                    @error('custom_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="custom_price_currency">Currency</label>
                                    <select class="form-control select2bs4 @error('custom_price_currency') is-invalid @enderror"
                                            id="custom_price_currency"
                                            name="custom_price_currency">
                                        <option value="">Select currency...</option>
                                        @foreach($currencies as $currency)
                                            <option value="{{ $currency->code }}"
                                                    @selected(old('custom_price_currency', $domain->custom_price_currency) === $currency->code)>
                                                {{ $currency->code }} - {{ $currency->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('custom_price_currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="custom_price_notes">Custom Price Notes</label>
                                    <textarea class="form-control @error('custom_price_notes') is-invalid @enderror"
                                              id="custom_price_notes"
                                              name="custom_price_notes"
                                              rows="2"
                                              maxlength="1000"
                                              placeholder="Optional notes about the custom pricing...">{{ old('custom_price_notes', $domain->custom_price_notes) }}</textarea>
                                    @error('custom_price_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.domains.info', $domain) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Registration</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.select2bs4').select2({
                    theme: 'bootstrap4',
                    width: '100%'
                });
            });
        </script>
    @endpush
</x-admin-layout>
