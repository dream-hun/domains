<x-admin-layout>
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Update Nameservers</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.domains.index') }}">Domains</a></li>
                        <li class="breadcrumb-item active">Nameservers</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-server mr-2"></i>
                                Update Nameservers for: {{ $domain->name }}
                            </h3>
                        </div>

                        <form action="{{ route('admin.domains.nameservers.update', $domain->uuid) }}" method="POST" id="nameservers-form">
                            @csrf
                            @method('PUT')

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
                                                        <th>Status:</th>
                                                        <td>
                                                            <span class="badge badge-{{ $domain->status === 'active' ? 'success' : 'warning' }}">
                                                                {{ ucfirst($domain->status) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Provider:</th>
                                                        <td>{{ ucfirst($domain->provider ?? 'Not set') }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Expires:</th>
                                                        <td>{{ $domain->expires_at->format('M d, Y') }}</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Current Nameservers -->
                                    <div class="col-md-6">
                                        <div class="card card-outline card-secondary">
                                            <div class="card-header">
                                                <h3 class="card-title">Current Nameservers</h3>
                                            </div>
                                            <div class="card-body">
                                                @if($domain->nameservers->count() > 0)
                                                    <ul class="list-unstyled">
                                                        @foreach($domain->nameservers as $nameserver)
                                                            <li class="mb-1">
                                                                <i class="bi bi-server text-muted mr-2"></i>
                                                                {{ $nameserver->name }}
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <p class="text-muted mb-0">No nameservers configured</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

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

                                        <!-- Nameserver 3 -->
                                        <div class="nameserver-row mb-3">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">NS3</span>
                                                </div>
                                                <input type="text"
                                                       class="form-control @error('nameservers.2') is-invalid @enderror"
                                                       name="nameservers[]"
                                                       value="{{ old('nameservers.2', $domain->nameservers[2]->name ?? '') }}"
                                                       placeholder="ns3.example.com (optional)">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-danger remove-nameserver" type="button" title="Remove this nameserver">
                                                        <i class="bi bi-x-octagon-fill"></i>
                                                    </button>
                                                </div>
                                                @error('nameservers.2')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Nameserver 4 -->
                                        <div class="nameserver-row mb-3">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">NS4</span>
                                                </div>
                                                <input type="text"
                                                       class="form-control @error('nameservers.3') is-invalid @enderror"
                                                       name="nameservers[]"
                                                       value="{{ old('nameservers.3', $domain->nameservers[3]->name ?? '') }}"
                                                       placeholder="ns4.example.com (optional)">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-danger remove-nameserver" type="button" title="Remove this nameserver">
                                                        <i class="bi bi-x-octagon-fill"></i>
                                                    </button>
                                                </div>
                                                @error('nameservers.3')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Validation Information -->
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> Nameserver Requirements</h5>
                                    <ul class="mb-0">
                                        <li>At least 2 nameservers are required</li>
                                        <li>Maximum of 4 nameservers allowed</li>
                                        <li>Each nameserver must be a valid hostname (e.g., ns1.example.com)</li>
                                        <li>Changes may take 24-48 hours to propagate worldwide</li>
                                        <li>Ensure nameservers are properly configured before updating</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Nameservers
                                </button>
                                <a href="{{ route('admin.domains.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Common Nameservers -->
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-star mr-2"></i>
                                Common Nameservers
                            </h3>
                        </div>
                        <div class="card-body">
                            <h6>Cloudflare</h6>
                            <div class="nameserver-preset mb-3" data-ns1="ns1.cloudflare.com" data-ns2="ns2.cloudflare.com">
                                <code>ns1.cloudflare.com</code><br>
                                <code>ns2.cloudflare.com</code>
                                <button class="btn btn-sm btn-outline-primary mt-1 use-preset">Use These</button>
                            </div>

                            <h6>Google Cloud DNS</h6>
                            <div class="nameserver-preset mb-3" data-ns1="ns-cloud-d1.googledomains.com" data-ns2="ns-cloud-d2.googledomains.com">
                                <code>ns-cloud-d1.googledomains.com</code><br>
                                <code>ns-cloud-d2.googledomains.com</code>
                                <button class="btn btn-sm btn-outline-primary mt-1 use-preset">Use These</button>
                            </div>

                            <h6>AWS Route 53</h6>
                            <div class="nameserver-preset mb-3" data-ns1="ns-123.awsdns-12.com" data-ns2="ns-456.awsdns-12.org">
                                <code>ns-xxx.awsdns-xx.com</code><br>
                                <code>ns-xxx.awsdns-xx.org</code>
                                <small class="text-muted d-block">Replace with your actual Route 53 nameservers</small>
                            </div>
                        </div>
                    </div>

                    <!-- Help Card -->
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-question-circle mr-2"></i>
                                Need Help?
                            </h3>
                        </div>
                        <div class="card-body">
                            <h6>Before Updating:</h6>
                            <ul class="text-sm">
                                <li>Ensure nameservers are configured</li>
                                <li>Set up DNS records on new nameservers</li>
                                <li>Consider DNS propagation time</li>
                            </ul>

                            <h6 class="mt-3">After Updating:</h6>
                            <ul class="text-sm">
                                <li>Monitor DNS propagation</li>
                                <li>Verify website and email functionality</li>
                                <li>Update any CDN or monitoring services</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle preset nameserver buttons
            document.querySelectorAll('.use-preset').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const preset = this.closest('.nameserver-preset');
                    const ns1 = preset.dataset.ns1;
                    const ns2 = preset.dataset.ns2;

                    // Fill in the nameserver fields
                    const inputs = document.querySelectorAll('input[name="nameservers[]"]');
                    if (inputs[0]) inputs[0].value = ns1;
                    if (inputs[1]) inputs[1].value = ns2;
                    if (inputs[2]) inputs[2].value = '';
                    if (inputs[3]) inputs[3].value = '';
                });
            });

            // Handle remove nameserver buttons
            document.querySelectorAll('.remove-nameserver').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const input = this.closest('.input-group').querySelector('input');
                    input.value = '';
                    input.removeAttribute('required');
                });
            });

            // Form validation
            document.getElementById('nameservers-form').addEventListener('submit', function(e) {
                const inputs = document.querySelectorAll('input[name="nameservers[]"]');
                const filledInputs = Array.from(inputs).filter(input => input.value.trim() !== '');

                if (filledInputs.length < 2) {
                    e.preventDefault();
                    alert('At least 2 nameservers are required.');
                    return false;
                }
            });
        });
    </script>
</x-admin-layout>
