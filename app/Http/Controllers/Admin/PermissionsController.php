<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use App\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class PermissionsController extends Controller
{
    public function index(): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
    {
        abort_if(Gate::denies('permission_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $permissions = Permission::all();

        return view('admin.permissions.index', ['permissions' => $permissions]);
    }

    public function create(): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
    {
        abort_if(Gate::denies('permission_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.permissions.create');
    }

    public function store(StorePermissionRequest $request)
    {
        Permission::create($request->all());

        return redirect()->route('admin.permissions.index');
    }

    public function edit(Permission $permission): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
    {
        abort_if(Gate::denies('permission_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.permissions.edit', ['permission' => $permission]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        $permission->update($request->all());

        return redirect()->route('admin.permissions.index');
    }

    public function show(Permission $permission): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
    {
        abort_if(Gate::denies('permission_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.permissions.show', ['permission' => $permission]);
    }

    public function destroy(Permission $permission): \Illuminate\Http\RedirectResponse
    {
        abort_if(Gate::denies('permission_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $permission->delete();

        return back();
    }
}
