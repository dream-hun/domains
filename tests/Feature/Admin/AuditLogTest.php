<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::query()->firstOrCreate(['id' => 1], ['title' => 'Admin']);
    Role::query()->firstOrCreate(['id' => 2], ['title' => 'User']);
});

it('records audit entries when models are updated', function (): void {
    $admin = createAdminWithPermissions(['audit_log_access']);
    Activity::query()->truncate();

    $admin->update(['first_name' => 'Audited']);

    expect(
        Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $admin->id)
            ->where('event', 'updated')
            ->exists()
    )->toBeTrue();
});

it('allows authorized admins to view audit logs', function (): void {
    $admin = createAdminWithPermissions(['audit_log_access']);
    Activity::query()->truncate();

    activity()->causedBy($admin)->event('manual')->log('Test audit entry');

    actingAs($admin)
        ->get(route('admin.audit-logs.index'))
        ->assertOk()
        ->assertSee('Test audit entry');
});

it('forbids users without permission from viewing audit logs', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('admin.audit-logs.index'))
        ->assertForbidden();
});

function createAdminWithPermissions(array $permissions): User
{
    $admin = User::factory()->create();

    $permissionIds = collect($permissions)
        ->map(fn (string $title): int => Permission::query()->firstOrCreate(['title' => $title])->id)
        ->all();

    Role::query()->findOrFail(1)->permissions()->syncWithoutDetaching($permissionIds);
    $admin->roles()->sync([1]);

    return $admin;
}
