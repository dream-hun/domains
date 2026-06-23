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
        $clearedCount = 0;

        DB::table('sessions')
            ->select(['id', 'payload'])
            ->orderBy('id')
            ->chunk(500, function ($sessions) use (&$clearedCount): void {
                $updates = [];

                foreach ($sessions as $session) {
                    try {
                        $decoded = base64_decode((string) $session->payload, true);
                        if ($decoded === false) {
                            continue;
                        }

                        $payload = unserialize($decoded, ['allowed_classes' => false]);
                        if (! is_array($payload)) {
                            continue;
                        }
                        if (! isset($payload['cart']) && ! isset($payload['coupon'])) {
                            continue;
                        }

                        unset($payload['cart'], $payload['coupon']);

                        $updates[] = [
                            'id' => $session->id,
                            'payload' => base64_encode(serialize($payload)),
                        ];
                        $clearedCount++;
                    } catch (Exception) {
                        continue;
                    }
                }

                if ($updates !== []) {
                    DB::table('sessions')->upsert($updates, ['id'], ['payload']);
                }
            });

        Log::info(sprintf('Cleared carts from %d user sessions after exchange rate update', $clearedCount), [
            'currencies_updated' => $event->updatedCount,
        ]);

        // Clear currency-related caches (exchange rate processor clears rate caches separately)
        Cache::forget('active_currencies');
        Cache::forget('base_currency');
        Cache::forget('app_settings');
    }
}
