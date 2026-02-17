{{-- Included by create and edit with: tldPricing (optional), tlds, currencies, action, method, submitLabel --}}
<form class="card" method="POST" action="{{ $action }}">
    @csrf
    @if (strtoupper($method ?? 'POST') !== 'POST')
        @method($method)
    @endif
    <div class="card-body">
        @if ($tldPricing)
            <div class="form-group">
                <label for="uuid">UUID</label>
                <input type="text" name="uuid" id="uuid" class="form-control" value="{{ $tldPricing->uuid }}" disabled>
                <small class="form-text text-muted">Unique identifier (read-only).</small>
            </div>
        @endif

        <div class="form-group">
            <label for="tld_id">TLD</label>
            <select name="tld_id" id="tld_id" class="form-control select2-tld @error('tld_id') is-invalid @enderror" data-placeholder="— None —">
                <option value="">— None —</option>
                @foreach ($tlds as $tld)
                    <option value="{{ $tld->id }}"
                        {{ old('tld_id', $tldPricing?->tld_id) == $tld->id ? 'selected' : '' }}>
                        {{ $tld->name }}
                    </option>
                @endforeach
            </select>
            @error('tld_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
            @if (!$tldPricing)
                <small class="form-text text-muted">Optional. Leave empty for global pricing.</small>
            @endif
        </div>

        <div class="form-group">
            <label for="currency_id">Currency <span class="text-danger">*</span></label>
            <select name="currency_id" id="currency_id" class="form-control @error('currency_id') is-invalid @enderror" required>
                @if (!$tldPricing)
                    <option value="">Select currency</option>
                @endif
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}"
                        {{ old('currency_id', $tldPricing?->currency_id) == $currency->id ? 'selected' : '' }}>
                        {{ $currency->code }}
                    </option>
                @endforeach
            </select>
            @error('currency_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="register_price">Register price <span class="text-danger">*</span></label>
                    <input type="number" name="register_price" id="register_price" min="0" step="1"
                        class="form-control @error('register_price') is-invalid @enderror"
                        value="{{ old('register_price', $tldPricing?->register_price) }}" required>
                    @error('register_price')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                    @if (!$tldPricing)
                        <small class="form-text text-muted">In smallest currency unit (e.g. cents).</small>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="renew_price">Renew price <span class="text-danger">*</span></label>
                    <input type="number" name="renew_price" id="renew_price" min="0" step="1"
                        class="form-control @error('renew_price') is-invalid @enderror"
                        value="{{ old('renew_price', $tldPricing?->renew_price) }}" required>
                    @error('renew_price')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                    @if (!$tldPricing)
                        <small class="form-text text-muted">In smallest currency unit (e.g. cents).</small>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="redemption_price">Redemption price</label>
                    <input type="number" name="redemption_price" id="redemption_price" min="0" step="1"
                        class="form-control @error('redemption_price') is-invalid @enderror"
                        value="{{ old('redemption_price', $tldPricing?->redemption_price) }}">
                    @error('redemption_price')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="transfer_price">Transfer price</label>
                    <input type="number" name="transfer_price" id="transfer_price" min="0" step="1"
                        class="form-control @error('transfer_price') is-invalid @enderror"
                        value="{{ old('transfer_price', $tldPricing?->transfer_price) }}">
                    @error('transfer_price')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="is_current">Current <span class="text-danger">*</span></label>
            <select name="is_current" id="is_current" class="form-control @error('is_current') is-invalid @enderror" required>
                <option value="1" {{ old('is_current', $tldPricing?->is_current ?? true) ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ !old('is_current', $tldPricing?->is_current ?? true) ? 'selected' : '' }}>No</option>
            </select>
            @error('is_current')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        @if ($tldPricing)
        <div class="form-group">
            <label for="reason">Reason for change <span id="reason-required-indicator" class="text-danger" style="display: none;">*</span></label>
            <textarea name="reason" id="reason" rows="2"
                class="form-control @error('reason') is-invalid @enderror"
                placeholder="Describe why this price is being changed...">{{ old('reason') }}</textarea>
            @error('reason')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
            <small class="form-text text-muted">Required when changing price fields.</small>
        </div>
        @endif

        <div class="form-group">
            <label for="effective_date">Effective date <span class="text-danger">*</span></label>
            <input type="date" name="effective_date" id="effective_date"
                class="form-control @error('effective_date') is-invalid @enderror"
                value="{{ old('effective_date', $tldPricing?->effective_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
            @error('effective_date')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-floppy"></i> {{ $submitLabel ?? 'Save' }}
        </button>
        <a href="{{ route('admin.tld-pricings.index') }}" class="btn btn-secondary float-right">
            <i class="bi bi-dash-circle"></i> Cancel
        </a>
    </div>
</form>

@push('scripts')
<script>
    $(function () {
        $('#tld_id').select2({
            placeholder: '— None —',
            allowClear: true,
            width: '100%'
        });

        // Make reason required when price fields change (edit mode only)
        var reasonField = document.getElementById('reason');
        if (reasonField) {
            var priceFields = ['register_price', 'renew_price', 'redemption_price', 'transfer_price'];
            var reasonIndicator = document.getElementById('reason-required-indicator');
            var originalValues = {};

            priceFields.forEach(function(field) {
                var el = document.getElementById(field);
                if (el) originalValues[field] = el.value;
            });

            function checkPriceChanges() {
                var hasChange = false;
                priceFields.forEach(function(field) {
                    var el = document.getElementById(field);
                    if (el && el.value !== originalValues[field]) hasChange = true;
                });

                if (hasChange) {
                    reasonField.setAttribute('required', 'required');
                    reasonIndicator.style.display = 'inline';
                    reasonField.classList.add('border-warning');
                } else {
                    reasonField.removeAttribute('required');
                    reasonIndicator.style.display = 'none';
                    reasonField.classList.remove('border-warning');
                }
            }

            priceFields.forEach(function(field) {
                var el = document.getElementById(field);
                if (el) {
                    el.addEventListener('input', checkPriceChanges);
                    el.addEventListener('change', checkPriceChanges);
                }
            });

            checkPriceChanges();
        }
    });
</script>
@endpush
