<?php

declare(strict_types=1);

namespace App\Actions\Tld;

use App\Models\Tld;

final class DeleteTldAction
{
    public function handle(Tld $tld): void
    {
        $tld->delete();
    }
}
