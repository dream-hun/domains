<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Country;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class ImportCountries extends Command
{
    protected $signature = 'app:import-countries';

    protected $description = 'Importing countries from https://restcountries.com API';

    /**
     * @throws ConnectionException
     */
    public function handle(): int
    {
        $this->info('Importing countries from https://restcountries.com API');
        $apiUrl = 'https://restcountries.com/v3.1/all?fields=name,cca3,capital,region,currencies,flags';
        $response = Http::timeout(30)->get($apiUrl);
        if ($response->failed()) {
            $this->error('Failed to retrieve Countries from '.$apiUrl);
            $this->error('Status code: '.$response->status());
            $this->error('Response body: '.$response->body());

            return 1;
        }

        $countries = $response->json();
        foreach ($countries as $country) {
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

            Country::query()->updateOrCreate(['iso_code' => $country['cca3'] ?? null], [
                'name' => $country['name']['common'] ?? null,
                'capital' => isset($country['capital']) ? implode(', ', $country['capital']) : null,
                'region' => $country['region'] ?? null,
                'currency' => $currency,
                'symbol' => $symbol,
                'flag' => $country['flags']['svg'] ?? ($country['flags']['png'] ?? null),
            ]);
        }

        $this->info('✅ Countries imported successfully!');

        return 0;
    }
}
