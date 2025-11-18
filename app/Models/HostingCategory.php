<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Hosting\CategoryStatus;
use Database\Factories\HostingCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function plans(): HasMany
    {
        return $this->hasMany(HostingPlan::class, 'category_id');
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
