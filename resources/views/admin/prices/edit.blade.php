@php
use App\Enums\DomainType;
use Illuminate\Support\Str;
@endphp
<x-admin-layout>
    @section('page-title')
        Edit Domain Price
    @endsection

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-edit mr-2"></i>Edit Domain Price
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.prices.index') }}">Domain Prices</a></li>
                        <li class="breadcrumb-item active">{{ $price->tld }}</li>
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
                                <i class="fas fa-tag mr-2"></i>Domain Price Information
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-{{ $price->status === 'active' ? 'success' : 'secondary' }} badge-lg">
                                    {{ ucfirst($price->status) }}
                                </span>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.prices.update', $price->uuid) }}" id="price-form">
                            @csrf
                            @method('PATCH')

                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tld">
                                                <i class="fas fa-globe mr-1"></i>TLD
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">.</span>
                                                </div>
                                                <input type="text"
                                                       name="tld"
                                                       id="tld"
                                                       class="form-control @error('tld') is-invalid @enderror"
                                                       value="{{ old('tld', $price->tld) }}"
                                                       placeholder="com"
                                                       required>
                                            </div>
                                            @error('tld')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="type">
                                                <i class="fas fa-map-marker-alt mr-1"></i>Type
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select name="type"
                                                    id="type"
                                                    class="form-control @error('type') is-invalid @enderror"
                                                    required>
                                                @foreach(DomainType::cases() as $type)
                                                    <option
                                                        value="{{ $type->value }}" {{ old('type', $price->type?->value) === $type->value ? 'selected' : '' }}>
                                                        {{ $type->label() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('type')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="status">
                                                <i class="fas fa-toggle-on mr-1"></i>Status
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select name="status"
                                                    id="status"
                                                    class="form-control @error('status') is-invalid @enderror">
                                                <option
                                                    value="active" {{ old('status', $price->status) === 'active' ? 'selected' : '' }}>
                                                    Active
                                                </option>
                                                <option
                                                    value="inactive" {{ old('status', $price->status) === 'inactive' ? 'selected' : '' }}>
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
                                    <i class="fas fa-dollar-sign mr-2 text-success"></i>Pricing (in cents)
                                </h5>

                                <div class="row">
                                    <div class="col-md-6 col-lg-3">
                                        <div class="form-group">
                                            <label for="register_price">
                                                <i class="fas fa-plus-circle mr-1 text-primary"></i>Register Price
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        {{ $price->type === \App\Enums\DomainType::Local ? 'RWF' : 'USD' }}
                                                    </span>
                                                </div>
                                                <input type="number"
                                                       name="register_price"
                                                       id="register_price"
                                                       class="form-control @error('register_price') is-invalid @enderror"
                                                       value="{{ old('register_price', $price->register_price) }}"
                                                       min="0"
                                                       step="1"
                                                       required>
                                            </div>
                                            <small class="form-text text-muted">
                                                Current: {{ $price->formatRegistrationPrice() }}
                                            </small>
                                            @error('register_price')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-lg-3">
                                        <div class="form-group">
                                            <label for="renewal_price">
                                                <i class="fas fa-sync-alt mr-1 text-info"></i>Renewal Price
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        {{ $price->type === \App\Enums\DomainType::Local ? 'RWF' : 'USD' }}
                                                    </span>
                                                </div>
                                                <input type="number"
                                                       name="renewal_price"
                                                       id="renewal_price"
                                                       class="form-control @error('renewal_price') is-invalid @enderror"
                                                       value="{{ old('renewal_price', $price->renewal_price) }}"
                                                       min="0"
                                                       step="1"
                                                       required>
                                            </div>
                                            <small class="form-text text-muted">
                                                Current: {{ $price->formatRenewalPrice() }}
                                            </small>
                                            @error('renewal_price')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-lg-3">
                                        <div class="form-group">
                                            <label for="transfer_price">
                                                <i class="fas fa-exchange-alt mr-1 text-warning"></i>Transfer Price
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        {{ $price->type === \App\Enums\DomainType::Local ? 'RWF' : 'USD' }}
                                                    </span>
                                                </div>
                                                <input type="number"
                                                       name="transfer_price"
                                                       id="transfer_price"
                                                       class="form-control @error('transfer_price') is-invalid @enderror"
                                                       value="{{ old('transfer_price', $price->transfer_price) }}"
                                                       min="0"
                                                       step="1"
                                                       required>
                                            </div>
                                            <small class="form-text text-muted">
                                                Current: {{ $price->formatTransferPrice() }}
                                            </small>
                                            @error('transfer_price')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-lg-3">
                                        <div class="form-group">
                                            <label for="redemption_price">
                                                <i class="fas fa-undo mr-1 text-danger"></i>Redemption Price
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">
                                                        {{ $price->type === \App\Enums\DomainType::Local ? 'RWF' : 'USD' }}
                                                    </span>
                                                </div>
                                                <input type="number"
                                                       name="redemption_price"
                                                       id="redemption_price"
                                                       class="form-control @error('redemption_price') is-invalid @enderror"
                                                       value="{{ old('redemption_price', $price->redemption_price) }}"
                                                       min="0"
                                                       step="1">
                                            </div>
                                            <small class="form-text text-muted">
                                                @if($price->redemption_price)
                                                    Current: {{ $price->formatRedemptionPrice() }}
                                                @else
                                                    Not set
                                                @endif
                                            </small>
                                            @error('redemption_price')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <h5 class="mb-3">
                                    <i class="fas fa-cog mr-2 text-secondary"></i>Additional Settings
                                </h5>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="min_years">
                                                <i class="fas fa-calendar-minus mr-1"></i>Min Years
                                            </label>
                                            <input type="number"
                                                   name="min_years"
                                                   id="min_years"
                                                   class="form-control @error('min_years') is-invalid @enderror"
                                                   value="{{ old('min_years', $price->min_years) }}"
                                                   min="1"
                                                   step="1">
                                            @error('min_years')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="max_years">
                                                <i class="fas fa-calendar-plus mr-1"></i>Max Years
                                            </label>
                                            <input type="number"
                                                   name="max_years"
                                                   id="max_years"
                                                   class="form-control @error('max_years') is-invalid @enderror"
                                                   value="{{ old('max_years', $price->max_years) }}"
                                                   min="1"
                                                   step="1">
                                            @error('max_years')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="description">
                                                <i class="fas fa-align-left mr-1"></i>Description
                                            </label>
                                            <input type="text"
                                                   name="description"
                                                   id="description"
                                                   class="form-control @error('description') is-invalid @enderror"
                                                   value="{{ old('description', $price->description) }}"
                                                   placeholder="Optional description">
                                            @error('description')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

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
                                    <a href="{{ route('admin.prices.index') }}" class="btn btn-default">
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
                                <dt class="col-sm-5"><i class="fas fa-globe mr-1"></i>TLD:</dt>
                                <dd class="col-sm-7"><strong>.{{ $price->tld }}</strong></dd>

                                <dt class="col-sm-5"><i class="fas fa-map-marker-alt mr-1"></i>Type:</dt>
                                <dd class="col-sm-7">
                                    @if(isset($price->type) && method_exists($price->type, 'label'))
                                        <span class="badge {{ $price->type->color() }}">{{ $price->type->label() }}</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst((string) $price->type) }}</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-5"><i class="fas fa-toggle-on mr-1"></i>Status:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge badge-{{ $price->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($price->status) }}
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
                                <li class="mb-2">All prices are stored in <strong>cents</strong></li>
                                <li class="mb-2">Price changes require a <strong>reason</strong></li>
                                <li class="mb-2">All changes are tracked in history</li>
                                <li>Currency depends on domain type</li>
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
                                <span class="badge badge-info">{{ $histories->count() }} {{ Str::plural('record', $histories->count()) }}</span>
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
                                                        $isRWF = $price->type === \App\Enums\DomainType::Local;
                                                        $oldValues = $history->old_values ?? [];
                                                        $changes = $history->changes ?? [];
                                                    @endphp
                                                    <div class="small">
                                                        @if(isset($changes['register_price']))
                                                            <div class="mb-1">
                                                                <span class="badge badge-light">Register:</span>
                                                                <span class="text-muted">
                                                                    {{ $isRWF ? \Cknow\Money\Money::RWF($oldValues['register_price'] ?? 0)->format() : \Cknow\Money\Money::USD($oldValues['register_price'] ?? 0)->format() }}
                                                                </span>
                                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                <span class="text-success font-weight-bold">
                                                                    {{ $isRWF ? \Cknow\Money\Money::RWF($changes['register_price'])->format() : \Cknow\Money\Money::USD($changes['register_price'])->format() }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if(isset($changes['renewal_price']))
                                                            <div class="mb-1">
                                                                <span class="badge badge-light">Renewal:</span>
                                                                <span class="text-muted">
                                                                    {{ $isRWF ? \Cknow\Money\Money::RWF($oldValues['renewal_price'] ?? 0)->format() : \Cknow\Money\Money::USD($oldValues['renewal_price'] ?? 0)->format() }}
                                                                </span>
                                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                <span class="text-success font-weight-bold">
                                                                    {{ $isRWF ? \Cknow\Money\Money::RWF($changes['renewal_price'])->format() : \Cknow\Money\Money::USD($changes['renewal_price'])->format() }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if(isset($changes['transfer_price']))
                                                            <div class="mb-1">
                                                                <span class="badge badge-light">Transfer:</span>
                                                                <span class="text-muted">
                                                                    {{ $isRWF ? \Cknow\Money\Money::RWF($oldValues['transfer_price'] ?? 0)->format() : \Cknow\Money\Money::USD($oldValues['transfer_price'] ?? 0)->format() }}
                                                                </span>
                                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                <span class="text-success font-weight-bold">
                                                                    {{ $isRWF ? \Cknow\Money\Money::RWF($changes['transfer_price'])->format() : \Cknow\Money\Money::USD($changes['transfer_price'])->format() }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if(isset($changes['redemption_price']))
                                                            <div class="mb-1">
                                                                <span class="badge badge-light">Redemption:</span>
                                                                <span class="text-muted">
                                                                    @if($oldValues['redemption_price'] !== null)
                                                                        {{ $isRWF ? \Cknow\Money\Money::RWF($oldValues['redemption_price'])->format() : \Cknow\Money\Money::USD($oldValues['redemption_price'])->format() }}
                                                                    @else
                                                                        —
                                                                    @endif
                                                                </span>
                                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                <span class="text-success font-weight-bold">
                                                                    @if($changes['redemption_price'] !== null)
                                                                        {{ $isRWF ? \Cknow\Money\Money::RWF($changes['redemption_price'])->format() : \Cknow\Money\Money::USD($changes['redemption_price'])->format() }}
                                                                    @else
                                                                        —
                                                                    @endif
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

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const priceFields = ['register_price', 'renewal_price', 'transfer_price', 'redemption_price'];
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
