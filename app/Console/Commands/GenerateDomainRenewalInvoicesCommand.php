<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DomainInvoiceGenerationService;
use Illuminate\Console\Command;

final class GenerateDomainRenewalInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:generate-renewal-invoices
                            {--days=7 : Number of days before renewal to generate invoice}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate renewal invoices for domains due for renewal';

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
