<x-admin-layout page-title="Edit TLD Pricing">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Edit TLD Pricing</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.tld-pricings.index') }}">TLD Pricing</a></li>
                        <li class="breadcrumb-item active">Edit TLD Pricing</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="col-md-12">
                @include('admin.tld-pricing._form', [
                    'tldPricing' => $tldPricing,
                    'tlds' => $tlds,
                    'currencies' => $currencies,
                    'action' => route('admin.tld-pricings.update', $tldPricing),
                    'method' => 'PUT',
                    'submitLabel' => 'Update',
                ])
            </div>
        </div>
    </section>
</x-admin-layout>
