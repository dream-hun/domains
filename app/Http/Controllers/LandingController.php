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

        $allPlans = HostingPlan::query()
            ->where('status', 'active')
            ->with([
                'category:id,name,slug',
                'planPrices' => fn ($q) => $q->where('status', 'active')->with('currency'),
                'planFeatures.hostingFeature',
            ])
            ->orderBy('sort_order')
            ->get();

        // Load categories with their plans (prices already loaded above)
        $hostingCategories = HostingCategory::query()
            ->select(['id', 'name', 'slug', 'icon', 'description'])
            ->where('status', 'active')
            ->with(['plans' => fn ($q) => $q->where('status', 'active')])
            ->get();

        // Manually attach already-loaded planPrices to plans in categories to avoid duplicate queries
        foreach ($hostingCategories as $category) {
            foreach ($category->plans as $plan) {
                /** @var HostingPlan $plan */
                $loadedPlan = $allPlans->firstWhere('id', $plan->id);
                if ($loadedPlan && $loadedPlan->relationLoaded('planPrices')) {
                    $plan->setRelation('planPrices', $loadedPlan->planPrices);
                }
            }
        }

        return view('welcome', [
            'hostingCategories' => $hostingCategories,
            'hostingPlans' => $allPlans,
        ]);
    }
}
