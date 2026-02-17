<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\HostingCategory;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MenuComponent extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $hostingCategories = HostingCategory::getActiveCategories();

        return view('components.menu-component', ['hostingCategories' => $hostingCategories]);
    }
}
