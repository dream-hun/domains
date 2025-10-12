<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeInterface;
use Exception;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

final class User extends Authenticatable implements MustVerifyEmail
{
    use Billable, HasFactory, Notifiable;

    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'password',
        'client_code',
        'preferred_currency',
    ];

    protected $hidden = [
        'client_code',
        'password',
        'remember_token',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * @throws Exception
     */
    public static function generateCustomerNumber(): string
    {
        $lastUser = self::orderBy('id', 'desc')->first();

        if (! $lastUser) {
            return 'BLCL-000001';
        }

        preg_match('/\d+/', $lastUser->client_code, $matches);

        if (! isset($matches[0])) {
            throw new Exception('Invalid format for client_code');
        }

        $number = (int) $matches[0] + 1;

        return 'BLCL-'.mb_str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isAdmin(): bool
    {
        return $this->roles()->where('roles.id', 1)->exists();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function getGravatarAttribute(): string
    {
        $emailStr = is_null($this->email) ? '' : (string) $this->email;
        $email = md5(mb_strtolower(mb_trim($emailStr)));

        return "https://www.gravatar.com/avatar/$email";
    }

    /**
     * Get the user's full name.
     */
    public function getNameAttribute(): string
    {
        return mb_trim($this->first_name.' '.$this->last_name);
    }

    protected static function booted(): void
    {
        self::created(function (self $user): void {
            $registrationRole = config('panel.registration_default_role');
            if (! $user->roles()->get()->contains($registrationRole)) {
                $user->roles()->attach($registrationRole);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
