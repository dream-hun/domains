<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Load balancer and uptime-monitor health probe.
 *
 * GET /health → 200 {"status":"ok",...} or 503 {"status":"degraded",...}
 *
 * Checked by the load balancer every 10s; a 503 removes the node from rotation.
 * Only checks core infrastructure — never performs writes or expensive queries.
 */
final class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $healthy = collect($checks)->every(fn (bool $ok): bool => $ok);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::selectOne('SELECT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            $key = 'health:probe:'.now()->timestamp;
            Cache::put($key, true, 5);

            return Cache::has($key);
        } catch (Throwable) {
            return false;
        }
    }
}
