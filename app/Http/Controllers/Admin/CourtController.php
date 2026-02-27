<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Court\DeleteAction;
use App\Actions\Admin\Court\ListAction;
use App\Actions\Admin\Court\StoreAction;
use App\Actions\Admin\Court\UpdateAction;
use App\Enums\CourtStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Court\DeleteCourtRequest;
use App\Http\Requests\Admin\Court\StoreCourtRequest;
use App\Http\Requests\Admin\Court\UpdateCourtRequest;
use App\Models\Court;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CourtController extends Controller
{
    public function index(Request $request, ListAction $action): Response
    {
        $search = $request->string('search')->toString() ?: null;

        return Inertia::render('admin/courts/index', [
            'courts' => $action->handle($search),
            'filters' => ['search' => $search],
            'statuses' => array_map(
                fn (CourtStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'color' => $status->color(),
                ],
                CourtStatus::cases()
            ),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/courts/create', [
            'statuses' => array_map(
                fn (CourtStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'color' => $status->color(),
                ],
                CourtStatus::cases()
            ),
        ]);
    }

    public function store(StoreCourtRequest $request, StoreAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($request->validated(), $user);

        return to_route('admin.courts.index')->with('success', 'Court created successfully.');
    }

    public function edit(Court $court): Response
    {
        return Inertia::render('admin/courts/edit', [
            'court' => $court,
            'statuses' => array_map(
                fn (CourtStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'color' => $status->color(),
                ],
                CourtStatus::cases()
            ),
        ]);
    }

    public function update(UpdateCourtRequest $request, UpdateAction $action, Court $court): RedirectResponse
    {
        $action->handle($court, $request->validated());

        return to_route('admin.courts.index');
    }

    public function destroy(DeleteCourtRequest $request, DeleteAction $action, Court $court): RedirectResponse
    {
        $action->handle($court);

        return back();
    }
}
