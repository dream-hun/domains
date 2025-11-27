<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HostingCategory;
use App\Models\HostingPlan;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class LandingController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View|Factory
    {
        $hostingCategories = HostingCategory::query()
            ->select(['id', 'name', 'slug', 'icon', 'description'])
            ->where('status', 'active')
            ->with(['plans' => fn ($q) => $q->where('status', 'active')->with(['planPrices' => fn ($p) => $p->where('status', 'active')])])
            ->get();

        $hostingPlans = HostingPlan::query()
            ->where('status', 'active')
            ->with([
                'category:id,name,slug',
                'planPrices' => fn ($q) => $q->where('status', 'active'),
                'planFeatures.hostingFeature',
            ])
            ->orderBy('sort_order')
            ->get();

        return view('welcome', [
            'hostingCategories' => $hostingCategories,
            'hostingPlans' => $hostingPlans,
        ]);
    }
}
