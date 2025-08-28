<x-admin-layout>
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Renew Domain</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.domains.index') }}">Domains</a></li>
                        <li class="breadcrumb-item active">Renew</li>
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
                                <i class="fas fa-redo mr-2"></i>
                                Renew Domain: {{ $domain->name }}
                            </h3>
                        </div>

                        <form action="{{ route('admin.domains.renew.store', $domain->uuid) }}" method="POST">
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
                                                <h3 class="card-title">Current Domain Information</h3>
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
                                                        <th>Current Expiry:</th>
                                                        <td>
                                                            {{ $domain->expires_at->format('M d, Y') }}
                                                            @if($domain->expires_at->isPast())
                                                                <span class="badge badge-danger ml-2">Expired</span>
                                                            @elseif($domain->expires_at->diffInDays() <= 30)
                                                                <span class="badge badge-warning ml-2">Expires Soon</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @if($domain->last_renewed_at)
                                                    <tr>
                                                        <th>Last Renewed:</th>
                                                        <td>{{ $domain->last_renewed_at->format('M d, Y') }}</td>
                                                    </tr>
                                                    @endif
                                                    <tr>
                                                        <th>Provider:</th>
                                                        <td>{{ ucfirst($domain->provider ?? 'Not set') }}</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card card-outline card-success">
                                            <div class="card-header">
                                                <h3 class="card-title">Renewal Options</h3>
                                            </div>
                                            <div class="card-body">
                                                <!-- Renewal Period -->
                                                <div class="form-group">
                                                    <label for="years">Renewal Period <span class="text-danger">*</span></label>
                                                    <select class="form-control @error('years') is-invalid @enderror"
                                                            id="years" name="years" required>
                                                        <option value="">Select renewal period</option>
                                                        @for($i = 1; $i <= 10; $i++)
                                                            <option value="{{ $i }}"
                                                                    {{ old('years', 1) == $i ? 'selected' : '' }}>
                                                                {{ $i }} Year{{ $i > 1 ? 's' : '' }}
                                                                @if($pricing && isset($pricing[$i]))
                                                                    - ${{ number_format($pricing[$i], 2) }}
                                                                @endif
                                                            </option>
                                                        @endfor
                                                    </select>
                                                    @error('years')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <!-- New Expiry Date Preview -->
                                                <div class="form-group">
                                                    <label>New Expiry Date</label>
                                                    <div class="p-2 bg-light rounded">
                                                        <span id="new-expiry-date">
                                                            {{ $domain->expires_at->addYear()->format('M d, Y') }}
                                                        </span>
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        The new expiration date after renewal
                                                    </small>
                                                </div>

                                                @if($pricing)
                                                <!-- Pricing Information -->
                                                <div class="form-group">
                                                    <label>Renewal Cost</label>
                                                    <div class="p-2 bg-light rounded">
                                                        <span id="renewal-cost" class="h5 text-success">
                                                            ${{ number_format($pricing[1] ?? 0, 2) }}
                                                        </span>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Information Alert -->
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> Renewal Information</h5>
                                    <ul class="mb-0">
                                        <li>Domain renewal will extend your registration period.</li>
                                        <li>The renewal will be processed immediately upon confirmation.</li>
                                        <li>You will receive a confirmation email once the renewal is complete.</li>
                                        <li>For .rw domains, renewal is processed through the local registry.</li>
                                        <li>For international domains, renewal is processed through Namecheap.</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-redo"></i> Renew Domain
                                </button>
                                <a href="{{ route('admin.domains.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Help Card -->
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-question-circle mr-2"></i>
                                Need Help?
                            </h3>
                        </div>
                        <div class="card-body">
                            <h6>Renewal Guidelines:</h6>
                            <ul class="text-sm">
                                <li>Renew before expiry to avoid service interruption</li>
                                <li>You can renew up to 10 years in advance</li>
                                <li>Renewal pricing may vary by TLD</li>
                                <li>Auto-renewal can be enabled after manual renewal</li>
                            </ul>

                            <h6 class="mt-3">Payment Methods:</h6>
                            <ul class="text-sm">
                                <li>Credit/Debit Card</li>
                                <li>Bank Transfer</li>
                                <li>Account Balance</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const yearsSelect = document.getElementById('years');
            const newExpirySpan = document.getElementById('new-expiry-date');
            const renewalCostSpan = document.getElementById('renewal-cost');
            const currentExpiry = new Date('{{ $domain->expires_at->toISOString() }}');

            const pricing = @json($pricing ?? []);

            yearsSelect.addEventListener('change', function() {
                const years = parseInt(this.value) || 1;

                // Calculate new expiry date
                const newExpiry = new Date(currentExpiry);
                newExpiry.setFullYear(newExpiry.getFullYear() + years);

                // Format and display new expiry date
                const options = { year: 'numeric', month: 'short', day: 'numeric' };
                newExpirySpan.textContent = newExpiry.toLocaleDateString('en-US', options);

                // Update renewal cost if pricing is available
                if (renewalCostSpan && pricing[years]) {
                    renewalCostSpan.textContent = '$' + parseFloat(pricing[years]).toFixed(2);
                }
            });
        });
    </script>
</x-admin-layout>
