<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Country;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

#[Description('Importing countries from https://restcountries.com API')]
#[Signature('app:import-countries')]
final class ImportCountries extends Command
{
    /**
     * @throws ConnectionException
     */
    public function handle(): int
    {
        $this->info('Importing countries from https://restcountries.com API');
        $apiUrl = 'https://restcountries.com/v3.1/all?fields=name,cca2,cca3,capital,region,currencies,flags';
        $response = Http::timeout(30)->get($apiUrl);
        if ($response->failed()) {
            $this->error('Failed to retrieve Countries from '.$apiUrl);
            $this->error('Status code: '.$response->status());
            $this->error('Response body: '.$response->body());

            return 1;
        }

        $countries = $response->json();
        $skipped = 0;
        foreach ($countries as $country) {
            $isoCode = $country['cca3'] ?? null;
            $name = $country['name']['common'] ?? null;

            if (! $isoCode || ! $name) {
                $skipped++;

                continue;
            }

            // Extract currency information
            $currency = null;
            $symbol = null;
            if (isset($country['currencies']) && is_array($country['currencies'])) {
                $firstCurrency = array_values($country['currencies'])[0] ?? null;
                if ($firstCurrency) {
                    $currency = $firstCurrency['name'] ?? null;
                    $symbol = $firstCurrency['symbol'] ?? null;
                }
            }

            Country::query()->updateOrCreate(['iso_code' => $isoCode], [
                'iso_alpha2' => $country['cca2'] ?? null,
                'name' => $name,
                'capital' => isset($country['capital']) ? implode(', ', $country['capital']) : null,
                'region' => $country['region'] ?? null,
                'currency' => $currency,
                'symbol' => $symbol,
                'flag' => $country['flags']['svg'] ?? ($country['flags']['png'] ?? null),
            ]);
        }

        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} entries with missing iso_code or name.");
        }

        $this->info('✅ Countries imported successfully!');

        return 0;
    }
}
