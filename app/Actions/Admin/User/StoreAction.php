<?php

declare(strict_types=1);

namespace App\Actions\Admin\User;

use App\Models\User;

final class StoreAction
{
    /** @param array{name: string, email: string, password: string, role: string} $data */
    public function handle(array $data): User
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $user->assignRole($data['role']);

        return $user;
    }
}
