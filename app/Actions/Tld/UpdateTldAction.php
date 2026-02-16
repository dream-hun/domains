<?php

declare(strict_types=1);

namespace App\Actions\Tld;

use App\Models\Tld;

final class UpdateTldAction
{
    /**
     * @param  array{name: string, status: string, type: string}  $validated
     */
    public function handle(Tld $tld, array $validated): Tld
    {
        $tld->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'status' => $validated['status'],
        ]);

        return $tld;
    }
}
