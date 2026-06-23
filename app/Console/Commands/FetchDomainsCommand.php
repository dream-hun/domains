<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\FetchDomainsJob;
use App\Services\Domain\NamecheapDomainService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Fetch domains from Namecheap')]
#[Signature('domains:fetch')]
final class FetchDomainsCommand extends Command
{
    public function handle(NamecheapDomainService $domainService): void
    {
        $this->info('Starting domain fetch...');

        $job = new FetchDomainsJob($domainService);
        dispatch_sync($job);

        $this->info('Domain fetch completed!');
    }
}
