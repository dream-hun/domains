<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="mb-0">Review Your Order</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Registration Period</th>
                        <th class="text-right">Price per Period</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->cartItems as $item)
                        <tr>
                            <td>
                                <strong>{{ $this->getItemDisplayName($item) }}</strong>
                                @if(isset($item->attributes['whois_privacy']) && $item->attributes['whois_privacy'])
                                    <br>
                                    <small class="text-muted">
                                        <i class="bi bi-shield"></i> + WHOIS Privacy Protection
                                    </small>
                                @endif
                            </td>
                            <td>
                                {{ $this->getRegistrationPeriod($item) }}
                            </td>
                            <td class="text-right">
                                {{ $this->getItemUnitPrice($item) }}
                            </td>
                            <td class="text-right">
                                <strong>{{ $this->getItemPrice($item) }}</strong>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <button wire:click="nextStep" class="btn btn-primary btn-md float-right">
            Continue to Contact Information
            <i class="bi bi-arrow-right ml-2"></i>
        </button>
    </div>
</div>
