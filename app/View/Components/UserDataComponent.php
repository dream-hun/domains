<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Domain;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class UserDataComponent extends Component
{
    public function render(): View|Closure|string
    {
        $totalDomains = Domain::count();
        $totalSSL = 0;
        $totalHostingPlans = 0;
        $totalVPS = 0;


        return view('components.user-data-component', ['totalDomains' => $totalDomains, 'totalSSL' => $totalSSL, 'totalHostingPlans' => $totalHostingPlans, 'totalVPS' => $totalVPS]);
    }
}
