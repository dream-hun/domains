<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\TldPricing\DeleteTldPricingAction;
use App\Actions\TldPricing\ListTldPricingAction;
use App\Actions\TldPricing\StoreTldPricingAction;
use App\Actions\TldPricing\UpdateTldPricingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTldPricingRequest;
use App\Http\Requests\Admin\UpdateTldPricingRequest;
use App\Models\Currency;
use App\Models\Tld;
use App\Models\TldPricing;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class TldPricingController extends Controller
{
    public function index(ListTldPricingAction $action): View|Factory
    {
        abort_if(Gate::denies('tld_pricing_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tldPricings = $action->handle();

        return view('admin.tld-pricing.index', ['tldPricings' => $tldPricings]);
    }

    public function create(): View|Factory
    {
        abort_if(Gate::denies('tld_pricing_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tlds = Tld::query()->orderBy('name')->get();
        $currencies = Currency::getActiveCurrencies();

        return view('admin.tld-pricing.create', [
            'tlds' => $tlds,
            'currencies' => $currencies,
        ]);
    }

    public function store(StoreTldPricingRequest $request, StoreTldPricingAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('admin.tld-pricings.index')->with('success', 'TLD pricing created successfully.');
    }

    public function edit(TldPricing $tldPricing): View|Factory
    {
        abort_if(Gate::denies('tld_pricing_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $tldPricing->load(['tld', 'currency']);

        $tlds = Tld::query()->orderBy('name')->get();
        $currencies = Currency::getActiveCurrencies();

        $histories = $tldPricing->domainPriceHistories()
            ->with('changedBy')
            ->latest('created_at')
            ->get();

        return view('admin.tld-pricing.edit', [
            'tldPricing' => $tldPricing,
            'tlds' => $tlds,
            'currencies' => $currencies,
            'histories' => $histories,
        ]);
    }

    public function update(UpdateTldPricingRequest $request, TldPricing $tldPricing, UpdateTldPricingAction $action): RedirectResponse
    {
        $action->handle($tldPricing, $request->validated());

        return to_route('admin.tld-pricings.index')->with('success', 'TLD pricing updated successfully.');
    }

    public function destroy(TldPricing $tldPricing, DeleteTldPricingAction $action): RedirectResponse
    {
        abort_if(Gate::denies('tld_pricing_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $action->handle($tldPricing);

        return to_route('admin.tld-pricings.index')->with('success', 'TLD pricing deleted successfully.');
    }
}
