<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Currency\DeleteCurrencyAction;
use App\Actions\Currency\ListCurrencyAction;
use App\Actions\Currency\StoreCurrencyAction;
use App\Actions\Currency\UpdateCurrencyAction;
use App\Actions\Currency\UpdateExchangeRatesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCurrencyRequest;
use App\Http\Requests\Admin\UpdateCurrencyRequest;
use App\Models\Currency;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class CurrencyController extends Controller
{
    public function index(ListCurrencyAction $action): View|Factory
    {
        abort_if(Gate::denies('currency_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $currencies = $action->handle();

        return view('admin.currencies.index', ['currencies' => $currencies]);
    }

    public function create(): View|Factory
    {
        abort_if(Gate::denies('currency_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.currencies.create');
    }

    public function store(StoreCurrencyRequest $request, StoreCurrencyAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('admin.currencies.index')->with('success', 'Currency created successfully.');
    }

    public function edit(Currency $currency): View|Factory
    {
        abort_if(Gate::denies('currency_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.currencies.edit', ['currency' => $currency]);
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency, UpdateCurrencyAction $action): RedirectResponse
    {
        $action->handle($currency, $request->validated());

        return to_route('admin.currencies.index')->with('success', 'Currency updated successfully.');
    }

    public function destroy(Currency $currency, DeleteCurrencyAction $action): RedirectResponse
    {
        abort_if(Gate::denies('currency_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        try {
            $action->handle($currency);

            return to_route('admin.currencies.index')->with('success', 'Currency deleted successfully.');
        } catch (Exception $exception) {
            return to_route('admin.currencies.index')->with('error', $exception->getMessage());
        }
    }

    public function updateRates(UpdateExchangeRatesAction $action): RedirectResponse
    {
        abort_if(Gate::denies('currency_update_rates'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        try {
            $success = $action->handle();

            if ($success) {
                return to_route('admin.currencies.index')->with('success', 'Exchange rates updated successfully. All user carts have been cleared.');
            }

            return to_route('admin.currencies.index')->with('error', 'Failed to update exchange rates. Please try again.');
        } catch (Exception $exception) {
            return to_route('admin.currencies.index')->with('error', 'Error updating exchange rates: '.$exception->getMessage());
        }
    }
}
