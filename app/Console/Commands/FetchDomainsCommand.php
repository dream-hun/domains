<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\FetchDomainsJob;
use App\Services\Domain\NamecheapDomainService;
use Illuminate\Console\Command;

final class FetchDomainsCommand extends Command
{
    protected $signature = 'domains:fetch';

    protected $description = 'Fetch domains from Namecheap';

    public function handle(NamecheapDomainService $domainService): void
    {
        $this->info('Starting domain fetch...');

        $job = new FetchDomainsJob($domainService);
        dispatch_sync($job);

        $this->info('Domain fetch completed!');
    }
}
