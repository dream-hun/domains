@props(['title', 'icon', 'routes' => []])

@php
$isActive = false;
if (!empty($routes)) {
    // Check route names with wildcards (e.g., 'admin.tlds.*')
    $isActive = request()->routeIs($routes);
    
    // Also check URL paths as fallback (convert route patterns to URL patterns)
    if (!$isActive) {
        $pathPatterns = array_map(function($route) {
            // Convert 'admin.tlds.*' to 'admin/tlds*'
            return str_replace(['admin.', '.*'], ['admin/', '*'], $route);
        }, $routes);
        $isActive = request()->is($pathPatterns);
    }
}
@endphp

<li class="nav-item {{ $isActive ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ $isActive ? 'active' : '' }}">
        @if($icon)
            <i class="nav-icon {{ $icon }}"></i>
        @endif
        <p>
            {{ $title }}
            <i class="bi bi-chevron-down right"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        {{ $slot }}
    </ul>
</li>
