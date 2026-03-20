<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Domain;
use App\Models\Subscription;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class UserDataComponent extends Component
{
    public function render(): View
    {
        $totalDomains = Domain::query()->count();
        $totalSSL = 0;
        $totalHostingPlans = 0;
        $totalVPS = Subscription::query()->whereNotNull('provider_resource_id')->count();

        return view('components.user-data-component', ['totalDomains' => $totalDomains, 'totalSSL' => $totalSSL, 'totalHostingPlans' => $totalHostingPlans, 'totalVPS' => $totalVPS]);
    }
}
