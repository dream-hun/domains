<x-admin-layout>
    @section('page-title')
        Edit Subscription
    @endsection

    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Subscription</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.subscriptions.index') }}">Subscriptions</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.subscriptions.show', $subscription) }}">Details</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-edit"></i> Edit Subscription #{{ $subscription->plan?->name }}
                            </h3>
                        </div>
                        <form action="{{ route('admin.subscriptions.update', $subscription) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="status">
                                                <i class="fas fa-tag"></i> Status <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control @error('status') is-invalid @enderror"
                                                    id="status"
                                                    name="status"
                                                    required>
                                                @foreach($statusOptions as $status)
                                                    <option value="{{ $status }}"
                                                            @selected(old('status', $subscription->status) === $status)>
                                                        {{ ucfirst($status) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('status')
                                                <span class="error invalid-feedback">{{ $message }}</span>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Current status: <strong class="text-primary">{{ ucfirst($subscription->status) }}</strong>
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="domain">
                                                <i class="fas fa-globe"></i> Linked Domain
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-link"></i></span>
                                                </div>
                                                <input type="text"
                                                       class="form-control @error('domain') is-invalid @enderror"
                                                       id="domain"
                                                       name="domain"
                                                       value="{{ old('domain', $subscription->domain) }}"
                                                       placeholder="example.com">
                                                @error('domain')
                                                    <span class="error invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <small class="form-text text-muted">
                                                Leave empty for VPS or standalone hosting
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="starts_at">
                                                <i class="fas fa-calendar-check"></i> Start Date <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group date" id="starts_at_wrapper" data-target-input="nearest">
                                                <input type="datetime-local"
                                                       class="form-control @error('starts_at') is-invalid @enderror"
                                                       id="starts_at"
                                                       name="starts_at"
                                                       value="{{ old('starts_at', $subscription->starts_at?->format('Y-m-d\TH:i')) }}"
                                                       required>
                                                <div class="input-group-append" data-target="#starts_at_wrapper" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                                @error('starts_at')
                                                    <span class="error invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="expires_at">
                                                <i class="fas fa-calendar-times"></i> Expiry Date <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group date" id="expires_at_wrapper" data-target-input="nearest">
                                                <input type="datetime-local"
                                                       class="form-control @error('expires_at') is-invalid @enderror"
                                                       id="expires_at"
                                                       name="expires_at"
                                                       value="{{ old('expires_at', $subscription->expires_at?->format('Y-m-d\TH:i')) }}"
                                                       required>
                                                <div class="input-group-append" data-target="#expires_at_wrapper" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                                @error('expires_at')
                                                    <span class="error invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="next_renewal_at">
                                                <i class="fas fa-calendar-alt"></i> Next Renewal Date
                                            </label>
                                            <div class="input-group date" id="next_renewal_at_wrapper" data-target-input="nearest">
                                                <input type="datetime-local"
                                                       class="form-control @error('next_renewal_at') is-invalid @enderror"
                                                       id="next_renewal_at"
                                                       name="next_renewal_at"
                                                       value="{{ old('next_renewal_at', $subscription->next_renewal_at?->format('Y-m-d\TH:i')) }}">
                                                <div class="input-group-append" data-target="#next_renewal_at_wrapper" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                                @error('next_renewal_at')
                                                    <span class="error invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <small class="form-text text-muted">
                                                Usually same as expiry date
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>
                                                <i class="fas fa-sync"></i> Auto Renewal Settings
                                            </label>
                                            <div class="custom-control custom-switch custom-switch-lg">
                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       id="auto_renew"
                                                       name="auto_renew"
                                                       value="1"
                                                       @checked(old('auto_renew', $subscription->auto_renew))>
                                                <label class="custom-control-label" for="auto_renew">
                                                    Enable Auto-Renewal
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">
                                                If enabled, subscription will automatically renew before expiration
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="card card-outline card-secondary">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-info-circle"></i> Read-Only Information
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p>
                                                    <strong><i class="fas fa-user"></i> Customer:</strong>
                                                    <span class="text-muted">{{ $subscription->user?->name ?? 'N/A' }}</span>
                                                </p>
                                                <p>
                                                    <strong><i class="fas fa-box"></i> Plan:</strong>
                                                    <span class="text-muted">{{ $subscription->plan?->name ?? 'N/A' }}</span>
                                                </p>
                                                <p>
                                                    <strong><i class="fas fa-sync-alt"></i> Billing Cycle:</strong>
                                                    <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $subscription->billing_cycle)) }}</span>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                @if($subscription->planPrice)
                                                    <p>
                                                        <strong><i class="fas fa-dollar-sign"></i> Regular Price:</strong>
                                                        <span class="text-success">{{ $subscription->planPrice->getFormattedPrice('regular_price') }}</span>
                                                    </p>
                                                    <p>
                                                        <strong><i class="fas fa-redo"></i> Renewal Price:</strong>
                                                        <span class="text-primary">{{ $subscription->planPrice->getFormattedPrice('renewal_price') }}</span>
                                                    </p>
                                                @endif
                                                @if($subscription->provider_resource_id)
                                                    <p>
                                                        <strong><i class="fas fa-key"></i> Provider Reference:</strong>
                                                        <code class="text-primary">{{ $subscription->provider_resource_id }}</code>
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.subscriptions.show', $subscription) }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Subscription
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-admin-layout>
