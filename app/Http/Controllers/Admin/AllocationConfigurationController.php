<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Allocation\UpdateAllocationConfiguration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Allocation\UpdateAllocationConfigurationRequest;
use App\Models\AllocationConfiguration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class AllocationConfigurationController extends Controller
{
    public function edit(): Response
    {
        $config = AllocationConfiguration::query()->latest()->firstOrFail();

        return Inertia::render('admin/allocation-configuration/edit', [
            'config' => $config,
        ]);
    }

    public function update(UpdateAllocationConfigurationRequest $request, UpdateAllocationConfiguration $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        $action->handle(
            (float) $validated['insurance_percentage'],
            (float) $validated['savings_percentage'],
            (float) $validated['pathway_percentage'],
            (float) $validated['administration_percentage'],
            $user,
        );

        return to_route('admin.allocation-configuration.edit')->with('success', 'Allocation configuration saved.');
    }
}
