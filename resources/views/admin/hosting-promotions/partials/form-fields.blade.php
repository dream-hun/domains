@php
    $editing = isset($promotion);
    $defaultStart = now();
    $defaultStartValue = $defaultStart->copy()->format('Y-m-d\TH:i');
    $defaultEndValue = $defaultStart->copy()->addDays(7)->format('Y-m-d\TH:i');
@endphp

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="hosting_plan_id">Hosting Plan <span class="text-danger">*</span></label>
            <select name="hosting_plan_id" id="hosting_plan_id"
                    class="form-control @error('hosting_plan_id') is-invalid @enderror" required>
                <option value="">Select a plan</option>
                @foreach ($plans as $plan)
                    <option value="{{ $plan['id'] }}" {{ (int) old('hosting_plan_id', $editing ? $promotion->hosting_plan_id : '') === $plan['id'] ? 'selected' : '' }}>
                        {{ $plan['name'] }}{{ $plan['category'] ? ' ('.$plan['category'].')' : '' }}
                    </option>
                @endforeach
            </select>
            @error('hosting_plan_id')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="billing_cycle">Billing Cycle <span class="text-danger">*</span></label>
            <select name="billing_cycle" id="billing_cycle"
                    class="form-control @error('billing_cycle') is-invalid @enderror" required>
                <option value="">Select billing cycle</option>
                @foreach ($billingCycles as $cycle)
                    <option value="{{ $cycle->value }}" {{ old('billing_cycle', $editing ? $promotion->billing_cycle : '') === $cycle->value ? 'selected' : '' }}>
                        {{ $cycle->label() }}
                    </option>
                @endforeach
            </select>
            @error('billing_cycle')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="discount_percentage">Discount Percentage <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="number" step="0.01" name="discount_percentage" id="discount_percentage"
                       class="form-control @error('discount_percentage') is-invalid @enderror"
                       value="{{ old('discount_percentage', $editing ? $promotion->discount_percentage : '') }}"
                       min="0" max="100" required>
                <div class="input-group-append">
                    <span class="input-group-text">%</span>
                </div>
            </div>
            @error('discount_percentage')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="starts_at">Promotion Starts <span class="text-danger">*</span></label>
            <input type="datetime-local" name="starts_at" id="starts_at"
                   class="form-control @error('starts_at') is-invalid @enderror"
                       value="{{ old('starts_at', $editing ? $promotion->starts_at->format('Y-m-d\TH:i') : $defaultStartValue) }}"
                   required>
            @error('starts_at')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="ends_at">Promotion Ends <span class="text-danger">*</span></label>
            <input type="datetime-local" name="ends_at" id="ends_at"
                   class="form-control @error('ends_at') is-invalid @enderror"
                   value="{{ old('ends_at', $editing ? $promotion->ends_at->format('Y-m-d\TH:i') : $defaultEndValue) }}"
                   required>
            @error('ends_at')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

