<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Domain;
use App\Models\DomainPrice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Domain\DomainServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Ensure roles exist
    $adminRole = Role::query()->firstOrCreate(['id' => 1], ['title' => 'Admin']);
    Role::query()->firstOrCreate(['id' => 2], ['title' => 'User']);

    // Create permissions and attach to admin role
    $permissionIds = [
        Permission::query()->firstOrCreate(['title' => 'domain_show'])->id,
        Permission::query()->firstOrCreate(['title' => 'domain_edit'])->id,
    ];

    $adminRole->permissions()->sync($permissionIds);

    $this->user = User::factory()->create(['email' => 'test.user@example.com']);
    $this->user->roles()->sync([$adminRole->id]);

    // Create a domain price first
    $this->domainPrice = DomainPrice::factory()->create([
        'tld' => '.com',
        'register_price' => 1000,
        'renewal_price' => 1000,
    ]);

    $this->domain = Domain::factory()->create([
        'owner_id' => $this->user->id,
        'domain_price_id' => $this->domainPrice->id,
        'uuid' => (string) Str::uuid(),
    ]);

    $this->contacts = Contact::factory()->count(4)->create(['user_id' => $this->user->id]);
});

test('user can refresh domain info', function (): void {
    $mockDomainService = mock(DomainServiceInterface::class);
    $mockDomainService->shouldReceive('getDomainInfo')
        ->once()
        ->andReturn([
            'success' => true,
            'domain' => $this->domain->name,
            'expiry_date' => '2024-12-31',
            'locked' => true,
            'auto_renew' => false,
        ]);

    $this->app->instance(DomainServiceInterface::class, $mockDomainService);

    $response = $this->actingAs($this->user)
        ->post(route('admin.domains.refresh-info', ['domain' => $this->domain]));

    $response->assertRedirect()
        ->assertSessionHas('success');

    $this->domain->refresh();
    expect($this->domain->is_locked)->toBeTrue();
    expect($this->domain->auto_renew)->toBeFalse();
    expect($this->domain->expires_at->format('Y-m-d'))->toBe('2024-12-31');
});

test('user can update domain contacts', function (): void {
    $contactData = [
        'registrant' => ['contact_id' => $this->contacts[0]->id],
        'admin' => ['contact_id' => $this->contacts[1]->id],
        'technical' => ['contact_id' => $this->contacts[2]->id],
        'billing' => ['contact_id' => $this->contacts[3]->id],
    ];

    $mockDomainService = mock(DomainServiceInterface::class);
    $mockDomainService->shouldReceive('updateDomainContacts')
        ->once()
        ->andReturn([
            'success' => true,
            'message' => 'Domain contacts updated successfully',
        ]);

    $this->app->instance(DomainServiceInterface::class, $mockDomainService);

    $response = $this->actingAs($this->user)
        ->put(route('admin.domains.contacts.update', $this->domain->uuid), $contactData);

    $response->assertRedirect()
        ->assertSessionHas('success');

    // Verify contacts were synced in the database
    $this->domain->refresh();
    expect($this->domain->contacts)->toHaveCount(4);
    expect($this->domain->contacts->pluck('id')->toArray())
        ->toContain($this->contacts[0]->id)
        ->toContain($this->contacts[1]->id)
        ->toContain($this->contacts[2]->id)
        ->toContain($this->contacts[3]->id);
});

test('unauthorized user cannot access domain info', function (): void {
    $unauthorizedUser = User::factory()->create(['email' => 'unauthorized@example.com']);

    $response = $this->actingAs($unauthorizedUser)
        ->post(route('admin.domains.refresh-info', ['domain' => $this->domain]));

    $response->assertForbidden();
});

test('unauthorized user cannot update domain contacts', function (): void {
    $unauthorizedUser = User::factory()->create(['email' => 'unauthorized2@example.com']);

    $response = $this->actingAs($unauthorizedUser)
        ->put(route('admin.domains.contacts.update', $this->domain->uuid), []);

    $response->assertForbidden();
});
