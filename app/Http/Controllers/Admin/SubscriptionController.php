<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class SubscriptionController extends Controller
{
    public function index(Request $request): View|Factory
    {
        abort_if(Gate::denies('subscription_access'), 403);

        $filters = [
            'status' => $request->string('status')->trim()->toString(),
            'billing_cycle' => $request->string('billing_cycle')->trim()->toString(),
            'search' => $request->string('search')->trim()->toString(),
            'starts_from' => $request->string('starts_from')->trim()->toString(),
            'starts_to' => $request->string('starts_to')->trim()->toString(),
        ];

        $perPage = $request->integer('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $subscriptionsQuery = Subscription::query()
            ->with([
                'user:id,first_name,last_name,email',
                'plan:id,name',
                'planPrice:id,hosting_plan_id,billing_cycle,regular_price,renewal_price',
            ]);

        if ($filters['status'] !== '') {
            $subscriptionsQuery->where('status', $filters['status']);
        }

        if ($filters['billing_cycle'] !== '') {
            $subscriptionsQuery->where('billing_cycle', $filters['billing_cycle']);
        }

        if ($filters['search'] !== '') {
            $searchTerm = '%'.$filters['search'].'%';

            $subscriptionsQuery->where(function ($query) use ($searchTerm): void {
                $query->where('domain', 'like', $searchTerm)
                    ->orWhere('provider_resource_id', 'like', $searchTerm)
                    ->orWhereHas('user', function ($userQuery) use ($searchTerm): void {
                        $userQuery->where('first_name', 'like', $searchTerm)
                            ->orWhere('last_name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm);
                    });
            });
        }

        if ($filters['starts_from'] !== '') {
            $this->applyDateFilter($subscriptionsQuery, 'starts_at', '>=', $filters['starts_from']);
        }

        if ($filters['starts_to'] !== '') {
            $this->applyDateFilter($subscriptionsQuery, 'starts_at', '<=', $filters['starts_to']);
        }

        $subscriptions = $subscriptionsQuery
            ->latest('starts_at')
            ->paginate($perPage)
            ->withQueryString();

        $statsQuery = Subscription::query();
        $now = Date::now();

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('status', 'active')->count(),
            'expiring_soon' => (clone $statsQuery)
                ->where('status', 'active')
                ->whereBetween('expires_at', [$now, $now->copy()->addDays(30)])
                ->count(),
            'cancelled' => (clone $statsQuery)->whereNotNull('cancelled_at')->count(),
        ];

        $statusOptions = Subscription::query()
            ->select('status')
            ->distinct()
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status')
            ->map(static fn ($value): string => (string) $value)
            ->values();

        $billingCycleOptions = Subscription::query()
            ->select('billing_cycle')
            ->distinct()
            ->whereNotNull('billing_cycle')
            ->orderBy('billing_cycle')
            ->pluck('billing_cycle')
            ->map(static fn ($value): string => (string) $value)
            ->values();

        return view('admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'filters' => array_merge($filters, ['per_page' => $perPage]),
            'stats' => $stats,
            'statusOptions' => $statusOptions,
            'billingCycleOptions' => $billingCycleOptions,
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
