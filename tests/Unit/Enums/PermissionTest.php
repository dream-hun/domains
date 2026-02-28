<?php

declare(strict_types=1);

use App\Enums\Permission;

test('permission label returns correct string for each case', function (Permission $permission, string $expectedLabel): void {
    expect($permission->label())->toBe($expectedLabel);
})->with([
    [Permission::ViewCourts, 'View Courts'],
    [Permission::CreateCourts, 'Create Courts'],
    [Permission::EditCourts, 'Edit Courts'],
    [Permission::DeleteCourts, 'Delete Courts'],
    [Permission::ViewGames, 'View Games'],
    [Permission::CreateGames, 'Create Games'],
    [Permission::EditGames, 'Edit Games'],
    [Permission::DeleteGames, 'Delete Games'],
    [Permission::ModerateGames, 'Moderate Games'],
    [Permission::ViewUsers, 'View Users'],
    [Permission::ManageUsers, 'Manage Users'],
]);

test('permission values returns all permission strings', function (): void {
    $values = Permission::values();

    expect($values)->toBeArray()
        ->toContain('view-courts')
        ->toContain('create-courts')
        ->toContain('edit-courts')
        ->toContain('delete-courts')
        ->toContain('view-games')
        ->toContain('create-games')
        ->toContain('edit-games')
        ->toContain('delete-games')
        ->toContain('moderate-games')
        ->toContain('view-users')
        ->toContain('manage-users')
        ->toHaveCount(11);
});
