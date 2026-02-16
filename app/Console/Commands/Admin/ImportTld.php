<?php

declare(strict_types=1);

namespace App\Console\Commands\Admin;

use App\Enums\TldStatus;
use App\Enums\TldType;
use App\Models\Tld;
use App\Services\Domain\NamecheapDomainService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

final class ImportTld extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-namecheap-tlds
                            {--activate : Mark imported TLDs as active (defaults to inactive for new ones)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all TLDs from Namecheap and store them locally';

    public function handle(NamecheapDomainService $service): int
    {
        $this->info('Fetching TLDs from Namecheap...');

        try {
            $response = $service->getTldList();
        } catch (Throwable $throwable) {
            $this->error('Failed to fetch TLD list: '.$throwable->getMessage());

            return 1;
        }

        if (! ($response['success'] ?? false)) {
            $this->error('Failed to fetch TLD list: '.($response['message'] ?? 'Unknown error'));

            return 1;
        }

        $rawTlds = array_map(
            fn (array $item): string => (string) ($item['name'] ?? ''),
            $response['tlds'] ?? []
        );
        $rawTlds = array_values(array_unique(array_filter($rawTlds)));

        if ($rawTlds === []) {
            $this->warn('No TLDs returned from Namecheap.');

            return 0;
        }

        $normalized = array_values(array_unique(array_filter(array_map(
            fn (string $tld): string => mb_ltrim(mb_strtolower(mb_trim($tld)), '.'),
            $rawTlds
        ))));
        $names = array_map(fn (string $tld): string => '.'.$tld, $normalized);

        $activate = $this->option('activate');
        $statusForNew = $activate ? TldStatus::Active : TldStatus::Inactive;

        $created = 0;
        $updated = 0;

        foreach ($names as $name) {
            $tld = Tld::query()->firstOrCreate(
                ['name' => $name],
                [
                    'uuid' => Str::uuid()->toString(),
                    'name' => $name,
                    'type' => TldType::International,
                    'status' => $statusForNew,
                ]
            );

            if ($tld->wasRecentlyCreated) {
                $created++;
            } elseif ($activate && $tld->status !== TldStatus::Active) {
                $tld->update(['status' => TldStatus::Active]);
                $updated++;
            }
        }

        $this->info('TLD import complete.');
        $this->line('Created: '.$created);
        $this->line('Updated (activated): '.$updated);
        $this->line('Total from Namecheap: '.count($names));

        return 0;
    }
}
