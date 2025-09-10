<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DomainContactUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_domain_contact(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Create a domain
        $domain = Domain::factory()->create(['owner_id' => $user->id]);

        // Create contacts
        $registrantContact = Contact::factory()->create(['user_id' => $user->id]);
        $adminContact = Contact::factory()->create(['user_id' => $user->id]);

        // Attach initial contacts
        $domain->contacts()->attach($registrantContact->id, [
            'type' => 'registrant',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        // Update admin contact
        $response = $this->put(route('admin.domains.contacts.update', $domain->uuid), [
            'admin' => ['contact_id' => $adminContact->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the contact was updated in the database
        $this->assertDatabaseHas('domain_contacts', [
            'domain_id' => $domain->id,
            'contact_id' => $adminContact->id,
            'type' => 'admin',
        ]);
    }

    public function test_domain_edit_page_displays_contact_cards(): void
    {
        $user = User::factory()->create();
        $domain = Domain::factory()->create(['owner_id' => $user->id]);

        // Create and attach contacts
        $registrantContact = Contact::factory()->create(['user_id' => $user->id]);
        $domain->contacts()->attach($registrantContact->id, [
            'type' => 'registrant',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('admin.domains.edit', $domain->uuid));

        $response->assertOk();
        $response->assertSee('Registrant Contact');
        $response->assertSee('Admin Contact');
        $response->assertSee('Technical Contact');
        $response->assertSee('Billing Contact');
        $response->assertSee($registrantContact->full_name);
    }
}
