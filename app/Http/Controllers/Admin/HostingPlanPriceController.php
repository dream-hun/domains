<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Hosting\PlanPrices\DeletePlanPriceAction;
use App\Actions\Hosting\PlanPrices\ListPlanPriceAction;
use App\Actions\Hosting\PlanPrices\StorePlanPriceAction;
use App\Actions\Hosting\PlanPrices\UpdatePlanPriceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlanPriceRequest;
use App\Http\Requests\Admin\UpdatePlanPriceRequest;
use App\Models\Currency;
use App\Models\HostingCategory;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class HostingPlanPriceController extends Controller
{
    public function index(ListPlanPriceAction $action): View|Factory
    {
        $categoryUuid = request()->input('category_id');
        $categoryUuid = ($categoryUuid !== null && $categoryUuid !== '') ? (string) $categoryUuid : null;

        $planUuid = request()->input('plan_id');
        $planUuid = ($planUuid !== null && $planUuid !== '') ? (string) $planUuid : null;

        $search = request()->input('search');
        $search = ($search !== null && $search !== '') ? (string) $search : null;

        $currencyId = request()->input('currency_id');
        $currencyId = ($currencyId !== null && $currencyId !== '') ? (int) $currencyId : null;

        $prices = $action->handle(10, $categoryUuid, $planUuid, $search, $currencyId);
        $categories = HostingCategory::getActiveCategories();
        $currencies = Currency::getActiveCurrencies();

        $plansQuery = HostingPlan::query()->select(['uuid', 'name', 'category_id'])->orderBy('name');
        if ($categoryUuid !== null) {
            $plansQuery->whereHas('category', function ($q) use ($categoryUuid): void {
                $q->where('uuid', $categoryUuid);
            });
        }

        $plans = $plansQuery->get();

        return view('admin.hosting-plan-prices.index', [
            'prices' => $prices,
            'categories' => $categories,
            'plans' => $plans,
            'currencies' => $currencies,
            'selectedCategoryUuid' => $categoryUuid,
            'selectedPlanUuid' => $planUuid,
            'selectedCurrencyId' => $currencyId,
            'search' => $search,
        ]);
    }

    public function create(): View|Factory
    {
        $categories = HostingCategory::getActiveCategories();
        $plans = HostingPlan::query()->select(['id', 'name', 'category_id'])->orderBy('name')->get();
        $currencies = Currency::getActiveCurrencies();

        return view('admin.hosting-plan-prices.create', [
            'categories' => $categories,
            'plans' => $plans,
            'currencies' => $currencies,
        ]);
    }

    public function store(StorePlanPriceRequest $request, StorePlanPriceAction $action): RedirectResponse
    {
        $data = $request->validated();
        unset($data['hosting_category_id']);

        $action->handle($data);

        return to_route('admin.hosting-plan-prices.index')->with('success', 'Hosting plan price created successfully.');
    }

    public function edit(HostingPlanPrice $hostingPlanPrice): View|Factory
    {
        $categories = HostingCategory::getActiveCategories();
        $plans = HostingPlan::query()->select(['id', 'name', 'category_id'])->orderBy('name')->get();
        $currencies = Currency::getActiveCurrencies();
        $hostingPlanPrice->load(['plan.category', 'currency']);

        $histories = $hostingPlanPrice->hostingPlanPriceHistories()
            ->with('changedBy')
            ->latest('created_at')
            ->get();

        return view('admin.hosting-plan-prices.edit', [
            'price' => $hostingPlanPrice,
            'categories' => $categories,
            'plans' => $plans,
            'currencies' => $currencies,
            'histories' => $histories,
        ]);
    }

    public function update(UpdatePlanPriceRequest $request, HostingPlanPrice $hostingPlanPrice, UpdatePlanPriceAction $action): RedirectResponse
    {
        $data = $request->validated();
        unset($data['hosting_category_id']);

        $action->handle($hostingPlanPrice->uuid, $data);

        return to_route('admin.hosting-plan-prices.index')->with('success', 'Hosting plan price updated successfully.');
    }

    public function destroy(HostingPlanPrice $hostingPlanPrice, DeletePlanPriceAction $action): RedirectResponse
    {
        $action->handle($hostingPlanPrice->uuid);

        return to_route('admin.hosting-plan-prices.index')->with('success', 'Hosting plan price deleted successfully.');
    }
}
