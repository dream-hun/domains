<x-admin-layout>
    @section('page-title')
        Domains
    @endsection

    <div class="container-fluid">
        <div class="row">
            <div class="col-12 mt-5">
                <div class="card">
                    <div class="card-header">
                        <h4>Domains</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped table-hover datatable datatable-Domain w-100">
                            <thead>
                                <tr>
                                    <th>Domain Name</th>
                                    <th>Status</th>
                                    <th>Registered At</th>
                                    <th>Expires At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($domains as $domain)
                                    <tr>
                                        <td>{{ $domain->name }}</td>
                                        <td>
                                        <span class="btn btn-sm {{ $domain->status->color() }}">
                                            <i class="bi bi-{{ $domain->status->icon() }}"></i>
                                            {{ $domain->status->label() }}
                                        </span>
                                        </td>
                                        <td>{{ $domain->registeredAt() }}</td>
                                        <td>{{ $domain->expiresAt() }}</td>
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
                                                        <button onclick="addRenewalToCart(this, '{{ $domain->uuid }}', '{{ $domain->name }}', {{ $domain->id }})"
                                                            class="btn btn-sm btn-success">
                                                            <i class="bi bi-cart-plus"></i> Renew
                                                        </button>
                                                    @endif
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
    </div>

    @section('scripts')
        @parent
        <script>
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
