<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TldStatus;
use App\Enums\TldType;
use App\Models\Tld;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tld>
 */
final class TldFactory extends Factory
{
    protected $model = Tld::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => '.'.fake()->unique()->lexify('???'),
            'type' => TldType::International,
            'status' => TldStatus::Active,
        ];
    }

    public function local(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => '.rw',
            'type' => TldType::Local,
        ]);
    }

    public function international(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => '.com',
            'type' => TldType::International,
        ]);
    }
}
