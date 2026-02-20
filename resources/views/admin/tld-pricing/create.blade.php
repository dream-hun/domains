<x-admin-layout page-title="Add TLD Pricing">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-plus mr-2"></i>Add TLD Pricing
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.tld-pricings.index') }}">TLD Pricing</a></li>
                        <li class="breadcrumb-item active">Add TLD Pricing</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-8">
                    @include('admin.tld-pricing._form', [
                        'tldPricing' => null,
                        'tlds' => $tlds,
                        'currencies' => $currencies,
                        'action' => route('admin.tld-pricings.store'),
                        'method' => 'POST',
                        'submitLabel' => 'Create',
                    ])
                </div>
            </div>
        </div>
    </section>
</x-admin-layout>
