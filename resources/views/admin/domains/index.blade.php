@php use App\Enums\DomainStatus; @endphp
<x-admin-layout>
    @section('page-title')
        Domains
    @endsection

    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Manage Domains</h1>
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
                    <a class="btn btn-success" href="{{ route('domains') }}">
                        Register Domain
                    </a>
                    @can('domain_create')
                        <a class="btn btn-primary" href="{{ route('admin.domains.custom-register') }}">
                            Custom Registration
                        </a>
                    @endcan
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Domains</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover datatable datatable-Domain w-100">
                            <thead>
                            <tr>
                                <th>Domain</th>
                                <th>Owner</th>
                                <th>Status</th>
                                <th>Expiry Date</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($domains as $key => $domain)
                                <tr data-entry-id="{{ $domain->id }}">
                                    <td>{{ $domain->name ?? '' }}</td>
                                    <td>{{ $domain->owner->name ?? '' }}</td>
                                    <td>
                                            <span class="badge {{ $domain->status->color() }}">
                                                {{ $domain->status->label() }}
                                            </span>
                                    </td>
                                    <td>{{ $domain->expiresAt() ?? '' }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @can('domain_edit')
                                                <a href="{{ route('admin.domains.edit', $domain->uuid) }}"
                                                   class="btn btn-sm btn-warning">
                                                    Manage
                                                </a>
                                            @endcan

                                            @can('domain_renew')
                                                @if ($domain->status !== DomainStatus::Expired)
                                                    <button
                                                        onclick="addRenewalToCart(this, '{{ $domain->uuid }}', '{{ $domain->name }}', {{ $domain->id }})"
                                                        class="btn btn-sm btn-success">
                                                        Renew
                                                    </button>
                                                @endif
                                            @endcan

                                            @can('domain_edit')
                                                @if ($domain->status === DomainStatus::Expired)
                                                    <form
                                                        action="{{ route('admin.domains.reactivate', $domain->uuid) }}"
                                                        method="POST" style="display: inline-block;">
                                                        @csrf
                                                        <input type="hidden" name="domain" value="{{ $domain->name }}">
                                                        <button type="submit" class="btn btn-sm btn-warning"
                                                                onclick="return confirm('Are you sure you want to reactivate this domain? Additional fees may apply.')">
                                                            Reactivate
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan

                                            @can('domain_edit')
                                                <a href="{{ route('admin.domains.assign', $domain->uuid) }}"
                                                   class="btn btn-sm btn-primary">
                                                    Assign Owner
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
        </style>
    @endsection

    @section('scripts')
        @parent
        <script>
            $(function () {
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

                $('a[data-toggle="tab"]').on('shown.bs.tab click', function (e) {
                    $($.fn.dataTable.tables(true)).DataTable()
                        .columns.adjust();
                });
            })

            const renewalAddToCartUrlTemplate = @json(route('domains.renew.add-to-cart', ['domain' => '__DOMAIN_UUID__']));

            function addRenewalToCart(element, domainUuid, domainName, domainId) {
                element.disabled = true;
                const originalContent = element.innerHTML;
                element.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Adding...';

                const addToCartUrl = renewalAddToCartUrlTemplate.replace('__DOMAIN_UUID__', domainUuid);

                fetch(addToCartUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        years: 1,
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
                            alert(`${domainName} has been added to your cart for renewal!`);
                            window.location.href = '/shopping-cart';
                        } else {
                            alert(data.message || 'Failed to add domain to cart');
                            element.disabled = false;
                            element.innerHTML = originalContent;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                        element.disabled = false;
                        element.innerHTML = originalContent;
                    });
            }
        </script>
    @endsection
</x-admin-layout>
