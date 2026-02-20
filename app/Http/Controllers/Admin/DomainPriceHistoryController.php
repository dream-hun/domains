<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainPriceHistory;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Throwable;

final class DomainPriceHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'changed_by' => $request->integer('changed_by', 0),
            'date_from' => $request->string('date_from')->trim()->toString(),
            'date_to' => $request->string('date_to')->trim()->toString(),
        ];

        $perPage = $request->integer('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $historyQuery = DomainPriceHistory::query()
            ->with([
                'tldPricing.tld:id,name',
                'tldPricing.currency:id,code',
                'changedBy:id,first_name,last_name,email',
            ]);

        if ($filters['search'] !== '') {
            $searchTerm = '%'.$filters['search'].'%';

            $historyQuery->whereHas('tldPricing.tld', function (Builder $query) use ($searchTerm): void {
                $query->where('name', 'like', $searchTerm);
            });
        }

        if ($filters['changed_by'] > 0) {
            $historyQuery->where('changed_by', $filters['changed_by']);
        }

        if ($filters['date_from'] !== '') {
            $this->applyDateFilter($historyQuery, 'created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $this->applyDateFilter($historyQuery, 'created_at', '<=', $filters['date_to']);
        }

        $histories = $historyQuery
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $userOptions = User::query()
            ->select('id', 'first_name', 'last_name', 'email')
            ->whereIn('id', DomainPriceHistory::query()->distinct()->pluck('changed_by')->filter())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => mb_trim(($user->first_name ?? '').' '.($user->last_name ?? '')).' ('.$user->email.')',
                ];
            });

        return view('admin.domain-price-history.index', [
            'histories' => $histories,
            'filters' => array_merge($filters, ['per_page' => $perPage]),
            'userOptions' => $userOptions,
        ]);
    }

    private function applyDateFilter(Builder $query, string $column, string $operator, string $value): void
    {
        try {
            $date = Date::parse($value)->startOfDay();

            $query->whereDate($column, $operator, $date);
        } catch (Throwable) {
            // Ignore invalid date input
        }
    }
}
