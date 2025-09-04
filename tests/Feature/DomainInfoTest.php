<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Domain;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('can view domain info with contacts', function (): void {
    $user = User::factory()->create();

    // Create and assign necessary permissions
    $permission = Permission::create(['title' => 'domain_show']);
    $role = Role::create(['title' => 'Admin']);
    $role->permissions()->attach($permission->id);
    $user->roles()->attach($role->id);

    $domain = Domain::factory()->create([
        'owner_id' => $user->id,
        'uuid' => Str::uuid(),
    ]);
    $contact = Contact::factory()->create(['user_id' => $user->id]);

    $domain->contacts()->attach($contact->id);

    $this->actingAs($user)
        ->get(route('admin.domains.info', $domain->uuid))
        ->assertOk()
        ->assertViewIs('admin.domains.domainInfo')
        ->assertViewHas('domainInfo');
});
