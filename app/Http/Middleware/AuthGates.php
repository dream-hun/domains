<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

final class AuthGates
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        // Pre-load once per request so gate closures never fire extra queries.
        $user->loadMissing('roles');
        $userRoleIds = $user->roles->pluck('id')->all();

        $roles = Cache::remember('auth_gates_roles', 300, fn () => Role::with('permissions')->get());
        $permissionsArray = [];

        foreach ($roles as $role) {
            foreach ($role->permissions as $permission) {
                /** @var Permission $permission */
                $permissionsArray[$permission->title][] = $role->id;
            }
        }

        foreach ($permissionsArray as $title => $roleIds) {
            Gate::define($title, fn (User $u): bool => array_intersect($userRoleIds, $roleIds) !== []);
        }

        return $next($request);
    }
}
