<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Role extends Model
{
    protected $table = 'roles';

    protected $guarded = [];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
