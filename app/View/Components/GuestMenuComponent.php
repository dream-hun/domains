<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\HostingCategory;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class GuestMenuComponent extends Component
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
        $hostingCategories = HostingCategory::query()->select(['name', 'slug', 'description', 'icon'])->where('status', 'active')->get();

        return view('components.guest-menu-component', ['hostingCategories' => $hostingCategories]);
    }
}
