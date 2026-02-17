@php
use Illuminate\Support\Str;
@endphp
<x-admin-layout page-title="Edit TLD Pricing">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-edit mr-2"></i>Edit TLD Pricing
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.tld-pricings.index') }}">TLD Pricing</a></li>
                        <li class="breadcrumb-item active">{{ $tldPricing->tld?->name ?? 'Edit' }}</li>
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
                        'tldPricing' => $tldPricing,
                        'tlds' => $tlds,
                        'currencies' => $currencies,
                        'action' => route('admin.tld-pricings.update', $tldPricing),
                        'method' => 'PUT',
                        'submitLabel' => 'Update',
                    ])
                </div>

                <div class="col-lg-4">
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-2"></i>Quick Info
                            </h3>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-5"><i class="fas fa-globe mr-1"></i>TLD:</dt>
                                <dd class="col-sm-7"><strong>{{ $tldPricing->tld?->name ?? 'Global' }}</strong></dd>

                                <dt class="col-sm-5"><i class="fas fa-money-bill mr-1"></i>Currency:</dt>
                                <dd class="col-sm-7">{{ $tldPricing->currency?->code ?? 'N/A' }}</dd>

                                <dt class="col-sm-5"><i class="fas fa-check-circle mr-1"></i>Current:</dt>
                                <dd class="col-sm-7">
                                    <span class="badge badge-{{ $tldPricing->is_current ? 'success' : 'secondary' }}">
                                        {{ $tldPricing->is_current ? 'Yes' : 'No' }}
                                    </span>
                                </dd>

                                <dt class="col-sm-5"><i class="fas fa-calendar-day mr-1"></i>Effective:</dt>
                                <dd class="col-sm-7">{{ $tldPricing->effective_date?->format('M d, Y') ?? 'N/A' }}</dd>

                                <dt class="col-sm-5"><i class="fas fa-calendar mr-1"></i>Created:</dt>
                                <dd class="col-sm-7">{{ $tldPricing->created_at?->format('M d, Y') ?? 'N/A' }}</dd>

                                <dt class="col-sm-5"><i class="fas fa-edit mr-1"></i>Updated:</dt>
                                <dd class="col-sm-7">{{ $tldPricing->updated_at?->format('M d, Y') ?? 'N/A' }}</dd>
                            </dl>
                        </div>
                    </div>

                    <div class="card card-warning card-outline mt-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Important Notes
                            </h3>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0 pl-3">
                                <li class="mb-2">Prices are stored in <strong>smallest currency units</strong> (e.g. cents)</li>
                                <li class="mb-2">Price changes require a <strong>reason</strong></li>
                                <li class="mb-2">All changes are tracked in history</li>
                                <li>Setting <strong>Current = Yes</strong> deactivates other pricings for this TLD + currency</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-2"></i>Price Change History
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-info badge-lg">{{ $histories->count() }} {{ Str::plural('record', $histories->count()) }}</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            @if($histories->isEmpty())
                                <div class="alert alert-info m-3 mb-0">
                                    <i class="fas fa-info-circle mr-2"></i>No price change history available.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead class="thead-light">
                                        <tr>
                                            <th style="width: 15%">Date & Time</th>
                                            <th style="width: 15%">Changed By</th>
                                            <th style="width: 35%">Price Changes</th>
                                            <th style="width: 20%">Reason</th>
                                            <th style="width: 15%">IP Address</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($histories as $history)
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong>{{ $history->created_at->format('M d, Y') }}</strong>
                                                        <div class="text-muted small">{{ $history->created_at->format('H:i:s') }}</div>
                                                        <div class="text-muted" style="font-size: 0.7rem;">
                                                            <i class="fas fa-clock mr-1"></i>{{ $history->created_at->diffForHumans() }}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <i class="fas fa-user mr-1 text-muted"></i>
                                                    <strong>{{ $history->changedBy?->name ?? 'System' }}</strong>
                                                </td>
                                                <td>
                                                    @php
                                                        $oldValues = $history->old_values ?? [];
                                                        $changes = $history->changes ?? [];
                                                        $historyCurrencyCode = $tldPricing->currency?->code ?? 'USD';
                                                    @endphp
                                                    <div class="small">
                                                        @if(isset($changes['register_price']))
                                                            <div class="mb-1">
                                                                <span class="badge badge-light">Register:</span>
                                                                <span class="text-muted">
                                                                    {{ app(\App\Services\PriceFormatter::class)->format((float) ($oldValues['register_price'] ?? 0), $historyCurrencyCode) }}
                                                                </span>
                                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                <span class="text-success font-weight-bold">
                                                                    {{ app(\App\Services\PriceFormatter::class)->format((float) $changes['register_price'], $historyCurrencyCode) }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if(isset($changes['renew_price']))
                                                            <div class="mb-1">
                                                                <span class="badge badge-light">Renew:</span>
                                                                <span class="text-muted">
                                                                    {{ app(\App\Services\PriceFormatter::class)->format((float) ($oldValues['renew_price'] ?? 0), $historyCurrencyCode) }}
                                                                </span>
                                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                <span class="text-success font-weight-bold">
                                                                    {{ app(\App\Services\PriceFormatter::class)->format((float) $changes['renew_price'], $historyCurrencyCode) }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if(isset($changes['transfer_price']))
                                                            <div class="mb-1">
                                                                <span class="badge badge-light">Transfer:</span>
                                                                <span class="text-muted">
                                                                    {{ app(\App\Services\PriceFormatter::class)->format((float) ($oldValues['transfer_price'] ?? 0), $historyCurrencyCode) }}
                                                                </span>
                                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                <span class="text-success font-weight-bold">
                                                                    {{ app(\App\Services\PriceFormatter::class)->format((float) $changes['transfer_price'], $historyCurrencyCode) }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                        @if(isset($changes['redemption_price']))
                                                            <div class="mb-1">
                                                                <span class="badge badge-light">Redemption:</span>
                                                                <span class="text-muted">
                                                                    {{ app(\App\Services\PriceFormatter::class)->format((float) ($oldValues['redemption_price'] ?? 0), $historyCurrencyCode) }}
                                                                </span>
                                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                                <span class="text-success font-weight-bold">
                                                                    {{ app(\App\Services\PriceFormatter::class)->format((float) $changes['redemption_price'], $historyCurrencyCode) }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($history->reason)
                                                        <div class="text-wrap" style="max-width: 200px;">
                                                            {{ Str::limit($history->reason, 80) }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted"><i>No reason provided</i></span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="text-muted small">
                                                        <i class="fas fa-network-wired mr-1"></i>{{ $history->ip_address ?? '—' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-admin-layout>
