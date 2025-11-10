<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class RolesController extends Controller
{
    public function index(): View|Factory
    {
        abort_if(Gate::denies('role_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $roles = Role::with(['permissions'])->get();

        return view('admin.roles.index', ['roles' => $roles]);
    }

    public function create(): View|Factory
    {
        abort_if(Gate::denies('role_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $permissions = Permission::query()->pluck('title', 'id');

        return view('admin.roles.create', ['permissions' => $permissions]);
    }

    public function store(StoreRoleRequest $request): Redirector|RedirectResponse
    {
        $role = Role::query()->create($request->only(['title']));
        $role->permissions()->sync($request->input('permissions', []));

        return to_route('admin.roles.index');
    }

    public function edit(Role $role): View|Factory
    {
        abort_if(Gate::denies('role_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $permissions = Permission::query()->pluck('title', 'id');

        $role->load('permissions');

        return view('admin.roles.edit', ['permissions' => $permissions, 'role' => $role]);
    }

    public function update(UpdateRoleRequest $request, Role $role): Redirector|RedirectResponse
    {
        $role->update($request->only(['title']));
        $role->permissions()->sync($request->input('permissions', []));

        return to_route('admin.roles.index');
    }

    public function show(Role $role): View|Factory
    {
        abort_if(Gate::denies('role_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $role->load('permissions');

        return view('admin.roles.show', ['role' => $role]);
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if(Gate::denies('role_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $role->delete();

        return back();
    }
}
