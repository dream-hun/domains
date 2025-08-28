<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Services\Domain\InternationalDomainService;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel application
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test domains
$testDomains = [
    'example.com',
    'google.com',
    'probabilistically-existent domain123456789.com',
    'test-domain-availability.com',
];

// Create service instance
$domainService = app(InternationalDomainService::class);

echo "Testing domain availability checking...\n\n";

foreach ($testDomains as $domain) {
    echo "Checking domain: $domain\n";

    try {
        $result = $domainService->checkAvailability($domain);

        echo '  Available: '.($result['available'] ? 'Yes' : 'No')."\n";
        echo '  Reason: '.$result['reason']."\n\n";

        // Log the result
        Log::info('Domain availability test result', [
            'domain' => $domain,
            'available' => $result['available'],
            'reason' => $result['reason'],
        ]);
    } catch (Exception $e) {
        echo '  Error: '.$e->getMessage()."\n\n";

        // Log the error
        Log::error('Domain availability test error', [
            'domain' => $domain,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}

echo "Test completed.\n";
