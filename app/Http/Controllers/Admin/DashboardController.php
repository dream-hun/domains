<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\DomainPricing;
use App\Models\Hosting;
use App\Models\Scopes\DomainPricingScope;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $tlds = Cache::remember('dashboard.tlds', 3600, function () {
            return DomainPricing::withoutGlobalScope(DomainPricingScope::class)->count();
        });
        $plans = Cache::remember('dashboard.plans', 3600, function () {
            return Hosting::count();
        });

        $customers = Cache::remember('dashboard.customers', 3600, function () {
            return User::whereHas('roles', function ($query): void {
                $query->where('title', 'user');
            })->count();
        });
        $domains = Cache::remember('dashboard.domains', 3600, function () {
            return Domain::count();
        });

        return view('dashboard', ['tlds' => $tlds, 'customers' => $customers, 'domains' => $domains, 'plans' => $plans]);
    }
}
