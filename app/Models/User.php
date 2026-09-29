<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'player',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
        ];
    }

    public function canManageContent(): bool
    {
        return $this->role->canManageContent();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * @return HasMany<MapNote, $this>
     */
    public function mapNotes(): HasMany
    {
        return $this->hasMany(MapNote::class);
    }

    /**
     * @return HasMany<RaidRoute, $this>
     */
    public function raidRoutes(): HasMany
    {
        return $this->hasMany(RaidRoute::class);
    }

    /**
     * Personal item tracking (need / have / keep / sell...).
     *
     * @return BelongsToMany<Item, $this>
     */
    public function trackedItems(): BelongsToMany
    {
        return $this->belongsToMany(Item::class)
            ->withPivot(['intent', 'quantity_needed', 'quantity_owned', 'is_favorite', 'note'])
            ->withTimestamps();
    }

    /**
     * Favorited / discovered markers.
     *
     * @return BelongsToMany<Marker, $this>
     */
    public function markerStates(): BelongsToMany
    {
        return $this->belongsToMany(Marker::class)
            ->withPivot(['is_favorite', 'discovered_at'])
            ->withTimestamps();
    }
}
