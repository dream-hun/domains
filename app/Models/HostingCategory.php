<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Hosting\CategoryStatus;
use Database\Factories\HostingCategoryFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HostingCategory extends Model
{
    /** @use HasFactory<HostingCategoryFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => CategoryStatus::class,
    ];

    /**
     * Get active hosting categories (cached for 1 hour).
     */
    public static function getActiveCategories(): Collection
    {
        return Cache::remember('active_hosting_categories', 3600, fn () => self::query()->where('status', 'active')->orderBy('name')->get());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function plans(): HasMany
    {
        return $this->hasMany(HostingPlan::class, 'category_id');
    }

    public function allowsExternalDomain(): bool
    {
        return in_array($this->slug, [
            'shared-hosting',
            'reseller-hosting',
        ], true);
    }

    public function requiresDomain(): bool
    {
        return in_array($this->slug, [
            'shared-hosting',
            'reseller-hosting',
        ], true);
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function ($category): void {
            $category->uuid = (string) Str::uuid();
            $category->slug = Str::slug($category->name);
        });
    }
}
