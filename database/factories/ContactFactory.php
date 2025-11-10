<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Contact>
 */
final class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'contact_id' => 'CON'.mb_strtoupper(Str::random(8)),
            'contact_type' => $this->faker->randomElement(ContactType::cases())->value,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'title' => $this->faker->optional()->jobTitle(),
            'organization' => $this->faker->optional()->company(),
            'address_one' => $this->faker->streetAddress(),
            'address_two' => $this->faker->optional()->secondaryAddress(),
            'city' => $this->faker->city(),
            'state_province' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country_code' => $this->faker->countryCode(),
            'phone' => $this->faker->phoneNumber(),
            'phone_extension' => $this->faker->optional()->numerify('###'),
            'fax_number' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the contact is a registrant.
     */
    public function registrant(): static
    {
        return $this->state(fn (array $attributes): array => [
            'contact_type' => ContactType::Registrant->value,
        ]);
    }

    /**
     * Indicate that the contact is technical.
     */
    public function technical(): static
    {
        return $this->state(fn (array $attributes): array => [
            'contact_type' => ContactType::Technical->value,
        ]);
    }

    /**
     * Indicate that the contact is administrative.
     */
    public function administrative(): static
    {
        return $this->state(fn (array $attributes): array => [
            'contact_type' => ContactType::Administrative->value,
        ]);
    }

    /**
     * Indicate that the contact is billing.
     */
    public function billing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'contact_type' => ContactType::Billing->value,
        ]);
    }
}
