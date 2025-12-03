<?php

declare(strict_types=1);

namespace App\Services\Audit;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Stringable;
use UnitEnum;

use function activity;
use function class_basename;
use function collect;

final class ActivityLogger
{
    private const SENSITIVE_FIELDS = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    public function logModelEvent(string $event, Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $properties = array_filter([
            'attributes' => $this->sanitizeAttributes($model->attributesToArray()),
            'changes' => $event === 'updated'
                ? $this->sanitizeAttributes($model->getChanges())
                : null,
            'old' => in_array($event, ['updated', 'deleted', 'restored'], true)
                ? $this->sanitizeAttributes($model->getOriginal())
                : null,
            'request' => $this->requestContext(),
            'subject' => [
                'id' => $model->getKey(),
                'type' => $model::class,
                'label' => $this->subjectLabel($model),
            ],
        ], fn (?array $value): bool => $value !== null && $value !== []);

        activity()
            ->event($event)
            ->performedOn($model)
            ->causedBy($this->resolveCauser())
            ->withProperties($properties)
            ->log($this->describeModelEvent($event, $model));
    }

    public function logAuthEvent(string $event, ?Authenticatable $user, array $context = []): void
    {
        if (! config('activitylog.enabled', true)) {
            return;
        }

        $properties = array_filter([
            'guard' => $context['guard'] ?? null,
            'request' => $this->requestContext(),
            'context' => Arr::except($context, ['guard']),
        ], fn ($value): bool => $value !== null && $value !== []);

        activity()
            ->event($event)
            ->causedBy($user ?? $this->resolveCauser())
            ->withProperties($properties)
            ->log($this->describeAuthEvent($event, $user));
    }

    private function shouldSkip(Model $model): bool
    {
        if (! config('activitylog.enabled', true)) {
            return true;
        }

        if (! str_starts_with($model::class, 'App\\')) {
            return true;
        }

        return in_array($model::class, config('activitylog.ignored_models', []), true);
    }

    private function describeModelEvent(string $event, Model $model): string
    {
        $subject = $this->subjectLabel($model);

        return sprintf('%s %s', Str::headline($event), $subject);
    }

    private function describeAuthEvent(string $event, ?Authenticatable $user): string
    {
        $identifier = $user?->email ?? $user?->getAuthIdentifier() ?? 'guest';

        return sprintf('Authentication %s for %s', Str::headline($event), $identifier);
    }

    private function subjectLabel(Model $model): string
    {
        $label = class_basename($model);

        foreach (['name', 'title', 'email'] as $attribute) {
            if ($model->getAttribute($attribute)) {
                return sprintf('%s (%s)', $label, $model->getAttribute($attribute));
            }
        }

        return sprintf('%s (#%s)', $label, $model->getKey());
    }

    private function sanitizeAttributes(array $attributes): array
    {
        return collect($attributes)
            ->reject(fn ($value, string $key): bool => in_array($key, self::SENSITIVE_FIELDS, true))
            ->map(fn ($value): mixed => $this->normalizeValue($value))
            ->all();
    }

    private function requestContext(): array
    {
        if (! App::bound('request')) {
            return [];
        }

        $request = request();

        if (! $request instanceof Request) {
            return [];
        }

        return array_filter([
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function resolveCauser(): ?Authenticatable
    {
        return Auth::user() ?? (App::bound('request') ? request()->user() : null);
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if ($value instanceof Model) {
            return $value->getKey();
        }

        if (is_array($value)) {
            return collect($value)->map(fn ($inner): mixed => $this->normalizeValue($inner))->all();
        }

        return (string) $value;
    }
}
