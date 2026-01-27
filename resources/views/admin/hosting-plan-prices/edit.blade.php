@php
use Illuminate\Support\Str;
@endphp
<x-admin-layout>
    @section('page-title')
        Hosting Plan Prices
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-edit mr-2"></i>Edit Hosting Plan Price
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.hosting-plan-prices.index') }}">Hosting Plan Prices</a></li>
                        <li class="breadcrumb-item active">{{ $price->plan?->name ?? 'Edit Price' }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-server mr-2"></i>Hosting Plan Price Information
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-{{ ($price->status?->value ?? 'active') === 'active' ? 'success' : 'secondary' }} badge-lg">
                                    {{ ucfirst($price->status?->value ?? 'active') }}
                                </span>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.hosting-plan-prices.update', $price->uuid) }}" id="price-form">
                            @csrf
                            @method('PATCH')

                            <div class="card-body">
                                <h5 class="mb-3">
                                    <i class="fas fa-info-circle mr-2 text-primary"></i>Basic Information
                                </h5>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="hosting_category_id">
                                                <i class="fas fa-folder mr-1"></i>Hosting Category
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select name="hosting_category_id"
                                                    id="hosting_category_id"
                                                    class="form-control @error('hosting_category_id') is-invalid @enderror"
                                                    required>
                                                <option value="">Select a category</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ old('hosting_category_id', $price->plan?->category_id) == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('hosting_category_id')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="hosting_plan_id">
                                                <i class="fas fa-server mr-1"></i>Hosting Plan
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select name="hosting_plan_id"
                                                    id="hosting_plan_id"
                                                    class="form-control @error('hosting_plan_id') is-invalid @enderror"
                                                    required>
                                                <option value="">Select a plan</option>
                                                @foreach($plans as $plan)
                                                    <option value="{{ $plan->id }}"
                                                            data-category="{{ $plan->category_id }}"
                                                            {{ old('hosting_plan_id', $price->hosting_plan_id) == $plan->id ? 'selected' : '' }}>
                                                        {{ $plan->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('hosting_plan_id')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="billing_cycle">
                                                <i class="fas fa-calendar-alt mr-1"></i>Billing Cycle
                                                <span class="text-danger">*</span>
                                            </label>
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
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="status">
                                                <i class="fas fa-toggle-on mr-1"></i>Status
                                            </label>
                                            <select name="status"
                                                    id="status"
                                                    class="form-control @error('status') is-invalid @enderror">
                                                <option value="active" {{ old('status', $price->status?->value ?? 'active') === 'active' ? 'selected' : '' }}>
                                                    Active
                                                </option>
                                                <option value="inactive" {{ old('status', $price->status?->value ?? 'active') === 'inactive' ? 'selected' : '' }}>
                                                    Inactive
                                                </option>
                                            </select>
                                            @error('status')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <h5 class="mb-3">
                                    <i class="fas fa-dollar-sign mr-2 text-success"></i>Pricing Information
                                </h5>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="regular_price">
                                                <i class="fas fa-tag mr-1"></i>Regular Price
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">USD</span>
                                                </div>
                                                <input type="number"
                                                       name="regular_price"
                                                       id="regular_price"
                                                       class="form-control @error('regular_price') is-invalid @enderror"
                                                       value="{{ old('regular_price', $price->regular_price) }}"
                                                       min="0"
                                                       step="1"
                                                       required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">cents</span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">
                                                Current: {{ $price->getFormattedPrice('regular_price', 'USD') }}
                                            </small>
                                            @error('regular_price')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="renewal_price">
                                                <i class="fas fa-sync-alt mr-1"></i>Renewal Price
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">USD</span>
                                                </div>
                                                <input type="number"
                                                       name="renewal_price"
                                                       id="renewal_price"
                                                       class="form-control @error('renewal_price') is-invalid @enderror"
                                                       value="{{ old('renewal_price', $price->renewal_price) }}"
                                                       min="0"
                                                       step="1"
                                                       required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text">cents</span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">
                                                Current: {{ $price->getFormattedPrice('renewal_price', 'USD') }}
                                            </small>
                                            @error('renewal_price')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <h5 class="mb-3">
                                    <i class="fas fa-comment-alt mr-2 text-warning"></i>Change Reason
                                </h5>

                                <div class="form-group">
                                    <label for="reason">
                                        <i class="fas fa-comment-alt mr-1"></i>Reason for Price Change
                                        <span class="text-danger" id="reason-required-indicator" style="display: none;">*</span>
                                    </label>
                                    <textarea name="reason"
                                              id="reason"
                                              rows="4"
                                              class="form-control @error('reason') is-invalid @enderror"
                                              placeholder="Please provide a reason for changing the price...">{{ old('reason') }}</textarea>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Required when any price field is changed. This reason will be recorded in the price change history.
                                    </small>
                                    @error('reason')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.hosting-plan-prices.index') }}" class="btn btn-default">
                                        <i class="fas fa-arrow-left mr-1"></i>Back to List
                                    </a>
                                    <div>
                                        <button type="reset" class="btn btn-secondary mr-2">
                                            <i class="fas fa-redo mr-1"></i>Reset
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save mr-1"></i>Update Price
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-2"></i>Quick Info
                            </h3>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-5"><i class="fas fa-server mr-1"></i>Plan:</dt>
                                <dd class="col-sm-7"><strong>{{ $price->plan?->name ?? 'N/A' }}</strong></dd>

                                <dt class="col-sm-5"><i class="fas fa-folder mr-1"></i>Category:</dt>
                                <dd class="col-sm-7">{{ $price->plan?->category?->name ?? 'N/A' }}</dd>

                                <dt class="col-sm-5"><i class="fas fa-calendar-alt mr-1"></i>Billing Cycle:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge badge-info">{{ ucfirst(str_replace('-', ' ', $price->billing_cycle)) }}</span>
                                </dd>

                                <dt class="col-sm-5"><i class="fas fa-toggle-on mr-1"></i>Status:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge badge-{{ ($price->status?->value ?? 'active') === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($price->status?->value ?? 'active') }}
                                    </span>
                                </dd>

                                <dt class="col-sm-5"><i class="fas fa-calendar mr-1"></i>Created:</dt>
                                <dd class="col-sm-7">{{ $price->created_at?->format('M d, Y') ?? 'N/A' }}</dd>

                                <dt class="col-sm-5"><i class="fas fa-edit mr-1"></i>Updated:</dt>
                                <dd class="col-sm-7">{{ $price->updated_at?->format('M d, Y') ?? 'N/A' }}</dd>
                            </dl>
                        </div>
                    </div>

                    <div class="card card-warning card-outline mt-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Important Notes
                            </h3>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0 pl-3">
                                <li class="mb-2">All prices are stored in <strong>cents (USD)</strong></li>
                                <li class="mb-2">Price changes require a <strong>reason</strong></li>
                                <li class="mb-2">All changes are tracked in history</li>
                                <li>Prices are displayed in USD currency</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-2"></i>Price Change History
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-info badge-lg">{{ $histories->count() }} {{ Str::plural('record', $histories->count()) }}</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            @if($histories->isEmpty())
                                <div class="alert alert-info m-3 mb-0">
                                    <i class="fas fa-info-circle mr-2"></i>No price change history available.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead class="thead-light">
                                        <tr>
                                            <th style="width: 15%">Date & Time</th>
                                            <th style="width: 15%">Changed By</th>
                                            <th style="width: 35%">Price Changes</th>
                                            <th style="width: 20%">Reason</th>
                                            <th style="width: 15%">IP Address</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($histories as $history)
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong>{{ $history->created_at->format('M d, Y') }}</strong>
                                                        <div class="text-muted small">{{ $history->created_at->format('H:i:s') }}</div>
                                                        <div class="text-muted" style="font-size: 0.7rem;">
                                                            <i class="fas fa-clock mr-1"></i>{{ $history->created_at->diffForHumans() }}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <i class="fas fa-user mr-1 text-muted"></i>
                                                    <strong>{{ $history->changedBy?->name ?? 'System' }}</strong>
                                                </td>
                                                <td>
                                                    @php
                                                        $oldValues = $history->old_values ?? [];
                                                        $changes = $history->changes ?? [];
                                                    @endphp
                                                    <div class="small">
                                                        @if(isset($changes['regular_price']))
                                                            <div class="mb-1">
                                                                <span class="badge badge-light">Regular:</span>
                                                                <span class="text-muted">
                                                                    {{ \Cknow\Money\Money::USD($oldValues['regular_price'] ?? 0)->format() }}
                                                                </span>
                                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                <span class="text-success font-weight-bold">
                                                                    {{ \Cknow\Money\Money::USD($changes['regular_price'])->format() }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if(isset($changes['renewal_price']))
                                                            <div class="mb-1">
                                                                <span class="badge badge-light">Renewal:</span>
                                                                <span class="text-muted">
                                                                    {{ \Cknow\Money\Money::USD($oldValues['renewal_price'] ?? 0)->format() }}
                                                                </span>
                                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                <span class="text-success font-weight-bold">
                                                                    {{ \Cknow\Money\Money::USD($changes['renewal_price'])->format() }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($history->reason)
                                                        <div class="text-wrap" style="max-width: 200px;">
                                                            {{ Str::limit($history->reason, 80) }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted"><i>No reason provided</i></span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="text-muted small">
                                                        <i class="fas fa-network-wired mr-1"></i>{{ $history->ip_address ?? '—' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('scripts')
        @include('admin.hosting-plan-prices.partials.dependent-plan-script')
    @endsection

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const priceFields = ['regular_price', 'renewal_price'];
            const reasonField = document.getElementById('reason');
            const reasonRequiredIndicator = document.getElementById('reason-required-indicator');
            const originalValues = {};

            // Store original values
            priceFields.forEach(function(field) {
                const fieldElement = document.getElementById(field);
                if (fieldElement) {
                    originalValues[field] = fieldElement.value;
                }
            });

            function checkPriceChanges() {
                let hasChange = false;
                priceFields.forEach(function(field) {
                    const fieldElement = document.getElementById(field);
                    if (fieldElement && fieldElement.value !== originalValues[field]) {
                        hasChange = true;
                    }
                });

                if (hasChange) {
                    reasonField.setAttribute('required', 'required');
                    reasonRequiredIndicator.style.display = 'inline';
                    reasonField.classList.add('border-warning');
                } else {
                    reasonField.removeAttribute('required');
                    reasonRequiredIndicator.style.display = 'none';
                    reasonField.classList.remove('border-warning');
                }
            }

            // Check on input change
            priceFields.forEach(function(field) {
                const fieldElement = document.getElementById(field);
                if (fieldElement) {
                    fieldElement.addEventListener('input', checkPriceChanges);
                    fieldElement.addEventListener('change', checkPriceChanges);
                }
            });

            // Initial check
            checkPriceChanges();
        });
    </script>
    @endpush
</x-admin-layout>
