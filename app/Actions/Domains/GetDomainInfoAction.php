<?php

declare(strict_types=1);

namespace App\Actions\Domains;

use App\Models\Domain;
use App\Services\Domain\NamecheapDomainService;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class GetDomainInfoAction
{
    public function __construct(
        private NamecheapDomainService $domainService
    ) {}

    public function handle(Domain $domain): array
    {
        try {
            $result = $this->domainService->getDomainInfo($domain->name);

            if ($result['success']) {
                // Update domain model with latest info from registrar
                $domain->update([
                    'expires_at' => $result['expiry_date'],
                    'is_locked' => true,
                    // 'whoisguard_enabled' => $result['whoisguard_enabled'],
                    'auto_renew' => $result['auto_renew'],
                ]);
            }

            return $result;
        } catch (Exception $exception) {
            Log::error('Failed to get domain info from registrar', [
                'domain' => $domain->name,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to get domain info: '.$exception->getMessage(),
            ];
        }
    }
}
