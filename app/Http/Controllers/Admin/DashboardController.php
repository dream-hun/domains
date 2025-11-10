<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\DomainPrice;
use App\Models\Scopes\DomainPriceScope;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View|Factory
    {
        $tlds = Cache::remember('dashboard.tlds', 3600, fn () => DomainPrice::query()->withoutGlobalScope(DomainPriceScope::class)->count());
        $plans = Cache::remember('dashboard.plans', 3600, fn (): int => 0);

        $customers = Cache::remember('dashboard.customers', 3600, fn () => User::query()->whereHas('roles', function ($query): void {
            $query->where('title', 'user');
        })->count());
        $domains = Cache::remember('dashboard.domains', 3600, fn () => Domain::query()->count());

        return view('dashboard', ['tlds' => $tlds, 'customers' => $customers, 'domains' => $domains, 'plans' => $plans]);
    }
}
