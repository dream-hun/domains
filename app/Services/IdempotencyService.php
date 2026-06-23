<?php

declare(strict_types=1);

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Prevents duplicate processing of payment webhooks.
 *
 * Stripe can deliver the same webhook event multiple times, and PawaPay
 * retries on non-2xx responses. Without deduplication, a payment that triggers
 * two near-simultaneous deliveries could create two domain registrations and
 * two invoices. This service uses Redis SETNX (Cache::add) — the first caller
 * wins; subsequent calls within the TTL window are no-ops.
 */
final readonly class IdempotencyService
{
    private const int DEFAULT_TTL_SECONDS = 86400; // 24 hours

    private const string KEY_PREFIX = 'idempotency:';

    /**
     * Execute $callback exactly once per $key within the TTL window.
     * Returns the cached result on subsequent calls.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T|null null when key already processed (duplicate)
     */
    public function once(string $key, Closure $callback, int $ttlSeconds = self::DEFAULT_TTL_SECONDS): mixed
    {
        $cacheKey = self::KEY_PREFIX.$key;

        if (! Cache::add($cacheKey, true, $ttlSeconds)) {
            Log::info('Idempotency: skipping duplicate event', ['key' => $key]);

            return null;
        }

        try {
            return $callback();
        } catch (Throwable $throwable) {
            // Release the lock so the event can be retried on genuine failures.
            Cache::forget($cacheKey);
            throw $throwable;
        }
    }

    public function hasProcessed(string $key): bool
    {
        return Cache::has(self::KEY_PREFIX.$key);
    }
}
