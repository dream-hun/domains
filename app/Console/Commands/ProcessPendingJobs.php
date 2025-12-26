<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ProcessPendingJobs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:process-pending
                            {--queue=default : The queue to process}
                            {--max=100 : Maximum number of jobs to process}
                            {--timeout=60 : The number of seconds a child process can run}';

    /**
     * The console command description.
     */
    protected $description = 'Process all pending jobs in the queue';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $queue = $this->option('queue');
        $max = (int) $this->option('max');
        $timeout = (int) $this->option('timeout');

        $this->info("Processing pending jobs from queue: {$queue}");

        // Count pending jobs
        $pendingCount = DB::table('jobs')
            ->where('queue', $queue)
            ->whereNull('reserved_at')
            ->where('available_at', '<=', now()->timestamp)
            ->count();

        if ($pendingCount === 0) {
            $this->info('No pending jobs found in the queue.');

            return self::SUCCESS;
        }

        $this->info("Found {$pendingCount} pending job(s). Processing up to {$max} job(s)...");

        $processed = 0;
        $failed = 0;

        // Process jobs one by one
        for ($i = 0; $i < min($max, $pendingCount); $i++) {
            try {
                $this->call('queue:work', [
                    '--queue' => $queue,
                    '--once' => true,
                    '--timeout' => $timeout,
                    '--no-interaction' => true,
                ]);

                $processed++;
                $this->line('Processed job '.($processed)." of {$pendingCount}");

            } catch (Exception $e) {
                $failed++;
                $this->error('Failed to process job: '.$e->getMessage());
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->info("  Processed: {$processed}");
        if ($failed > 0) {
            $this->warn("  Failed: {$failed}");
        }

        // Check if there are still pending jobs
        $remaining = DB::table('jobs')
            ->where('queue', $queue)
            ->whereNull('reserved_at')
            ->where('available_at', '<=', now()->timestamp)
            ->count();

        if ($remaining > 0) {
            $this->warn("  Remaining: {$remaining} job(s) still pending. Run this command again to process them.");
        } else {
            $this->info('  All pending jobs have been processed.');
        }

        return self::SUCCESS;
    }
}
