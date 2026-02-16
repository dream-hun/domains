@props(['route', 'icon' => null, 'badge' => null])

<li class="nav-item">
    <a href="{{ route($route) }}" 
       class="nav-link {{ request()->routeIs($route) ? 'active' : '' }}">
        @if($icon)
            <i class="nav-icon {{ $icon }}"></i>
        @endif
        <p>
            {{ $slot }}
            @if($badge)
                <span class="right badge {{ $badge['class'] ?? 'badge-danger' }}">{{ $badge['text'] }}</span>
            @endif
        </p>
    </a>
</li>
