<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DomainSearchRequest;
use App\Models\Tld;
use App\Services\Domain\DomainServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class DomainSearchController extends Controller
{
    public function __invoke(
        DomainSearchRequest $request,
        DomainServiceInterface $domainService
    ): JsonResponse {
        $query = mb_strtolower(mb_trim($request->input('query')));
        $type = $request->input('type', 'all');

        $domainPrices = $this->getDomainPrices($type);
        $results = $this->buildResults($domainPrices, $query, $domainService);

        return response()->json([
            'success' => true,
            'query' => $query,
            'type' => $type,
            'results' => $results,
        ]);
    }

    /**
     * @return Collection<int, Tld>
     */
    private function getDomainPrices(string $type): Collection
    {
        $query = Tld::query()
            ->with(['tldPricings' => fn ($q) => $q->current()->with('currency')])
            ->where('status', 'active');

        if ($type === 'local') {
            $query->localTlds();
        } elseif ($type === 'international') {
            $query->internationalTlds();
        }

        return $query->get();
    }

    private function buildResults(
        Collection $domainPrices,
        string $query,
        DomainServiceInterface $domainService
    ): array {
        if ($domainPrices->isEmpty()) {
            return [];
        }

        $domains = $domainPrices->map(
            fn (Tld $price): string => $this->buildDomainName($query, $price->tld)
        )->all();

        $availability = $domainService->checkAvailability($domains);

        return $domainPrices
            ->map(fn (Tld $price): array => $this->transformDomainPrice(
                $price,
                $query,
                $availability
            ))
            ->values()
            ->all();
    }

    private function buildDomainName(string $query, string $tld): string
    {
        $normalizedTld = Str::start(mb_ltrim($tld, '.'), '.');

        return $query.$normalizedTld;
    }

    private function transformDomainPrice(
        Tld $price,
        string $query,
        array $availability
    ): array {
        $fullDomain = $this->buildDomainName($query, $price->tld);
        $availabilityResult = Arr::get($availability, $fullDomain);

        $available = false;
        $reason = null;

        if (is_object($availabilityResult)) {
            $available = (bool) ($availabilityResult->available ?? false);
            $reason = $availabilityResult->reason ?? $availabilityResult->error ?? null;
        } elseif (is_array($availabilityResult)) {
            $available = (bool) ($availabilityResult['available'] ?? false);
            $reason = $availabilityResult['reason'] ?? $availabilityResult['error'] ?? null;
        }

        return [
            'domain' => $fullDomain,
            'available' => $available,
            'type' => $price->isLocalTld() ? 'local' : 'international',
            'register_price' => $price->getPriceInBaseCurrency('register_price'),
            'renewal_price' => $price->getPriceInBaseCurrency('renewal_price'),
            'transfer_price' => $price->getPriceInBaseCurrency('transfer_price'),
            'reason' => $reason,
        ];
    }
}
