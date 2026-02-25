<?php

declare(strict_types=1);

namespace App\Actions\Admin\Court;

use App\Models\Court;

final class UpdateAction
{
    /** @param array<string, mixed> $data */
    public function handle(Court $court, array $data): Court
    {
        $court->update($data);
        $court->refresh();

        return $court;
    }
}
