<x-admin-layout>
    @section('page-title')
        Domain Management
    @endsection
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Manage Domains</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.domains.index') }}">Domains</a></li>
                        <li class="breadcrumb-item active">{{$domain->name}}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <table class="table table-bordered-0 table-sm">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Auto Renew</th>
                                <th>Expire At</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td style="width: 25%;">{{ $domain->name }}</td>
                                <td>
                                    <button
                                        class="btn btn-sm btn-{{ $domain->status === 'active' ? 'success' : 'warning' }}">
                                        <i class="bi bi-check-circle"></i> {{ ucfirst($domain->status) }}
                                    </button>
                                </td>
                                <td style="width: 25%;">
                                    @if($domain->auto_renew)
                                        <button class="btn btn-sm btn-success">Enabled</button>
                                    @else
                                        <button class="btn btn-sm btn-secondary">Disabled</button>
                                    @endif
                                </td>
                                <td style="width: 25%;">{{ $domain->expires_at->format('M d, Y') }}</td>

                            </tr>
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-12 row">
                <div class="col-md-6">
                    <form class="card" action="{{ route('admin.domains.nameservers.update', $domain->uuid) }}"
                          method="POST"
                          id="nameservers-form">
                        @csrf
                        @method('PUT')
                        <div class="card-header"><h6>Nameserver management</h6></div>
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
                            <!-- New Nameservers -->
                            <div class="form-group">
                                <label>New Nameservers <span class="text-danger">*</span></label>
                                <small class="form-text text-muted mb-3">
                                    Enter 2-4 nameserver hostnames. These will replace the current nameservers.
                                </small>

                                <div id="nameservers-container">
                                    <!-- Nameserver 1 -->
                                    <div class="nameserver-row mb-3">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">NS1</span>
                                            </div>
                                            <input type="text"
                                                   class="form-control @error('nameservers.0') is-invalid @enderror"
                                                   name="nameservers[]"
                                                   value="{{ old('nameservers.0', $domain->nameservers[0]->name ?? '') }}"
                                                   placeholder="ns1.example.com"
                                                   required>
                                            @error('nameservers.0')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Nameserver 2 -->
                                    <div class="nameserver-row mb-3">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">NS2</span>
                                            </div>
                                            <input type="text"
                                                   class="form-control @error('nameservers.1') is-invalid @enderror"
                                                   name="nameservers[]"
                                                   value="{{ old('nameservers.1', $domain->nameservers[1]->name ?? '') }}"
                                                   placeholder="ns2.example.com"
                                                   required>
                                            @error('nameservers.1')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-floppy"></i> Update Nameservers
                            </button>
                            <a href="{{ route('admin.domains.index') }}" class="btn btn-secondary float-right">
                                <i class="bi bi-dash-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6>Change Ownership</h6>
                        </div>
                        <div class="card-body">
                            <p>
                                You can transfer your domain to another Bluhub account by entering the recipient's email
                                address below. This will send an email to the recipient with instructions on how to
                                accept the domain transfer.
                            </p>
                            <form method="POST" action="{{ route('admin.domains.transfer.store',$domain->uuid) }}">
                                @csrf
                                <div class="form-group">
                                    <label for="auth_code">Enter Email Account <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                           id="email" name="email" value="{{ old('email') }}"
                                           placeholder="Enter email" required>

                                    @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </form>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('admin.domains.transfer.store',$domain->uuid) }}"
                               onclick="event.preventDefault(); this.closest('form').submit();" class="btn btn-primary">
                                <i class="bi bi-send-check"></i> Change ownership
                            </a>
                            <a href="{{ route('admin.domains.index') }}" class="btn btn-secondary float-right">
                                <i class="bi bi-dash-circle"></i> Cancel
                            </a>
                        </div>
                    </div>

                </div>
            </div>
            <div class="col-md-12 row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6>Domain Contact Information</h6>
                        </div>
                        <div class="card-body">
                            <p>
                                The contact information associated with your domain is used for administrative,
                                technical, and billing purposes. It is important to keep this information accurate and
                                up-to-date to ensure that you receive important notifications regarding your domain.
                            </p>
                            <a href="{{ route('admin.domains.info',$domain->uuid) }}" class="btn btn-info">
                                <i class="bi bi-info-circle"></i> View Contact Info
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6>Transfer Domain Out</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-sm">
                                From here, you can transfer your domain name out from BLUHUB to another registrar. To
                                transfer out, you will need to make sure that Domain Lock is turned OFF and get an Auth
                                Code. After you place the request here, we'll send your Auth Code to the registrant
                                email address specified for this domain. It may take up to 5 days for the transfer to be
                                completed.
                            </p>

                            <div class="row">
                                <div class="form-group col-6">
                                    <label for="lock">Domain Lock</label>
                                    <div>
                                        @if($domain->is_locked)
                                            <button class="btn btn-sm btn-success"><i class="bi bi-lock"></i> Locked</button>
                                        @else
                                            <button class="btn btn-sm btn-danger"><i class="bi bi-unlock"></i> Unlocked</button>
                                        @endif
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('admin.domains.lock', $domain->uuid) }}" class="col-6 float-right mt-4" style="display: inline;">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-group">
                                        @if($domain->is_locked)
                                            <button type="submit" class="btn btn-danger">
                                                <i class="bi bi-unlock"></i> Unlock Domain
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-lock"></i> Lock Domain
                                            </button>
                                        @endif
                                    </div>
                                </form>
                            </div>

                        </div>
                        <div class="card-footer">
                            <a href="{{--{{ route('admin.domains.authcode',$domain->uuid) }}--}}"
                               class="btn btn-primary">
                                <i class="bi bi-key"></i> Get Auth Code
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </section>

</x-admin-layout>
