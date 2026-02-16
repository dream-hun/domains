<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Tld\DeleteTldAction;
use App\Actions\Tld\ListTldAction;
use App\Actions\Tld\StoreTldAction;
use App\Actions\Tld\UpdateTldAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTldRequest;
use App\Http\Requests\Admin\UpdateTldRequest;
use App\Models\Tld;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class TldController extends Controller
{
    public function index(ListTldAction $action): View|Factory
    {
        $tlds = $action->handle();

        return view('admin.tld.index', ['tlds' => $tlds]);
    }

    public function create(): View|Factory
    {
        return view('admin.tld.create');
    }

    public function store(StoreTldRequest $request, StoreTldAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('admin.tlds.index')->with('success', 'TLD created successfully.');
    }

    public function edit(Tld $tld): View|Factory
    {

        return view('admin.tld.edit', [
            'tld' => $tld,

        ]);
    }

    public function update(UpdateTldRequest $request, Tld $tld, UpdateTldAction $action): RedirectResponse
    {
        $action->handle($tld, $request->validated());

        return to_route('admin.tlds.index')->with('success', 'TLD updated successfully.');
    }

    public function destroy(Tld $tld, DeleteTldAction $action): RedirectResponse
    {

        $action->handle($tld);

        return to_route('admin.tlds.index')->with('success', 'TLD deleted successfully.');
    }
}
