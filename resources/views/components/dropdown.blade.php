@props(['align' => 'right', 'width' => '280px', 'contentClasses' => 'py-1 bg-white'])

@php
    $positionClasses = match ($align) {
        'left' => 'start-0',
        'top' => 'start-50 translate-middle-x',
        default => 'end-0',
    };

    $dropdownWidth = is_numeric($width) ? $width . 'px' : $width;
@endphp

<div class="position-relative d-inline-block" x-data="{ open: false }" @click.outside="open = false"
    @close.stop="open = false">
    <div @click.prevent="open = ! open" style="cursor: pointer;">
        {{ $trigger }}
    </div>

    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="dropdown-menu position-absolute {{ $positionClasses }} shadow-lg border rounded show"
        style="z-index: 10000; min-width: {{ $dropdownWidth }}; margin-top: 0.5rem;">
        <div class="{{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
