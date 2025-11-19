<?php

declare(strict_types=1);

use App\Models\Permission;

it('can create permission', function () {
    $permission = Permission::query()->create(['title' => 'domain_show']);

    expect($permission->title)->toBe('domain_show');

    $this->assertDatabaseHas('permissions', [
        'title' => 'domain_show',
    ]);
});
