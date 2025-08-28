<?php

declare(strict_types=1);

namespace App\Actions\Domains;

use App\Models\Domain;
use App\Services\Domain\EppDomainService;
use App\Services\Domain\NamecheapDomainService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class TransferDomainAction
{
    public function __construct(
        private readonly EppDomainService $eppDomainService,
        private readonly NamecheapDomainService $namecheapDomainService
    ) {}

    public function handle(Domain $domain, array $transferData): array
    {
        try {
            return DB::transaction(function () use ($domain, $transferData): array {
                // Determine which service to use based on TLD
                $isLocalDomain = $this->isLocalDomain($domain->name);

                if ($isLocalDomain) {
                    return $this->transferWithEpp($domain, $transferData);
                }

                return $this->transferWithNamecheap($domain, $transferData);
            });
        } catch (Exception $e) {
            Log::error('Domain transfer failed', [
                'domain_id' => $domain->id,
                'domain_name' => $domain->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Domain transfer failed: '.$e->getMessage(),
            ];
        }
    }

    private function transferWithEpp(Domain $domain, array $transferData): array
    {
        Log::info('Transferring local domain via EPP', [
            'domain' => $domain->name,
        ]);

        $result = $this->eppDomainService->transferDomainRegistration(
            $domain->name,
            $transferData['auth_code'],
            $this->prepareContactInfo($transferData)
        );

        if ($result['success']) {
            $this->updateDomainRecord($domain, 'transfer_pending');
        }

        return $result;
    }

    private function transferWithNamecheap(Domain $domain, array $transferData): array
    {
        Log::info('Transferring international domain via Namecheap', [
            'domain' => $domain->name,
        ]);

        $result = $this->namecheapDomainService->transferDomainRegistration(
            $domain->name,
            $transferData['auth_code'],
            $this->prepareContactInfo($transferData)
        );

        if ($result['success']) {
            $this->updateDomainRecord($domain, 'transfer_pending');
        }

        return $result;
    }

    private function prepareContactInfo(array $transferData): array
    {
        // Convert contact IDs to contact information format expected by services
        $contactInfo = [];

        if (isset($transferData['registrant_contact_id'])) {
            $contactInfo['registrant'] = $transferData['registrant_contact_id'];
        }

        if (isset($transferData['admin_contact_id'])) {
            $contactInfo['admin'] = $transferData['admin_contact_id'];
        }

        if (isset($transferData['tech_contact_id'])) {
            $contactInfo['technical'] = $transferData['tech_contact_id'];
        }

        if (isset($transferData['billing_contact_id'])) {
            $contactInfo['billing'] = $transferData['billing_contact_id'];
        }

        if (isset($transferData['nameservers'])) {
            $contactInfo['nameservers'] = $transferData['nameservers'];
        }

        $contactInfo['user_id'] = auth()->id();

        return $contactInfo;
    }

    private function updateDomainRecord(Domain $domain, string $status): void
    {
        $domain->update([
            'status' => $status,
            'updated_at' => now(),
        ]);

        Log::info('Domain record updated after transfer initiation', [
            'domain_id' => $domain->id,
            'domain_name' => $domain->name,
            'new_status' => $status,
        ]);
    }

    private function isLocalDomain(string $domainName): bool
    {
        return str_ends_with(mb_strtolower($domainName), '.rw');
    }
}
