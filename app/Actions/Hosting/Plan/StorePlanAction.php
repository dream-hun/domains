<?php

declare(strict_types=1);

namespace App\Actions\Hosting\Plan;

use App\Models\HostingPlan;
use Illuminate\Support\Str;

final class StorePlanAction
{
    /**
     * @param  array<string, mixed>  $validatedData
     */
    public function handle(array $validatedData): HostingPlan
    {
        $payload = $this->preparePayload($validatedData);

        return HostingPlan::query()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $validatedData
     * @return array<string, mixed>
     */
    private function preparePayload(array $validatedData): array
    {
        $payload = $validatedData;

        if (! isset($payload['uuid'])) {
            $payload['uuid'] = (string) Str::uuid();
        }

        if (! array_key_exists('sort_order', $payload) || $payload['sort_order'] === null || $payload['sort_order'] === '') {
            $maxSort = HostingPlan::query()->max('sort_order') ?? 0;
            $payload['sort_order'] = $maxSort + 1;
        }

        $payload['sort_order'] = (int) $payload['sort_order'];
        $payload['is_popular'] = (bool) ($payload['is_popular'] ?? false);

        return $payload;
    }
}
