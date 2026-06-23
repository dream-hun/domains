<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DomainInvoiceGenerationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Generate renewal invoices for domains due for renewal')]
#[Signature('domains:generate-renewal-invoices
                            {--days=7 : Number of days before renewal to generate invoice}')]
final class GenerateDomainRenewalInvoicesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DomainInvoiceGenerationService $service): int
    {
        $days = (int) $this->option('days');

        $this->info(sprintf('Generating renewal invoices for domains renewing within %d days...', $days));

        $results = $service->generateRenewalInvoices($days);

        $this->info(sprintf('Dispatched %d renewal invoice jobs (skipped %d).', $results['dispatched'], $results['skipped']));

        return self::SUCCESS;
    }
}
