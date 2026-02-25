<?php

declare(strict_types=1);

namespace App\Actions\Admin\Court;

use App\Models\Court;

final class DeleteAction
{
    public function handle(Court $court): void
    {
        $court->delete();
    }
}
