<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Country;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class ImportCountriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-countries-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @throws ConnectionException
     */
    public function handle(): int
    {
        $this->info('Importing countries...');
        $apiUrl = 'https://restcountries.com/v3.1/all?fields=name,cca2,cca3,capital,region,flags';
        $response = Http::timeout(30)->get($apiUrl);
        if ($response->failed()) {
            $this->error('Failed to retrieve Countries from '.$apiUrl);
            $this->error('Status code: '.$response->status());
            $this->error('Response body: '.$response->body());

            return self::FAILURE;
        }

        $countries = $response->json();

        if (! is_array($countries)) {
            $this->error('Unexpected response format');

            return self::FAILURE;
        }

        /** @var array<int, array{
         *     cca3?: string,
         *     cca2?: string,
         *     name?: array{common?: string},
         *     capital?: list<string>,
         *     region?: string,
         *     flags?: array{svg?: string, png?: string}
         * }> $countries
         */
        foreach ($countries as $country) {
            Country::query()->updateOrCreate([
                'iso_code' => $country['cca3'] ?? null],
                ['iso_alpha2' => $country['cca2'] ?? null,
                    'name' => $country['name']['common'] ?? null,
                    'capital' => isset($country['capital']) ? implode(',', $country['capital']) : null,
                    'region' => $country['region'] ?? null,
                    'flag' => $country['flags']['svg'] ?? ($country['flags']['png'] ?? null),
                ]);
        }

        $this->info('Countries imported successfully!');

        return self::SUCCESS;
    }
}
