<x-admin-layout>
    @section('page-title')
        Domains
    @endsection
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Manage Domains</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Domains</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a class="btn btn-success btn-lg shadow-sm" href="{{ route('domains') }}">
                                <i class="bi bi-plus-circle"></i> Register Domain
                            </a>
                            <a class="btn btn-outline-primary btn-lg shadow-sm ml-2" href="{{ route('admin.domains.custom-register.create') }}">
                                <i class="bi bi-gear"></i> Custom Registration
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-primary card-outline shadow-sm">
                <div class="card-header border-bottom">
                    <h3 class="card-title">
                        <i class="bi bi-globe"></i> All Domains
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="bi bi-dash"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 datatable datatable-Domain w-100">
                            <thead class="thead-light">
                                <tr>
                                    <th class="align-middle">
                                        <i class="bi bi-link-45deg"></i> Domain
                                    </th>
                                    <th class="align-middle">
                                        <i class="bi bi-person"></i> Owner
                                    </th>
                                    <th class="align-middle">
                                        <i class="bi bi-info-circle"></i> Status
                                    </th>
                                    <th class="align-middle">
                                        <i class="bi bi-calendar-event"></i> Expiry Date
                                    </th>
                                    <th class="align-middle text-center" style="min-width: 200px;">
                                        <i class="bi bi-gear"></i> Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($domains as $key => $domain)
                                    <tr data-entry-id="{{ $domain->id }}" class="align-middle">
                                        <td class="font-weight-bold">
                                            <span class="text-primary">{{ $domain->name ?? '' }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-circle mr-2 text-muted"></i>
                                                <span>{{ $domain->owner->name ?? 'N/A' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-lg {{ $domain->status->color() }} px-3 py-2">
                                                <i class="bi bi-{{ $domain->status->icon() }}"></i>
                                                {{ $domain->status->label() }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                <i class="bi bi-calendar3 mr-1"></i>
                                                {{ $domain->expiresAt() ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                @can('domain_edit')
                                                    <a href="{{ route('admin.domains.edit', $domain->uuid) }}"
                                                        class="btn btn-warning" title="Manage Domain">
                                                        <i class="bi bi-server"></i> Manage
                                                    </a>
                                                @endcan

                                                @can('domain_renew')
                                                    @if ($domain->status !== 'expired')
                                                        <button onclick="addRenewalToCart(this, '{{ $domain->uuid }}', '{{ $domain->name }}', {{ $domain->id }})"
                                                            class="btn btn-success" title="Renew Domain">
                                                            <i class="bi bi-cart-plus"></i> Renew
                                                        </button>
                                                    @endif
                                                @endcan

                                                @can('domain_edit')
                                                    @if ($domain->status === 'expired')
                                                        <form action="{{ route('admin.domains.reactivate', $domain->uuid) }}"
                                                            method="POST" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="domain" value="{{ $domain->name }}">
                                                            <button type="submit" class="btn btn-warning"
                                                                onclick="return confirm('Are you sure you want to reactivate this domain? Additional fees may apply.')"
                                                                title="Reactivate Domain">
                                                                <i class="bi bi-arrow-clockwise"></i> Reactivate
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endcan

                                                @can('domain_edit')
                                                    <a href="{{ route('admin.domains.assign', $domain->uuid) }}"
                                                        class="btn btn-primary" title="Assign Owner">
                                                        <i class="bi bi-person"></i> Assign
                                                    </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @section('styles')
        @parent
        <style>
            .datatable-Domain {
                width: 100% !important;
            }

            .datatable-Domain thead th {
                background-color: #f8f9fa;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.85rem;
                letter-spacing: 0.5px;
                border-bottom: 2px solid #dee2e6;
                padding: 1rem 0.75rem;
            }

            .datatable-Domain tbody td {
                padding: 1rem 0.75rem;
                vertical-align: middle;
            }

            .datatable-Domain tbody tr:hover {
                background-color: #f8f9fa;
                transition: background-color 0.2s ease;
            }

            .badge-lg {
                font-size: 0.875rem;
                font-weight: 500;
            }

            .btn-group-sm .btn {
                border-radius: 0.25rem;
                margin-right: 0.25rem;
            }

            .btn-group-sm .btn:last-child {
                margin-right: 0;
            }

            .card-primary.card-outline {
                border-top: 3px solid #007bff;
            }

            .card-header {
                background-color: #ffffff;
                border-bottom: 1px solid #dee2e6;
            }

            .card-title {
                font-weight: 600;
                color: #495057;
            }
        </style>
    @endsection

    @section('scripts')
        @parent
        <script>
            $(function() {
                let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
                let table = $('.datatable-Domain:not(.ajaxTable)').DataTable({
                    buttons: dtButtons,
                    paging: true,
                    pageLength: 10,
                    searching: true,
                    ordering: true,
                    info: true,
                    lengthChange: false,
                    dom: 'Bfrtip',
                    autoWidth: false,
                    language: {
                        search: "Search:",
                        searchPlaceholder: "Search domains..."
                    }
                })

                $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e) {
                    $($.fn.dataTable.tables(true)).DataTable()
                        .columns.adjust();
                });
            })

            const renewalAddToCartUrlTemplate = @json(route('domains.renew.add-to-cart', ['domain' => '__DOMAIN_UUID__']));

            // Add domain renewal to cart
            function addRenewalToCart(element, domainUuid, domainName, domainId) {
                // Disable the button to prevent double-clicks
                element.disabled = true;
                const originalContent = element.innerHTML;
                element.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Adding...';

                // Send AJAX request to add to cart
                const addToCartUrl = renewalAddToCartUrlTemplate.replace('__DOMAIN_UUID__', domainUuid);

                fetch(addToCartUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        years: 1, // Default to 1 year
                        domain_id: domainId
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert(`${domainName} has been added to your cart for renewal!`);
                        // Redirect to cart
                        window.location.href = '/shopping-cart';
                    } else {
                        alert(data.message || 'Failed to add domain to cart');
                        // Re-enable button
                        element.disabled = false;
                        element.innerHTML = originalContent;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    let errorMessage = 'An error occurred. Please try again.';
                    if (error.message) {
                        errorMessage = error.message;
                    }
                    alert(errorMessage);
                    // Re-enable button
                    element.disabled = false;
                    element.innerHTML = originalContent;
                });
            }
        </script>
    @endsection
</x-admin-layout>
