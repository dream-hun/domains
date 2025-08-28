<x-admin-layout>
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Transfer Domain</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.domains.index') }}">Domains</a></li>
                        <li class="breadcrumb-item active">Transfer</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-exchange-alt mr-2"></i>
                                Transfer Domain: {{ $domain->name }}
                            </h3>
                        </div>
                        
                        <form action="{{ route('admin.domains.transfer.store', $domain->id) }}" method="POST">
                            @csrf
                            <div class="card-body">
                                @if($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <!-- Domain Information -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card card-outline card-info">
                                            <div class="card-header">
                                                <h3 class="card-title">Domain Information</h3>
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-sm">
                                                    <tr>
                                                        <th style="width: 40%">Domain:</th>
                                                        <td>{{ $domain->name }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Current Status:</th>
                                                        <td>
                                                            <span class="badge badge-{{ $domain->status === 'active' ? 'success' : 'warning' }}">
                                                                {{ ucfirst($domain->status) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Current Provider:</th>
                                                        <td>{{ $domain->provider ?? 'Not set' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Expires:</th>
                                                        <td>{{ $domain->expires_at->format('M d, Y') }}</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Transfer Information -->
                                <div class="form-group">
                                    <label for="auth_code">Authorization Code (EPP Code) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('auth_code') is-invalid @enderror" 
                                           id="auth_code" name="auth_code" value="{{ old('auth_code') }}" 
                                           placeholder="Enter the authorization code from your current registrar" required>
                                    <small class="form-text text-muted">
                                        This is the transfer authorization code (also called EPP code or Auth code) 
                                        provided by your current domain registrar.
                                    </small>
                                    @error('auth_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Contact Information -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="registrant_contact_id">Registrant Contact <span class="text-danger">*</span></label>
                                            <select class="form-control @error('registrant_contact_id') is-invalid @enderror" 
                                                    id="registrant_contact_id" name="registrant_contact_id" required>
                                                <option value="">Select Registrant Contact</option>
                                                @foreach($contacts as $contact)
                                                    <option value="{{ $contact->id }}" 
                                                            {{ old('registrant_contact_id') == $contact->id ? 'selected' : '' }}>
                                                        {{ $contact->first_name }} {{ $contact->last_name }} ({{ $contact->email }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('registrant_contact_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="admin_contact_id">Admin Contact</label>
                                            <select class="form-control @error('admin_contact_id') is-invalid @enderror" 
                                                    id="admin_contact_id" name="admin_contact_id">
                                                <option value="">Select Admin Contact (Optional)</option>
                                                @foreach($contacts as $contact)
                                                    <option value="{{ $contact->id }}" 
                                                            {{ old('admin_contact_id') == $contact->id ? 'selected' : '' }}>
                                                        {{ $contact->first_name }} {{ $contact->last_name }} ({{ $contact->email }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('admin_contact_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tech_contact_id">Technical Contact</label>
                                            <select class="form-control @error('tech_contact_id') is-invalid @enderror" 
                                                    id="tech_contact_id" name="tech_contact_id">
                                                <option value="">Select Technical Contact (Optional)</option>
                                                @foreach($contacts as $contact)
                                                    <option value="{{ $contact->id }}" 
                                                            {{ old('tech_contact_id') == $contact->id ? 'selected' : '' }}>
                                                        {{ $contact->first_name }} {{ $contact->last_name }} ({{ $contact->email }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('tech_contact_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="billing_contact_id">Billing Contact</label>
                                            <select class="form-control @error('billing_contact_id') is-invalid @enderror" 
                                                    id="billing_contact_id" name="billing_contact_id">
                                                <option value="">Select Billing Contact (Optional)</option>
                                                @foreach($contacts as $contact)
                                                    <option value="{{ $contact->id }}" 
                                                            {{ old('billing_contact_id') == $contact->id ? 'selected' : '' }}>
                                                        {{ $contact->first_name }} {{ $contact->last_name }} ({{ $contact->email }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('billing_contact_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Nameservers (Optional) -->
                                <div class="form-group">
                                    <label>Nameservers (Optional)</label>
                                    <small class="form-text text-muted mb-2">
                                        You can specify custom nameservers for this domain. Leave empty to use default nameservers.
                                    </small>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="text" class="form-control @error('nameservers.0') is-invalid @enderror" 
                                                   name="nameservers[]" value="{{ old('nameservers.0') }}" 
                                                   placeholder="ns1.example.com">
                                            @error('nameservers.0')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control @error('nameservers.1') is-invalid @enderror" 
                                                   name="nameservers[]" value="{{ old('nameservers.1') }}" 
                                                   placeholder="ns2.example.com">
                                            @error('nameservers.1')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <input type="text" class="form-control @error('nameservers.2') is-invalid @enderror" 
                                                   name="nameservers[]" value="{{ old('nameservers.2') }}" 
                                                   placeholder="ns3.example.com (optional)">
                                            @error('nameservers.2')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control @error('nameservers.3') is-invalid @enderror" 
                                                   name="nameservers[]" value="{{ old('nameservers.3') }}" 
                                                   placeholder="ns4.example.com (optional)">
                                            @error('nameservers.3')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Warning Notice -->
                                <div class="alert alert-warning">
                                    <h5><i class="icon fas fa-exclamation-triangle"></i> Important!</h5>
                                    <ul class="mb-0">
                                        <li>Domain transfer can take 5-7 days to complete.</li>
                                        <li>The domain must be unlocked at your current registrar.</li>
                                        <li>The domain must be at least 60 days old.</li>
                                        <li>You will receive confirmation emails during the transfer process.</li>
                                        <li>The transfer will add 1 year to your domain's expiration date.</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-exchange-alt"></i> Initiate Transfer
                                </button>
                                <a href="{{ route('admin.domains.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-admin-layout>
