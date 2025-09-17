<div class="d-flex align-items-center">
    <div class="login-btn-has-dropdown">
        <a href="#" class="login__link">Login</a>
        <div class="login-submenu">
            <form action="#">
                <div class="form-inner text-black" wire:model.live="selectedCurrency">
                    @foreach ($currencies as $currency)
                        <option value="{{ $currency->code }}" @selected($currency->code === $currentCurrency->code)>
                            {{ $currency->symbol }} {{ $currency->code }}
                        </option>
                    @endforeach
                </div>
            </form>
        </div>
    </div>

    <!-- Cart Total -->
    <div class="live-chat-has-dropdown">
        <a href="{{ route('cart.index') }}" class="live__chat"
           style="color: white; text-decoration: none; position: relative;">
            <i class="bi bi-cart-plus-fill icon"></i>
            @if ($this->cartItemsCount > 0)
                <span class="nav-pills"
                      style="
            display: inline-flex;
            justify-content: center;
            align-items: center;
            background-color: #FFC107;
            margin-top: -6px;
            color: white;
            border-radius: 50%;
            width: 17px;
            height: 17px;
            font-size: 10px;
          ">
                    {{ $this->cartItemsCount }}
                </span>
            @endif &nbsp;&nbsp;
            {{ $formattedTotal }}
        </a>
    </div>
</div>
