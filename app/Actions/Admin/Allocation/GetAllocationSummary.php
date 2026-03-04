<?php

declare(strict_types=1);

namespace App\Actions\Admin\Allocation;

use App\Models\Allocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class GetAllocationSummary
{
    /**
     * @param  array{from?: string, to?: string, format?: string, player_id?: int}  $filters
     * @return array{total: float, insurance: float, savings: float, pathway: float, administration: float, count: int}
     */
    public function handle(array $filters = []): array
    {
        $query = Allocation::query()
            ->select([
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total'),
                DB::raw('SUM(insurance_amount) as insurance'),
                DB::raw('SUM(savings_amount) as savings'),
                DB::raw('SUM(pathway_amount) as pathway'),
                DB::raw('SUM(administration_amount) as administration'),
            ])
            ->when(
                isset($filters['from']),
                fn (Builder $q) => $q->where('allocations.created_at', '>=', $filters['from'])
            )
            ->when(
                isset($filters['to']),
                fn (Builder $q) => $q->where('allocations.created_at', '<=', $filters['to'])
            )
            ->when(
                isset($filters['player_id']),
                fn (Builder $q) => $q->where('player_id', $filters['player_id'])
            )
            ->when(
                isset($filters['format']),
                fn (Builder $q) => $q->whereHas(
                    'game',
                    fn (Builder $gq) => $gq->where('format', $filters['format'])
                )
            );

        $result = $query->first();

        return [
            'total' => (float) ($result?->total ?? 0),
            'insurance' => (float) ($result?->insurance ?? 0),
            'savings' => (float) ($result?->savings ?? 0),
            'pathway' => (float) ($result?->pathway ?? 0),
            'administration' => (float) ($result?->administration ?? 0),
            'count' => (int) ($result?->count ?? 0),
        ];
    }
}
