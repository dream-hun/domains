<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\User\DeleteAction;
use App\Actions\Admin\User\ListAction;
use App\Actions\Admin\User\StoreAction;
use App\Actions\Admin\User\UpdateAction;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\DeleteUserRequest;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class UserController extends Controller
{
    public function index(Request $request, ListAction $action): Response
    {
        $search = $request->string('search')->toString() ?: null;

        return Inertia::render('admin/users/index', [
            'users' => $action->handle($search),
            'roles' => array_map(
                fn (Role $role): array => [
                    'value' => $role->value,
                    'label' => $role->label(),
                    'color' => $role->color(),
                ],
                Role::cases()
            ),
            'filters' => ['search' => $search],
        ]);
    }

    public function store(StoreUserRequest $request, StoreAction $action): RedirectResponse
    {
        /** @var array{name: string, email: string, password: string, role: string} $data */
        $data = $request->validated();
        $action->handle($data);

        return to_route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, UpdateAction $action, User $user): RedirectResponse
    {
        /** @var array{name: string, email: string, role: string} $data */
        $data = $request->validated();
        $action->handle($user, $data);

        return to_route('admin.users.index');
    }

    public function destroy(DeleteUserRequest $request, DeleteAction $action, User $user): RedirectResponse
    {
        $action->handle($user);

        return to_route('admin.users.index');
    }
}
