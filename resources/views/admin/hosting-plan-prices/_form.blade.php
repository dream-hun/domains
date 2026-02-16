{{-- Included by create and edit with: price (optional), categories, plans, currencies, action, method, submitLabel --}}
@php
    use App\Enums\Hosting\BillingCycle;
@endphp
<form class="card" method="POST" action="{{ $action }}" id="price-form">
    @csrf
    @if (strtoupper($method ?? 'POST') !== 'POST')
        @method($method)
    @endif
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="hosting_category_id">Hosting Category <span class="text-danger">*</span></label>
                    <select name="hosting_category_id"
                            id="hosting_category_id"
                            class="form-control @error('hosting_category_id') is-invalid @enderror"
                            required>
                        <option value="">Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('hosting_category_id', $price?->plan?->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('hosting_category_id')
                    <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="hosting_plan_id">Hosting Plan <span class="text-danger">*</span></label>
                    <select name="hosting_plan_id"
                            id="hosting_plan_id"
                            class="form-control @error('hosting_plan_id') is-invalid @enderror"
                            required>
                        <option value="">Select a plan</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}"
                                    data-category="{{ $plan->category_id }}"
                                    {{ old('hosting_plan_id', $price?->hosting_plan_id) == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('hosting_plan_id')
                    <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="currency_id">Currency <span class="text-danger">*</span></label>
                    <select name="currency_id"
                            id="currency_id"
                            class="form-control @error('currency_id') is-invalid @enderror"
                            required>
                        @if (!$price)
                            <option value="">Select currency</option>
                        @endif
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->id }}"
                                {{ old('currency_id', $price?->currency_id) == $currency->id ? 'selected' : '' }}>
                                {{ $currency->code }}
                            </option>
                        @endforeach
                    </select>
                    @error('currency_id')
                    <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="billing_cycle">Billing Cycle <span class="text-danger">*</span></label>
                    <select name="billing_cycle"
                            id="billing_cycle"
                            class="form-control @error('billing_cycle') is-invalid @enderror"
                            required>
                        @foreach(BillingCycle::cases() as $cycle)
                            <option value="{{ $cycle->value }}" {{ old('billing_cycle', $price?->billing_cycle) === $cycle->value ? 'selected' : '' }}>
                                {{ $cycle->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('billing_cycle')
                    <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="regular_price">Regular Price <span class="text-danger">*</span></label>
                    <input type="number"
                           name="regular_price"
                           id="regular_price"
                           class="form-control @error('regular_price') is-invalid @enderror"
                           value="{{ old('regular_price', $price?->regular_price) }}"
                           min="0"
                           step="0.01"
                           required>
                    @error('regular_price')
                    <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="renewal_price">Renewal Price <span class="text-danger">*</span></label>
                    <input type="number"
                           name="renewal_price"
                           id="renewal_price"
                           class="form-control @error('renewal_price') is-invalid @enderror"
                           value="{{ old('renewal_price', $price?->renewal_price) }}"
                           min="0"
                           step="0.01"
                           required>
                    @error('renewal_price')
                    <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status"
                            id="status"
                            class="form-control @error('status') is-invalid @enderror">
                        <option value="active" {{ old('status', $price?->status?->value ?? 'active') === 'active' ? 'selected' : '' }}>
                            Active
                        </option>
                        <option value="inactive" {{ old('status', $price?->status?->value) === 'inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>
                    </select>
                    @error('status')
                    <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="is_current">Current <span class="text-danger">*</span></label>
                    <select name="is_current"
                            id="is_current"
                            class="form-control @error('is_current') is-invalid @enderror"
                            required>
                        <option value="1" {{ old('is_current', $price?->is_current ?? true) ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ !old('is_current', $price?->is_current ?? true) ? 'selected' : '' }}>No</option>
                    </select>
                    @error('is_current')
                    <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="effective_date">Effective Date <span class="text-danger">*</span></label>
                    <input type="date"
                           name="effective_date"
                           id="effective_date"
                           class="form-control @error('effective_date') is-invalid @enderror"
                           value="{{ old('effective_date', $price?->effective_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                           required>
                    @error('effective_date')
                    <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        @if($price)
            <hr class="my-4">
            <div class="form-group">
                <label for="reason">
                    Reason for Price Change
                    <span class="text-danger" id="reason-required-indicator" style="display: none;">*</span>
                </label>
                <textarea name="reason"
                          id="reason"
                          rows="4"
                          class="form-control @error('reason') is-invalid @enderror"
                          placeholder="Please provide a reason for changing the price...">{{ old('reason') }}</textarea>
                <small class="form-text text-muted">
                    Required when any price field is changed. This reason will be recorded in the price change history.
                </small>
                @error('reason')
                <span class="invalid-feedback d-block">{{ $message }}</span>
                @enderror
            </div>
        @endif
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-floppy"></i> {{ $submitLabel ?? 'Save' }}
        </button>
        <a href="{{ route('admin.hosting-plan-prices.index') }}" class="btn btn-secondary float-right">
            <i class="bi bi-dash-circle"></i> Cancel
        </a>
    </div>
</form>
