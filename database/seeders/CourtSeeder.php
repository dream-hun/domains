<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Court;
use App\Models\User;
use Illuminate\Database\Seeder;

final class CourtSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::role(Role::Administrator->value)->first();

        $courts = [
            // USA — 3 courts
            ['country' => 'United States', 'city' => 'Los Angeles', 'status' => 'active'],
            ['country' => 'United States', 'city' => 'New York', 'status' => 'pilot'],
            ['country' => 'United States', 'city' => 'Chicago', 'status' => 'priority'],

            // UK — 3 courts
            ['country' => 'United Kingdom', 'city' => 'London', 'status' => 'active'],
            ['country' => 'United Kingdom', 'city' => 'Manchester', 'status' => 'pilot'],
            ['country' => 'United Kingdom', 'city' => 'Birmingham', 'status' => 'priority'],

            // Nigeria — 3 courts
            ['country' => 'Nigeria', 'city' => 'Lagos', 'status' => 'active'],
            ['country' => 'Nigeria', 'city' => 'Abuja', 'status' => 'pilot'],
            ['country' => 'Nigeria', 'city' => 'Kano', 'status' => 'priority'],

            // Australia — 3 courts
            ['country' => 'Australia', 'city' => 'Sydney', 'status' => 'active'],
            ['country' => 'Australia', 'city' => 'Melbourne', 'status' => 'pilot'],
            ['country' => 'Australia', 'city' => 'Brisbane', 'status' => 'priority'],

            // Jamaica — 3 courts
            ['country' => 'Jamaica', 'city' => 'Kingston', 'status' => 'active'],
            ['country' => 'Jamaica', 'city' => 'Montego Bay', 'status' => 'pilot'],
            ['country' => 'Jamaica', 'city' => 'Spanish Town', 'status' => 'priority'],
        ];

        foreach ($courts as $data) {
            $factory = Court::factory()->{$data['status']}();

            $factory->create([
                'country' => $data['country'],
                'city' => $data['city'],
                'created_by' => $admin->id,
            ]);
        }
    }
}
