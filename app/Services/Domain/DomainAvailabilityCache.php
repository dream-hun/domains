<?php

declare(strict_types=1);

namespace App\Services\Domain;

use Illuminate\Support\Facades\Cache;

/**
 * Caches domain availability results to avoid hammering external registrar APIs.
 *
 * External availability checks cost ~200-800ms each and Namecheap enforces
 * hourly API limits. A 5-minute cache window eliminates duplicate requests
 * from the same user session and concurrent users searching the same domain.
 */
final readonly class DomainAvailabilityCache
{
    private const int TTL_SECONDS = 300; // 5 minutes

    private const string KEY_PREFIX = 'domain:avail:';

    public function remember(array $domains, callable $resolver): array
    {
        $key = $this->cacheKey($domains);

        return Cache::remember($key, self::TTL_SECONDS, $resolver);
    }

    public function forget(array $domains): void
    {
        Cache::forget($this->cacheKey($domains));
    }

    /**
     * Cache a full domain search result (details + suggestions) keyed by the
     * searched domain name, so concurrent users hitting the same query share
     * a single upstream call.
     *
     * @return array{details: mixed, suggestions: array<int, mixed>, domainType: mixed, searchedDomain: string, error: string|null}
     */
    public function rememberSearch(string $domain, callable $resolver): array
    {
        $key = self::KEY_PREFIX.'search:'.md5(mb_strtolower(mb_trim($domain)));

        return Cache::remember($key, self::TTL_SECONDS, $resolver);
    }

    private function cacheKey(array $domains): string
    {
        sort($domains);

        return self::KEY_PREFIX.md5(implode(',', $domains));
    }
}
