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
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class HostingPlanPriceController extends Controller
{
    public function index(ListPlanPriceAction $action): View|Factory
    {
        $prices = $action->handle();

        return view('admin.hosting-plan-prices.index', ['prices' => $prices]);
    }

    public function create(): View|Factory
    {
        $plans = HostingPlan::query()->select(['id', 'name'])->orderBy('name')->get();

        return view('admin.hosting-plan-prices.create', ['plans' => $plans]);
    }

    public function store(StorePlanPriceRequest $request, StorePlanPriceAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('admin.hosting-plan-prices.index')->with('success', 'Hosting plan price created successfully.');
    }

    public function edit(HostingPlanPrice $hostingPlanPrice): View|Factory
    {
        $plans = HostingPlan::query()->select(['id', 'name'])->orderBy('name')->get();

        return view('admin.hosting-plan-prices.edit', [
            'price' => $hostingPlanPrice,
            'plans' => $plans,
        ]);
    }

    public function update(UpdatePlanPriceRequest $request, HostingPlanPrice $hostingPlanPrice, UpdatePlanPriceAction $action): RedirectResponse
    {
        $action->handle($hostingPlanPrice->uuid, $request->validated());

        return to_route('admin.hosting-plan-prices.index')->with('success', 'Hosting plan price updated successfully.');
    }

    public function destroy(HostingPlanPrice $hostingPlanPrice, DeletePlanPriceAction $action): RedirectResponse
    {
        $action->handle($hostingPlanPrice->uuid);

        return to_route('admin.hosting-plan-prices.index')->with('success', 'Hosting plan price deleted successfully.');
    }
}
