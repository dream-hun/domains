<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Hosting\CategoryStatus;
use App\Enums\Hosting\HostingPlanPriceStatus;
use App\Enums\Hosting\HostingPlanStatus;
use App\Models\HostingCategory;
use Illuminate\Http\Request;

class CategoryShowController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, $slug)
    {
        $category = HostingCategory::query()
            ->where('slug', $slug)
            ->where('status', CategoryStatus::Active)
            ->with(['plans' => function ($query): void {
                $query->where('status', HostingPlanStatus::Active);
            }, 'plans.planPrices' => function ($query): void {
                $query->where('status', HostingPlanPriceStatus::Active);
            }])
            ->first();
        if (! $category) {
            return to_route('home')->with('error', 'Category not found');
        }

        return view('hosting.show', ['category' => $category, 'plans' => $category->plans]);
    }
}
