<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\CurrencyHelper;
use App\Models\Currency;
use App\Models\HostingCategory;
use App\Models\HostingPlan;
use App\Models\Tld;
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
        $userCurrencyCode = CurrencyHelper::getUserCurrency();
        $currency = Currency::getActiveCurrencies()->firstWhere('code', $userCurrencyCode);
        $currencyId = $currency?->id ?? Currency::getBaseCurrency()->id;

        $allPlans = HostingPlan::query()
            ->where('status', 'active')
            ->with([
                'category:id,name,slug',
                'planPrices' => fn ($q) => $q->where('status', 'active')
                    ->where('currency_id', $currencyId)
                    ->with('currency'),
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

        $domainCompareTlds = Tld::query()
            ->with(['tldPricings' => fn ($q) => $q->current()->with('currency')])
            ->whereIn('name', ['.com', '.net', '.info', '.org'])
            ->get()
            ->keyBy(fn (Tld $tld): string => mb_ltrim($tld->name, '.'));

        $domainComparePrices = [];
        foreach (['com', 'net', 'info', 'org'] as $ext) {
            $tld = $domainCompareTlds->get($ext);
            $domainComparePrices[$ext] = $tld
                ? $tld->getFormattedPriceWithFallback('register_price', $userCurrencyCode)
                : null;
        }

        return view('welcome', [
            'hostingCategories' => $hostingCategories,
            'hostingPlans' => $allPlans,
            'selectedCurrency' => $userCurrencyCode,
            'domainComparePrices' => $domainComparePrices,
        ]);
    }
}
