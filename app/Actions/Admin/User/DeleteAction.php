<?php

declare(strict_types=1);

namespace App\Actions\Admin\User;

use App\Models\User;

final class DeleteAction
{
    public function handle(User $user): void
    {
        $user->delete();
    }
}
