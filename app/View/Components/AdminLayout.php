<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class AdminLayout extends Component
{
    public function __construct(
        public ?string $pageTitle = null
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('layouts.admin');
    }
}
