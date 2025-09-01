<?php
declare(strict_types=1);
namespace App\Actions\Domains;
use App\Models\Domain;
use App\Services\Domain\DomainServiceInterface;
use Exception;
use Illuminate\Support\Facades\Log;

final readonly class UpdateDomainContactsAction
{
    public function __construct(
        private DomainServiceInterface $domainService
    ) {
    }

    public function handle(Domain $domain, array $contactInfo): array
    {
        try {
            $result = $this->domainService->updateDomainContacts($domain->name, $contactInfo);

            if ($result['success']) {
                // If contacts were successfully updated at the registrar, update our local records
                $domain->contacts()->sync([
                    $contactInfo['registrant']['contact_id'] => ['type' => 'registrant'],
                    $contactInfo['admin']['contact_id'] => ['type' => 'admin'],
                    $contactInfo['technical']['contact_id'] => ['type' => 'technical'],
                    $contactInfo['billing']['contact_id'] => ['type' => 'billing'],
                ]);
            }

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to update domain contacts', [
                'domain' => $domain->name,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update domain contacts: ' . $e->getMessage(),
            ];
        }
    }
}

