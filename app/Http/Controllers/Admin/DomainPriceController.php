<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Prices\DeleteDomainPriceAction;
use App\Actions\Prices\ListDomainPriceAction;
use App\Actions\Prices\StoreDomainPriceAction;
use App\Actions\Prices\UpdateDomainPriceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDomainPriceRequest;
use App\Http\Requests\Admin\UpdateDomainPriceRequest;
use App\Models\DomainPrice;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class DomainPriceController extends Controller
{
    public function index(ListDomainPriceAction $action): View|Factory
    {
        $prices = $action->handle();

        return view('admin.prices.index', ['prices' => $prices]);
    }

    public function create(): View|Factory
    {
        return view('admin.prices.create');
    }

    public function store(StoreDomainPriceRequest $request, StoreDomainPriceAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('admin.prices.index')->with('success', 'Domain price created successfully.');
    }

    public function edit(DomainPrice $price): View|Factory
    {
        $histories = $price->domainPriceHistories()
            ->with('changedBy')
            ->latest('created_at')
            ->get();

        return view('admin.prices.edit', [
            'price' => $price,
            'histories' => $histories,
        ]);
    }

    public function update(UpdateDomainPriceRequest $request, DomainPrice $price, UpdateDomainPriceAction $action): RedirectResponse
    {
        $action->handle($price->uuid, $request->validated());

        return to_route('admin.prices.index')->with('success', 'Domain price updated successfully.');
    }

    public function destroy(DomainPrice $price, DeleteDomainPriceAction $action): RedirectResponse
    {
        $action->handle($price->uuid);

        return to_route('admin.prices.index')->with('success', 'Domain price deleted successfully.');
    }
}
