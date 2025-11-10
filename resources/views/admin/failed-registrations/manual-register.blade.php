<x-admin-layout>
    @section('page-title')
        Manual Domain Registration
    @endsection
    <section class="content-header">
        <div class="container-fluid col-md-12">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Manual Domain Registration</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.failed-registrations.index') }}">Failed Registrations</a></li>
                        <li class="breadcrumb-item active">Manual Register</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <a class="btn btn-secondary" href="{{ route('admin.failed-registrations.index') }}">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Register a Domain Manually</h3>
        </div>
        <form method="POST" action="{{ route('admin.failed-registrations.manual-register.store') }}">
            @csrf
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="domain_name">Domain Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('domain_name') is-invalid @enderror" id="domain_name" name="domain_name" value="{{ old('domain_name') }}" placeholder="example.com" required>
                            @error('domain_name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="user_id">Domain Owner <span class="text-danger">*</span></label>
                            <select class="form-control select2 @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                                <option value="">Select User</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="years">Registration Period (Years) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('years') is-invalid @enderror" id="years" name="years" value="{{ old('years', 1) }}" min="1" max="10" required>
                            @error('years')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>

                <h5>Contact Information</h5>
                <p class="text-muted">Select contacts for the domain registration. All contacts are required.</p>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="registrant_contact_id">Registrant Contact <span class="text-danger">*</span></label>
                            <select class="form-control select2 @error('registrant_contact_id') is-invalid @enderror" id="registrant_contact_id" name="registrant_contact_id" required>
                                <option value="">Select Contact</option>
                                @foreach ($contacts as $contact)
                                    <option value="{{ $contact->id }}" {{ old('registrant_contact_id') == $contact->id ? 'selected' : '' }}>
                                        {{ $contact->first_name }} {{ $contact->last_name }} ({{ $contact->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('registrant_contact_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="admin_contact_id">Admin Contact <span class="text-danger">*</span></label>
                            <select class="form-control select2 @error('admin_contact_id') is-invalid @enderror" id="admin_contact_id" name="admin_contact_id" required>
                                <option value="">Select Contact</option>
                                @foreach ($contacts as $contact)
                                    <option value="{{ $contact->id }}" {{ old('admin_contact_id') == $contact->id ? 'selected' : '' }}>
                                        {{ $contact->first_name }} {{ $contact->last_name }} ({{ $contact->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('admin_contact_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="technical_contact_id">Technical Contact <span class="text-danger">*</span></label>
                            <select class="form-control select2 @error('technical_contact_id') is-invalid @enderror" id="technical_contact_id" name="technical_contact_id" required>
                                <option value="">Select Contact</option>
                                @foreach ($contacts as $contact)
                                    <option value="{{ $contact->id }}" {{ old('technical_contact_id') == $contact->id ? 'selected' : '' }}>
                                        {{ $contact->first_name }} {{ $contact->last_name }} ({{ $contact->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('technical_contact_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="billing_contact_id">Billing Contact <span class="text-danger">*</span></label>
                            <select class="form-control select2 @error('billing_contact_id') is-invalid @enderror" id="billing_contact_id" name="billing_contact_id" required>
                                <option value="">Select Contact</option>
                                @foreach ($contacts as $contact)
                                    <option value="{{ $contact->id }}" {{ old('billing_contact_id') == $contact->id ? 'selected' : '' }}>
                                        {{ $contact->first_name }} {{ $contact->last_name }} ({{ $contact->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('billing_contact_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>

                <h5>Nameservers (Optional)</h5>
                <p class="text-muted">If not specified, default nameservers will be used.</p>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nameserver_1">Nameserver 1</label>
                            <input type="text" class="form-control @error('nameserver_1') is-invalid @enderror" id="nameserver_1" name="nameserver_1" value="{{ old('nameserver_1') }}" placeholder="ns1.example.com">
                            @error('nameserver_1')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nameserver_2">Nameserver 2</label>
                            <input type="text" class="form-control @error('nameserver_2') is-invalid @enderror" id="nameserver_2" name="nameserver_2" value="{{ old('nameserver_2') }}" placeholder="ns2.example.com">
                            @error('nameserver_2')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nameserver_3">Nameserver 3</label>
                            <input type="text" class="form-control @error('nameserver_3') is-invalid @enderror" id="nameserver_3" name="nameserver_3" value="{{ old('nameserver_3') }}" placeholder="ns3.example.com">
                            @error('nameserver_3')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nameserver_4">Nameserver 4</label>
                            <input type="text" class="form-control @error('nameserver_4') is-invalid @enderror" id="nameserver_4" name="nameserver_4" value="{{ old('nameserver_4') }}" placeholder="ns4.example.com">
                            @error('nameserver_4')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Register Domain
                </button>
                <a href="{{ route('admin.failed-registrations.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    @section('scripts')
        @parent
        <script>
            $(function() {
                $('.select2').select2({
                    theme: 'bootstrap4'
                });
            });
        </script>
    @endsection
</x-admin-layout>

