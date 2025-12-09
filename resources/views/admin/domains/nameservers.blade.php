<x-admin-layout>
    @section('page-title')
        Domain Management
    @endsection
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>
                        <i class="bi bi-globe"></i> Manage Domain
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.domains.index') }}">Domains</a></li>
                        <li class="breadcrumb-item active">{{ $domain->name }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <!-- Domain Overview Card -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary">
                            <h5 class="mb-0 text-white">
                                <i class="bi bi-info-circle"></i> Domain Overview
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <small class="text-muted d-block mb-1"><i class="bi bi-globe2"></i> Domain Name</small>
                                    <strong class="d-block">{{ $domain->name }}</strong>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted d-block mb-1">Status</small>
                                    <span class="badge badge-lg {{ str_replace('bg-', 'badge-', $domain->status->color()) }}">
                                        <i class="{{ $domain->status->icon() }}"></i> {{ $domain->status->label() }}
                                    </span>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted d-block mb-1">Auto Renew</small>
                                    @if ($domain->auto_renew)
                                        <span class="badge badge-lg badge-success">
                                            <i class="bi bi-check-circle"></i> Enabled
                                        </span>
                                    @else
                                        <span class="badge badge-lg badge-secondary">
                                            <i class="bi bi-x-circle"></i> Disabled
                                        </span>
                                    @endif
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted d-block mb-1">Expires On</small>
                                    <strong class="d-block">{{ $domain->expires_at->format('M d, Y') }}</strong>
                                    <small class="text-muted">{{ $domain->expires_at->diffForHumans() }}</small>
                                </div>
                                <div class="col-md-3 text-right">
                                    <form action="{{ route('admin.domain.fetchContacts', $domain->uuid) }}"
                                        method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-arrow-clockwise"></i> Fetch Contacts
                                        </button>
                                    </form>
                                    @can('ownership_assignment_access')
                                        <div class="d-inline-block ms-2">
                                            <a href="{{ route('admin.domains.assign', $domain->uuid) }}" class="btn btn-info btn-sm">
                                                <i class="bi bi-person"></i> Assign Owner
                                            </a>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nameservers and Contacts Section -->
            <div class="row">
                <!-- Nameservers Card -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-info">
                            <h5 class="mb-0 text-white">
                                <i class="bi bi-server"></i> Nameserver Management
                            </h5>
                        </div>
                        <form action="{{ route('admin.domains.nameservers.update', $domain->uuid) }}"
                            method="POST" id="nameservers-form">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                @if ($errors->any())
                                    <div class="alert alert-danger alert-dismissible fade show">
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        <h6><i class="bi bi-exclamation-triangle"></i> Validation Errors</h6>
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Enter 2-4 nameserver hostnames. These will replace the current nameservers.
                                </div>

                                <div id="nameservers-container">
                                    @for ($i = 0; $i < max(2, count($domain->nameservers ?? [])); $i++)
                                        <div class="nameserver-row mb-3" data-index="{{ $i }}">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text bg-info text-white">
                                                        <strong>NS{{ $i + 1 }}</strong>
                                                    </span>
                                                </div>
                                                <input type="text"
                                                    class="form-control @error('nameservers.' . $i) is-invalid @enderror"
                                                    name="nameservers[]"
                                                    value="{{ old('nameservers.' . $i, $domain->nameservers[$i]->name ?? '') }}"
                                                    placeholder="ns{{ $i + 1 }}.example.com"
                                                    {{ $i < 2 ? 'required' : '' }}>
                                                @if ($i >= 2)
                                                    <div class="input-group-append">
                                                        <button type="button" class="btn btn-danger btn-remove-ns">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                                @error('nameservers.' . $i)
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endfor
                                </div>

                                <button type="button" class="btn btn-outline-info btn-sm" id="add-nameserver">
                                    <i class="bi bi-plus-circle"></i> Add Nameserver
                                </button>
                                <small class="text-muted d-block mt-2">
                                    You can add up to 4 nameservers (minimum 2 required)
                                </small>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Nameservers
                                </button>
                                <a href="{{ route('admin.domains.index') }}" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Transfer Domain Card -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0 text-white">
                                <i class="bi bi-arrow-left-right"></i> Transfer Domain Out
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">
                                <i class="bi bi-info-circle"></i> Transfer your domain from BLUHUB to another registrar.
                                Ensure Domain Lock is OFF and obtain an Auth Code. The transfer may take up to 5 days to complete.
                            </p>

                            <div class="mb-3">
                                <label class="d-block mb-2">
                                    <small class="text-muted"><i class="bi bi-lock"></i> Domain Lock Status</small>
                                </label>
                                <div class="mb-3">
                                    @if ($domain->is_locked)
                                        <span class="badge badge-lg badge-success">
                                            <i class="bi bi-lock-fill"></i> Locked
                                        </span>
                                    @else
                                        <span class="badge badge-lg badge-danger">
                                            <i class="bi bi-unlock-fill"></i> Unlocked
                                        </span>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('admin.domains.lock', $domain->uuid) }}">
                                    @csrf
                                    @method('PUT')
                                    @if ($domain->is_locked)
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-unlock"></i> Unlock Domain
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-lock"></i> Lock Domain
                                        </button>
                                    @endif
                                </form>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="{{-- {{ route('admin.domains.authcode',$domain->uuid) }} --}}" class="btn btn-warning">
                                <i class="bi bi-key"></i> Get Auth Code
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="row">
                <div class="col-md-12 mb-3">
                    <h4><i class="bi bi-people"></i> Contact Information</h4>
                </div>
                @php
                    $contactTypes = [
                        'registrant' => [
                            'title' => 'Registrant Contact',
                            'icon' => 'bi-person-fill',
                            'color' => 'primary',
                        ],
                        'admin' => [
                            'title' => 'Admin Contact',
                            'icon' => 'bi-shield-fill',
                            'color' => 'success'
                        ],
                        'technical' => [
                            'title' => 'Technical Contact',
                            'icon' => 'bi-gear-fill',
                            'color' => 'info',
                        ],
                        'billing' => [
                            'title' => 'Billing Contact',
                            'icon' => 'bi-credit-card-fill',
                            'color' => 'warning',
                        ],
                    ];
                @endphp

                @foreach ($contactTypes as $type => $config)
                    @php
                        // Handle different contact type mappings
                        $contact =
                            $contactsByType[$type] ??
                            (($type === 'technical' ? $contactsByType['tech'] ?? null : null) ??
                                ($type === 'billing' ? $contactsByType['auxbilling'] ?? null : null));
                    @endphp
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-{{ $config['color'] }}">
                                <h5 class="mb-0 text-white">
                                    <i class="{{ $config['icon'] }}"></i> {{ $config['title'] }}
                                </h5>
                            </div>
                            <div class="card-body">
                                @if ($contact)
                                    <div class="contact-info">
                                        <div class="row mb-2">
                                            <div class="col-4"><strong><i class="bi bi-person"></i> Name:</strong></div>
                                            <div class="col-8">{{ $contact->full_name }}</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4"><strong><i class="bi bi-envelope"></i> Email:</strong></div>
                                            <div class="col-8">
                                                <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4"><strong><i class="bi bi-telephone"></i> Phone:</strong></div>
                                            <div class="col-8">{{ $contact->phone }}</div>
                                        </div>
                                        @if ($contact->organization)
                                            <div class="row mb-2">
                                                <div class="col-4"><strong><i class="bi bi-building"></i> Organization:</strong></div>
                                                <div class="col-8">{{ $contact->organization }}</div>
                                            </div>
                                        @endif
                                        <div class="row">
                                            <div class="col-4"><strong><i class="bi bi-geo-alt"></i> Address:</strong></div>
                                            <div class="col-8">
                                                <small class="text-muted">{{ $contact->full_address }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="bi bi-person-x" style="font-size: 3rem;"></i>
                                        <p class="mb-0 mt-2">No {{ strtolower($config['title']) }} assigned</p>
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer">
                                <a href="{{ route('admin.domains.contacts.edit', ['domain' => $domain->uuid, 'type' => $type]) }}"
                                    class="btn btn-{{ $config['color'] }} btn-sm">
                                    <i class="bi bi-pencil"></i> Edit {{ $config['title'] }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @push('styles')
    <style>
        .card {
            border: none;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .shadow-sm {
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075) !important;
        }
        .card:hover .shadow-sm {
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
        }
        .badge-lg {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }
        .contact-info .row {
            padding: 0.25rem 0;
            border-bottom: 1px solid #f4f4f4;
        }
        .contact-info .row:last-child {
            border-bottom: none;
        }
        .nameserver-row {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .input-group-text {
            min-width: 50px;
            justify-content: center;
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('nameservers-container');
            const addButton = document.getElementById('add-nameserver');
            let nsCount = container.querySelectorAll('.nameserver-row').length;

            // Add nameserver functionality
            addButton.addEventListener('click', function() {
                if (nsCount >= 4) {
                    alert('Maximum 4 nameservers allowed');
                    return;
                }

                const newRow = document.createElement('div');
                newRow.className = 'nameserver-row mb-3';
                newRow.dataset.index = nsCount;
                newRow.innerHTML = `
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-info text-white">
                                <strong>NS${nsCount + 1}</strong>
                            </span>
                        </div>
                        <input type="text"
                            class="form-control"
                            name="nameservers[]"
                            placeholder="ns${nsCount + 1}.example.com">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-danger btn-remove-ns">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;

                container.appendChild(newRow);
                nsCount++;
                updateAddButtonState();
                attachRemoveHandlers();
            });

            // Remove nameserver functionality
            function attachRemoveHandlers() {
                document.querySelectorAll('.btn-remove-ns').forEach(button => {
                    button.addEventListener('click', function() {
                        if (nsCount <= 2) {
                            alert('Minimum 2 nameservers required');
                            return;
                        }

                        this.closest('.nameserver-row').remove();
                        nsCount--;
                        updateNameserverLabels();
                        updateAddButtonState();
                    });
                });
            }

            // Update nameserver labels after removal
            function updateNameserverLabels() {
                const rows = container.querySelectorAll('.nameserver-row');
                rows.forEach((row, index) => {
                    row.dataset.index = index;
                    row.querySelector('.input-group-text strong').textContent = `NS${index + 1}`;
                    const input = row.querySelector('input');
                    if (input.placeholder.startsWith('ns')) {
                        input.placeholder = `ns${index + 1}.example.com`;
                    }
                });
            }

            // Update add button state
            function updateAddButtonState() {
                if (nsCount >= 4) {
                    addButton.disabled = true;
                    addButton.classList.add('disabled');
                } else {
                    addButton.disabled = false;
                    addButton.classList.remove('disabled');
                }
            }

            // Initial setup
            attachRemoveHandlers();
            updateAddButtonState();
        });
    </script>
    @endpush
</x-admin-layout>
