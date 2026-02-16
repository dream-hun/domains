<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\TldStatus;
use App\Enums\TldType;
use App\Models\Currency;
use App\Models\Domain;
use App\Models\Scopes\DomainScope;
use App\Models\Tld;
use App\Models\TldPricing;
use App\Models\User;
use App\Notifications\DomainImportedNotification;
use App\Services\Domain\NamecheapDomainService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FetchDomainsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public NamecheapDomainService $domainService
    ) {}

    public function handle(): void
    {
        $page = 1;
        $perPage = 100;
        $totalFetched = 0;
        $importedDomains = [];

        try {
            do {
                $result = $this->domainService->getDomainList($page, $perPage);
                Log::debug('Namecheap getDomainList result', ['page' => $page, 'result' => $result]);
                if (! $result['success'] || ! isset($result['domains']) || empty($result['domains'])) {
                    if ($page === 1) {
                        Log::warning('No domains fetched from Namecheap.', ['result' => $result]);
                    }

                    break;
                }

                $totalItems = (int) ($result['total'] ?? 0);

                $count = 0;
                foreach ($result['domains'] as $domainData) {
                    $registeredAt = isset($domainData['created_date'])
                        ? Date::parse($domainData['created_date'])
                        : now();

                    $expiresAt = isset($domainData['expiry_date'])
                        ? Date::parse($domainData['expiry_date'])
                        : now()->addYear();

                    $years = $registeredAt->diffInYears($expiresAt) ?: 1;
                    $name = $domainData['name'] ?? '';

                    // Normalize status coming from registrar
                    $rawStatus = isset($domainData['status']) ? (string) $domainData['status'] : '';
                    $status = $this->mapRegistrarStatus($rawStatus);

                    // Prefer lock status from the API detailed endpoint when possible
                    $isLocked = $domainData['locked'] ?? false;
                    try {
                        $lockResult = $this->domainService->getDomainLock($name);
                        if (! empty($lockResult['success']) && array_key_exists('locked', $lockResult)) {
                            $isLocked = (bool) $lockResult['locked'];
                        }
                    } catch (Exception $e) {
                        Log::warning('Failed to fetch lock status for domain', ['domain' => $name, 'error' => $e->getMessage()]);
                    }

                    // Optional: toggle lock state on the registrar if configured.
                    // Set 'toggle_locks' => true under services.namecheap in config/services.php to enable.
                    try {
                        $shouldToggle = config('services.namecheap.toggle_locks', false);
                        if ($shouldToggle) {
                            $newLockState = ! $isLocked; // flip
                            $toggleResult = $this->domainService->setDomainLock($name, $newLockState);
                            if (! empty($toggleResult['success'])) {
                                Log::info('Toggled lock state for domain', ['domain' => $name, 'locked' => $newLockState]);
                                $isLocked = $newLockState;
                            } else {
                                Log::warning('Failed to toggle lock state for domain', ['domain' => $name, 'result' => $toggleResult]);
                            }
                        }
                    } catch (Exception $e) {
                        Log::warning('Error while attempting to toggle domain lock', ['domain' => $name, 'error' => $e->getMessage()]);
                    }

                    $domain = Domain::query()->withoutGlobalScope(DomainScope::class)->updateOrCreate(
                        ['name' => $name],
                        [
                            'uuid' => $domainData['uuid'] ?? Str::uuid()->toString(),
                            'name' => $name,
                            'registered_at' => $registeredAt,
                            'expires_at' => $expiresAt,
                            'status' => $status,
                            'auto_renew' => $domainData['auto_renew'] ?? false,
                            'is_locked' => $isLocked,
                            'owner_id' => $domainData['owner_id'] ?? 1,
                            'years' => $years,
                            'auth_code' => $domainData['auth_code'] ?? null,
                            'is_premium' => $domainData['is_premium'] ?? false,
                            'tld_pricing_id' => $this->getOrCreateTldPricingId($domainData),
                        ]
                    );

                    // Add to imported domains list for notification
                    if ($domain->wasRecentlyCreated) {
                        $importedDomains[] = $domain;
                    }

                    $count++;
                }

                $totalFetched += $count;
                $page++;

                // Stop if we've fetched all reported items
                if ($totalItems > 0 && $totalFetched >= $totalItems) {
                    break;
                }

                // Continue if we got domains in this iteration
            } while ($count > 0);

            Log::info(sprintf('Fetched and upserted %d domains from Namecheap.', $totalFetched));

            // Send notifications for newly imported domains
            if ($importedDomains !== []) {
                $this->sendImportNotifications($importedDomains);
            }
        } catch (Exception $exception) {
            Log::error('Error fetching or saving domains: '.$exception->getMessage(), [
                'exception' => $exception,
            ]);
        }
    }

    /**
     * Map registrar status to internal normalized status
     */
    private function mapRegistrarStatus(string $rawStatus): string
    {
        $s = mb_strtolower(mb_trim($rawStatus));
        if ($s === '') {
            return 'active';
        }

        // Common Namecheap/Registrar statuses mapping
        if (str_contains($s, 'active') || str_contains($s, 'ok')) {
            return 'active';
        }

        if (str_contains($s, 'expired')) {
            return 'expired';
        }

        if (str_contains($s, 'redemption') || str_contains($s, 'redemptionperiod')) {
            return 'redemption';
        }

        if (str_contains($s, 'pending') || str_contains($s, 'pendingdelete') || str_contains($s, 'pendingtransfer')) {
            return 'pending';
        }

        if (str_contains($s, 'clienthold') || str_contains($s, 'serverhold')) {
            return 'on_hold';
        }

        return $s;
    }

    /**
     * Get or create a TldPricing id for the domain (by TLD suffix).
     */
    private function getOrCreateTldPricingId(array $domainData): int
    {
        $suffix = $this->extractTldSuffix((string) ($domainData['name'] ?? ''));

        $tld = Tld::query()->firstOrCreate(
            ['name' => $suffix],
            [
                'uuid' => (string) Str::uuid(),
                'name' => $suffix,
                'type' => TldType::International,
                'status' => TldStatus::Active,
            ]
        );

        $tldPricing = $tld->currentTldPricings()->with('currency')->first();
        if ($tldPricing instanceof TldPricing) {
            return $tldPricing->id;
        }

        $currency = Currency::getBaseCurrency();

        return TldPricing::query()->create([
            'uuid' => (string) Str::uuid(),
            'tld_id' => $tld->id,
            'currency_id' => $currency->id,
            'register_price' => 2000,
            'renew_price' => 2000,
            'transfer_price' => 1000,
            'redemption_price' => null,
            'is_current' => true,
            'effective_date' => now(),
        ])->id;
    }

    private function extractTldSuffix(string $domainName): string
    {
        $parts = explode('.', $domainName);

        return count($parts) > 1 ? '.'.end($parts) : '.com';
    }

    /**
     * Send notifications for imported domains
     */
    private function sendImportNotifications(array $importedDomains): void
    {
        // Get unique owners of the imported domains
        $ownerIds = collect($importedDomains)->pluck('owner_id')->unique()->filter();

        foreach ($ownerIds as $ownerId) {
            $user = User::query()->find($ownerId);
            if ($user) {
                // Get domains for this specific user
                $userDomains = collect($importedDomains)->where('owner_id', $ownerId);

                // Send notification for each domain or batch notification
                if ($userDomains->count() === 1) {
                    $user->notify(new DomainImportedNotification($userDomains->first(), 1));
                } else {
                    // Send batch notification for multiple domains
                    $firstDomain = $userDomains->first();
                    $user->notify(new DomainImportedNotification($firstDomain, $userDomains->count()));
                }

                Log::info('Domain import notification sent', [
                    'user_id' => $ownerId,
                    'domains_count' => $userDomains->count(),
                    'domains' => $userDomains->pluck('name')->toArray(),
                ]);
            }
        }
    }
}
