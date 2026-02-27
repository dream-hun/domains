<?php

declare(strict_types=1);

namespace App\Actions\Admin\User;

use App\Models\User;

final class UpdateAction
{
    /** @param array{name: string, email: string, role: string} $data */
    public function handle(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $user->syncRoles([$data['role']]);
        $user->refresh();

        return $user;
    }
}
