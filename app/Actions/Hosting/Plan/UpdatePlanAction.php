<?php

declare(strict_types=1);

namespace App\Actions\Hosting\Plan;

use App\Models\HostingPlan;

final class UpdatePlanAction
{
    /**
     * @param  array<string, mixed>  $validatedData
     */
    public function handle(HostingPlan $hostingPlan, array $validatedData): HostingPlan
    {
        $payload = $this->preparePayload($validatedData);

        $hostingPlan->update($payload);

        return $hostingPlan->refresh();
    }

    /**
     * @param  array<string, mixed>  $validatedData
     * @return array<string, mixed>
     */
    private function preparePayload(array $validatedData): array
    {
        $payload = $validatedData;

        if (array_key_exists('sort_order', $payload)) {
            if ($payload['sort_order'] === null || $payload['sort_order'] === '') {
                unset($payload['sort_order']);
            } else {
                $payload['sort_order'] = (int) $payload['sort_order'];
            }
        }

        $payload['is_popular'] = (bool) ($payload['is_popular'] ?? false);

        return $payload;
    }
}
