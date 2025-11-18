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
                        <li class="breadcrumb-item active">Edit Price</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">Edit Hosting Plan Price</h3>
                        </div>
                        <form method="POST" action="{{ route('admin.hosting-plan-prices.update', $price->uuid) }}">
                            @csrf
                            @method('PATCH')

                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="hosting_plan_id">Hosting Plan <span class="text-danger">*</span></label>
                                            <select name="hosting_plan_id"
                                                    id="hosting_plan_id"
                                                    class="form-control @error('hosting_plan_id') is-invalid @enderror"
                                                    required>
                                                <option value="">Select a plan</option>
                                                @foreach($plans as $plan)
                                                    <option value="{{ $plan->id }}" {{ old('hosting_plan_id', $price->hosting_plan_id) == $plan->id ? 'selected' : '' }}>
                                                        {{ $plan->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('hosting_plan_id')
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
                                                <option value="monthly" {{ old('billing_cycle', $price->billing_cycle) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                                <option value="quarterly" {{ old('billing_cycle', $price->billing_cycle) === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                                <option value="semi-annually" {{ old('billing_cycle', $price->billing_cycle) === 'semi-annually' ? 'selected' : '' }}>Semi-Annually</option>
                                                <option value="annually" {{ old('billing_cycle', $price->billing_cycle) === 'annually' ? 'selected' : '' }}>Annually</option>
                                                <option value="biennially" {{ old('billing_cycle', $price->billing_cycle) === 'biennially' ? 'selected' : '' }}>Biennially</option>
                                                <option value="triennially" {{ old('billing_cycle', $price->billing_cycle) === 'triennially' ? 'selected' : '' }}>Triennially</option>
                                            </select>
                                            @error('billing_cycle')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="regular_price">Regular Price (cents) <span
                                                    class="text-danger">*</span></label>
                                            <input type="number"
                                                   name="regular_price"
                                                   id="regular_price"
                                                   class="form-control @error('regular_price') is-invalid @enderror"
                                                   value="{{ old('regular_price', $price->regular_price) }}"
                                                   required>
                                            @error('regular_price')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="promotional_price">Promotional Price (cents)</label>
                                            <input type="number"
                                                   name="promotional_price"
                                                   id="promotional_price"
                                                   class="form-control @error('promotional_price') is-invalid @enderror"
                                                   value="{{ old('promotional_price', $price->promotional_price) }}">
                                            @error('promotional_price')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="renewal_price">Renewal Price (cents) <span
                                                    class="text-danger">*</span></label>
                                            <input type="number"
                                                   name="renewal_price"
                                                   id="renewal_price"
                                                   class="form-control @error('renewal_price') is-invalid @enderror"
                                                   value="{{ old('renewal_price', $price->renewal_price) }}"
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
                                            <label for="discount_percentage">Discount Percentage</label>
                                            <input type="number"
                                                   name="discount_percentage"
                                                   id="discount_percentage"
                                                   class="form-control @error('discount_percentage') is-invalid @enderror"
                                                   value="{{ old('discount_percentage', $price->discount_percentage) }}"
                                                   min="0"
                                                   max="100">
                                            @error('discount_percentage')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="promotional_start_date">Promotional Start Date</label>
                                            <input type="date"
                                                   name="promotional_start_date"
                                                   id="promotional_start_date"
                                                   class="form-control @error('promotional_start_date') is-invalid @enderror"
                                                   value="{{ old('promotional_start_date', $price->promotional_start_date?->format('Y-m-d')) }}">
                                            @error('promotional_start_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="promotional_end_date">Promotional End Date</label>
                                            <input type="date"
                                                   name="promotional_end_date"
                                                   id="promotional_end_date"
                                                   class="form-control @error('promotional_end_date') is-invalid @enderror"
                                                   value="{{ old('promotional_end_date', $price->promotional_end_date?->format('Y-m-d')) }}">
                                            @error('promotional_end_date')
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
                                                <option
                                                    value="active" {{ old('status', $price->status?->value ?? 'active') === 'active' ? 'selected' : '' }}>
                                                    Active
                                                </option>
                                                <option
                                                    value="inactive" {{ old('status', $price->status?->value ?? 'active') === 'inactive' ? 'selected' : '' }}>
                                                    Inactive
                                                </option>
                                            </select>
                                            @error('status')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('admin.hosting-plan-prices.index') }}" class="btn btn-secondary mr-2">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-save"></i> Update
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

