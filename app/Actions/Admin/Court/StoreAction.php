<?php

declare(strict_types=1);

namespace App\Actions\Admin\Court;

use App\Models\Court;
use App\Models\User;
use Illuminate\Support\Str;

final class StoreAction
{
    /** @param array<string, mixed> $data */
    public function handle(array $data, User $user): Court
    {
        return Court::query()->create(array_merge($data, [
            'uuid' => Str::uuid(),
            'created_by' => $user->id,
        ]));
    }
}
