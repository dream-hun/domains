<?php

declare(strict_types=1);

namespace App\Services\Domain;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Circuit breaker for external registrar API calls (EPP, Namecheap).
 *
 * Without a circuit breaker, every user request waits for the full HTTP
 * timeout (~30s) when a registrar goes down, exhausting PHP-FPM workers
 * under load. After $threshold consecutive failures the circuit opens and
 * fast-fails for $resetTimeoutSeconds, then half-opens to probe recovery.
 *
 * Usage:
 *   $result = $circuitBreaker->call('namecheap', fn() => $this->http->get(...));
 */
final readonly class CircuitBreaker
{
    private const string KEY_PREFIX = 'circuit:';

    private const string STATE_OPEN = 'open';

    private const string STATE_HALF_OPEN = 'half_open';

    private const string STATE_CLOSED = 'closed';

    public function __construct(
        private int $threshold = 5,
        private int $resetTimeoutSeconds = 60,
    ) {}

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     *
     * @throws RuntimeException when circuit is open
     */
    public function call(string $service, Closure $callback): mixed
    {
        $state = $this->getState($service);

        if ($state === self::STATE_OPEN) {
            Log::warning('CircuitBreaker: fast-failing open circuit', ['service' => $service]);
            throw new RuntimeException(sprintf("Service '%s' is temporarily unavailable. Please try again shortly.", $service));
        }

        try {
            $result = $callback();
            $this->onSuccess($service);

            return $result;
        } catch (Throwable $throwable) {
            $this->onFailure($service);
            throw $throwable;
        }
    }

    public function isOpen(string $service): bool
    {
        return $this->getState($service) === self::STATE_OPEN;
    }

    private function getState(string $service): string
    {
        $openUntil = Cache::get($this->openUntilKey($service));

        if ($openUntil !== null) {
            return now()->timestamp < $openUntil ? self::STATE_OPEN : self::STATE_HALF_OPEN;
        }

        $failures = (int) Cache::get($this->failureKey($service), 0);

        return $failures >= $this->threshold ? self::STATE_OPEN : self::STATE_CLOSED;
    }

    private function onSuccess(string $service): void
    {
        Cache::forget($this->failureKey($service));
        Cache::forget($this->openUntilKey($service));
    }

    private function onFailure(string $service): void
    {
        $failures = (int) Cache::increment($this->failureKey($service));
        Cache::put($this->failureKey($service), $failures, $this->resetTimeoutSeconds * 2);

        if ($failures >= $this->threshold) {
            $resetAt = now()->addSeconds($this->resetTimeoutSeconds)->timestamp;
            Cache::put($this->openUntilKey($service), $resetAt, $this->resetTimeoutSeconds);

            Log::error('CircuitBreaker: circuit opened', [
                'service' => $service,
                'failures' => $failures,
                'reset_in_seconds' => $this->resetTimeoutSeconds,
            ]);
        }
    }

    private function failureKey(string $service): string
    {
        return self::KEY_PREFIX.$service.':failures';
    }

    private function openUntilKey(string $service): string
    {
        return self::KEY_PREFIX.$service.':open_until';
    }
}
