<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuditLogIndexRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

use function class_basename;

final class AuditLogController extends Controller
{
    public function __invoke(AuditLogIndexRequest $request): View
    {
        $search = mb_trim((string) $request->input('search'));
        $subjectType = $request->input('subject_type');
        $event = $request->input('event');

        $activities = Activity::query()
            ->with('causer')
            ->when($event, fn (Builder $query) => $query->where('event', $event))
            ->when($subjectType, fn (Builder $query) => $query->where('subject_type', $subjectType))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder->where('description', 'like', "%{$search}%")
                        ->orWhere('event', 'like', "%{$search}%")
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhere('subject_id', 'like', "%{$search}%")
                        ->orWhere('properties->request->ip', 'like', "%{$search}%")
                        ->orWhere('properties->request->url', 'like', "%{$search}%")
                        ->orWhereHasMorph('causer', [User::class], function (Builder $causer) use ($search): void {
                            $causer->where('email', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $eventOptions = $this->distinctValues('event');
        $subjectOptions = $this->distinctValues('subject_type')
            ->mapWithKeys(fn (string $type): array => [$type => class_basename($type)]);

        return view('admin.audit-logs.index', [
            'activities' => $activities,
            'filters' => [
                'search' => $search,
                'event' => $event,
                'subject_type' => $subjectType,
            ],
            'eventOptions' => $eventOptions,
            'subjectOptions' => $subjectOptions,
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    private function distinctValues(string $column): Collection
    {
        return Activity::query()
            ->select($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->filter(fn (?string $value): bool => filled($value))
            ->values();
    }
}
