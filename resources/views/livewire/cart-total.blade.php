<div class="live-chat-has-dropdown">
    <a href="{{ route('cart.index') }}" class="live__chat"
       style="color: white; text-decoration: none; position: relative;">
        <i class="bi bi-cart-plus-fill icon"></i>
        @if($this->cartItemsCount > 0)
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
        {{ $this->formattedTotal }}
    </a>
</div>
