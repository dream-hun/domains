<x-admin-layout>
    @section('page-title')
        Domains
    @endsection
    <section class="content-header">
        <div class="container-fluid col-md-12">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Manage Domains</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Domains</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <a class="btn btn-success" href="{{ route('domains') }}">
                Register Domain
            </a>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            Domains
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover datatable datatable-Domain w-100">
                    <thead>
                        <tr>
                            <th>
                                Domain Name
                            </th>
                            <th>
                                Status
                            </th>
                            <th>
                                Expiry Date
                            </th>
                            <th>
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($domains as $key => $domain)
                            <tr data-entry-id="{{ $domain->id }}">



                                <td>
                                    {{ $domain->name ?? '' }}
                                </td>
                                <td>
                                    <span class="btn btn-sm {{ $domain->status->color() }}">
                                        <i class="bi bi-{{ $domain->status->icon() }}"></i>
                                        {{ $domain->status->label() }}
                                    </span>
                                </td>
                                <td>
                                    {{ $domain->expiresAt() ?? '' }}
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        @can('domain_edit')
                                            <a href="{{ route('admin.domains.edit', $domain->uuid) }}"
                                                class="btn btn-sm btn-warning">
                                                <i class="bi bi-server"></i> Manage
                                            </a>
                                        @endcan

                                        @can('domain_renew')
                                            @if ($domain->status !== 'expired')
                                                <button onclick="addRenewalToCart('{{ $domain->uuid }}', '{{ $domain->name }}', {{ $domain->id }})"
                                                    class="btn btn-sm btn-success">
                                                    <i class="bi bi-cart-plus"></i> Renew
                                                </button>
                                            @endif
                                        @endcan
                                        @can('domain_edit')
                                            @if ($domain->status === 'expired')
                                                <form action="{{ route('admin.domains.reactivate', $domain->uuid) }}"
                                                    method="POST" style="display: inline-block;">
                                                    @csrf
                                                    <input type="hidden" name="domain" value="{{ $domain->name }}">
                                                    <button type="submit" class="btn btn-sm btn-warning"
                                                        onclick="return confirm('Are you sure you want to reactivate this domain? Additional fees may apply.')">
                                                        <i class="bi bi-arrow-clockwise"></i> Reactivate
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                        @can('domain_edit')
                                            
                                                <a href="{{ route('admin.domains.assign', $domain->uuid) }}"
                                                class="btn btn-sm btn-primary">
                                                <i class="bi bi-person"></i> Assign Owner
                                            </a>
                                            
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3 float-right">
                {{ $domains->links('vendor.pagination.adminlte') }}
            </div>
        </div>
    </div>

    @section('styles')
        @parent
        <style>
            .datatable-Domain {
                width: 100% !important;
                table-layout: fixed;
            }

            .datatable-Domain th:nth-child(1) {
                width: 30%;
            }

            /* Domain Name */
            .datatable-Domain th:nth-child(2) {
                width: 15%;
            }

            /* Status */
            .datatable-Domain th:nth-child(3) {
                width: 20%;
            }

            /* Expiry Date */
            .datatable-Domain th:nth-child(4) {
                width: 35%;
            }

            /* Actions */

            .datatable-Domain td:nth-child(1) {
                width: 30%;
            }

            .datatable-Domain td:nth-child(2) {
                width: 15%;
            }

            .datatable-Domain td:nth-child(3) {
                width: 20%;
            }

            .datatable-Domain td:nth-child(4) {
                width: 35%;
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
                    paging: false, // Disable DataTable pagination to use Laravel pagination
                    searching: true, // Enable search
                    ordering: true, // Enable sorting
                    info: false, // Disable "Showing X to Y of Z entries" info
                    lengthChange: false, // Disable "Show X entries" dropdown
                    dom: 'Bfrtip', // B=buttons, f=filter(search), r=processing, t=table, i=info, p=pagination
                    autoWidth: false, // Disable auto width calculation
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

            // Add domain renewal to cart
            function addRenewalToCart(domainUuid, domainName, domainId) {
                // Disable the button to prevent double-clicks
                event.target.disabled = true;
                event.target.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Adding...';

                // Send AJAX request to add to cart
                fetch(`/domains/${domainUuid}/renew/add-to-cart`, {
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
                        event.target.disabled = false;
                        event.target.innerHTML = '<i class="bi bi-cart-plus"></i> Renew';
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
                    event.target.disabled = false;
                    event.target.innerHTML = '<i class="bi bi-cart-plus"></i> Renew';
                });
            }
        </script>
    @endsection
</x-admin-layout>

