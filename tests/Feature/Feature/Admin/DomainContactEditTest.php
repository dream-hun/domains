<?php

declare(strict_types=1);

use App\Models\Domain;
use App\Models\User;
use Database\Seeders\PermissionSeeder;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

test('edit contact page renders without error when domain has no contact of the requested type', function (): void {
    $owner = User::factory()->create();
    $domain = Domain::factory()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)
        ->get(route('admin.domains.contacts.edit', [$domain->uuid, 'registrant']));

    $response->assertOk();
    $response->assertViewHas('currentContact', null);
});

test('edit contact page renders for each valid contact type with no contacts assigned', function (string $type): void {
    $owner = User::factory()->create();
    $domain = Domain::factory()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)
        ->get(route('admin.domains.contacts.edit', [$domain->uuid, $type]));

    $response->assertOk();
})->with(['registrant', 'admin', 'technical', 'billing']);
