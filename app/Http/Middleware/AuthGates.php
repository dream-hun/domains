<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class AuthGates
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = auth()->user();

        if (! $user) {
            return $next($request);
        }

        $roles = Role::with('permissions')->get();
        $permissionsArray = [];

        foreach ($roles as $role) {
            foreach ($role->permissions as $permission) {
                /** @var Permission $permission */
                $permissionsArray[$permission->title][] = $role->id;
            }
        }

        foreach ($permissionsArray as $title => $roles) {
            Gate::define($title, fn (User $user): bool => array_intersect($user->roles->pluck('id')->toArray(), $roles) !== []);
        }

        return $next($request);
    }
}
