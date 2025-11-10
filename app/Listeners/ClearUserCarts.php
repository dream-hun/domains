<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ExchangeRatesUpdated;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ClearUserCarts
{
    /**
     * Clear all user carts when exchange rates are updated
     */
    public function handle(ExchangeRatesUpdated $event): void
    {
        $sessions = DB::table('sessions')->select(['id', 'payload'])->get();
        $clearedCount = 0;

        foreach ($sessions as $session) {
            try {
                $payload = unserialize(base64_decode((string) $session->payload, true));
                if (! is_array($payload)) {
                    continue;
                }

                if (! isset($payload['cart'])) {
                    continue;
                }

                // Remove cart and related data
                unset($payload['cart'], $payload['coupon']);

                // Save updated session
                DB::table('sessions')
                    ->where('id', $session->id)
                    ->update(['payload' => base64_encode(serialize($payload))]);

                $clearedCount++;
            } catch (Exception) {
                // Skip invalid sessions
                continue;
            }
        }

        Log::info(sprintf('Cleared carts from %d user sessions after exchange rate update', $clearedCount), [
            'currencies_updated' => $event->updatedCount,
        ]);

        // Clear cart-related cache
        Cache::flush();
    }
}
