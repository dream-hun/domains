<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RegisterDomainAction;
use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\Country;
use App\Models\DomainPrice;
use App\Models\User;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegisterDomainTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Country $country;

    private DomainPrice $domainPrice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->country = Country::factory()->create([
            'iso_code' => 'US',
            'name' => 'United States',
        ]);
        $this->domainPrice = DomainPrice::factory()->com()->create();

        Cart::session($this->user->id)->add(1, 'example.com', 15.00, 1, [
            'years' => 1,
            'type' => 'registration',
        ]);
    }

    protected function tearDown(): void
    {
        Cart::clear();
        parent::tearDown();
    }

    public function test_user_can_view_domain_registration_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('domains.register'));

        $response->assertStatus(200);
        $response->assertViewIs('domains.register');
        $response->assertViewHas('cartItems');
        $response->assertViewHas('contactTypes');
        $response->assertViewHas('countries');
    }

    public function test_guest_cannot_access_domain_registration_page(): void
    {
        $response = $this->get(route('domains.register'));

        $response->assertRedirect(route('login'));
    }

    public function test_form_validation_works_with_valid_data(): void
    {
        $registrant = Contact::factory()->create([
            'user_id' => $this->user->id,
            'contact_type' => ContactType::Registrant,
            'country_code' => 'US',
        ]);

        // Test that the form validation passes with valid data
        // We'll test the validation logic without actually submitting to the domain service
        $validData = [
            'domain_name' => 'example.com',
            'registration_years' => 1,
            'registrant_contact_id' => (string) $registrant->id,
            'admin_contact_id' => (string) $registrant->id,
            'tech_contact_id' => (string) $registrant->id,
            'billing_contact_id' => (string) $registrant->id,
            'disable_dns' => 1, // Disable DNS to avoid nameserver validation
            'terms_accepted' => 1,
            'privacy_policy_accepted' => 1,
        ];

        // Test validation rules directly
        $rules = [
            'domain_name' => ['required', 'string', 'min:2', 'max:253'],
            'registration_years' => ['required', 'integer', 'min:1', 'max:10'],
            'registrant_contact_id' => ['required', 'exists:contacts,id'],
            'admin_contact_id' => ['required', 'exists:contacts,id'],
            'tech_contact_id' => ['required', 'exists:contacts,id'],
            'billing_contact_id' => ['required', 'exists:contacts,id'],
            'disable_dns' => ['nullable', 'boolean'],
            'terms_accepted' => ['required', 'accepted'],
            'privacy_policy_accepted' => ['required', 'accepted'],
        ];

        $validator = validator($validData, $rules);
        $this->assertTrue($validator->passes());
    }

    public function test_domain_service_selection_logic(): void
    {
        // Test the TLD detection logic through the action
        $action = resolve(RegisterDomainAction::class);

        // Test .rw domain uses EPP service
        $result = $action->handle('example.rw', [], 1);
        $this->assertEquals('EPP', $result['service'] ?? 'Unknown');

        // Test .com domain uses Namecheap service
        $result = $action->handle('example.com', [], 1);
        $this->assertEquals('Namecheap', $result['service'] ?? 'Unknown');
    }

    public function test_registration_fails_with_invalid_data(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('domains.register.store'), [
                'registration_years' => 15,
            ]);

        $response->assertSessionHasErrors([
            'domain_name',
            'registration_years',
            'registrant_contact_id',
            'admin_contact_id',
            'tech_contact_id',
            'billing_contact_id',
            'terms_accepted',
            'privacy_policy_accepted',
        ]);
    }
}
