<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeInterface;
use Exception;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;

/**
 * @property-read Collection<int, Domain> $domains
 * @property-read Collection<int, Contact> $contacts
 * @property-read Collection<int, Order> $orders
 * @property-read Collection<int, Role> $roles
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $unreadNotifications
 * @property-read string $name
 */
final class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'password',
        'client_code',
        'stripe_id',
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
        $lastUser = self::query()->orderBy('id', 'desc')->first();

        if (! $lastUser) {
            return 'BLCL-000001';
        }

        preg_match('/\d+/', (string) $lastUser->client_code, $matches);

        throw_unless(isset($matches[0]), Exception::class, 'Invalid format for client_code');

        $number = (int) $matches[0] + 1;

        return 'BLCL-'.mb_str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return HasMany<Domain, static>
     */
    public function domains(): HasMany
    {
        // @phpstan-ignore-next-line
        return $this->hasMany(Domain::class);
    }

    /**
     * @return HasMany<Contact, static>
     */
    public function contacts(): HasMany
    {
        // @phpstan-ignore-next-line
        return $this->hasMany(Contact::class);
    }

    /**
     * @return HasMany<Order, static>
     */
    public function orders(): HasMany
    {
        // @phpstan-ignore-next-line
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<Subscription, static>
     */
    public function subscriptions(): HasMany
    {
        // @phpstan-ignore-next-line
        return $this->hasMany(Subscription::class);
    }

    public function isAdmin(): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn (Role $role): bool => (int) $role->id === 1);
        }

        return $this->roles()->where('roles.id', 1)->exists();
    }

    /**
     * @return BelongsToMany<Role, static>
     */
    public function roles(): BelongsToMany
    {
        // @phpstan-ignore-next-line
        return $this->belongsToMany(Role::class);
    }

    protected static function booted(): void
    {
        self::created(function (self $user): void {
            $registrationRole = config('panel.registration_default_role');

            if (in_array($registrationRole, [null, '', '0'], true)) {
                return;
            }

            $roleExists = Role::query()->whereKey($registrationRole)->exists();

            if (! $roleExists) {
                return;
            }

            if ($user->roles()->whereKey($registrationRole)->exists()) {
                return;
            }

            $user->roles()->attach($registrationRole);
        });
    }

    protected function getGravatarAttribute(): string
    {
        $email = $this->attributes['email'] ?? '';
        $email = md5(mb_strtolower(mb_trim((string) $email)));

        return 'https://www.gravatar.com/avatar/'.$email;
    }

    /**
     * Get the user's full name.
     */
    protected function getNameAttribute(): string
    {
        return mb_trim($this->first_name.' '.$this->last_name);
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
