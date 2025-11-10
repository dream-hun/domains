<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Nameserver extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function domains(): BelongsToMany
    {
        return $this->belongsToMany(Domain::class, 'domain_nameservers', 'nameserver_id', 'domain_id');
    }
}
