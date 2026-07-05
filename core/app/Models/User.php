<?php

namespace XBB\Models;

use Illuminate\Auth\MustVerifyEmail as ImplementMustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use XBB\Models\Properties\ResourceType;
use XBB\Models\Properties\UserStatus;

/**
 * @property UserStatus $status
 * @property int $quota Storage quota in bytes; -1 means unlimited.
 * @property-read string $avatar
 */
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    use HasApiTokens, HasFactory, ImplementMustVerifyEmail, Notifiable, PasskeyAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $appends = [
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'status' => UserStatus::class,
            'is_admin' => 'boolean',
        ];
    }

    /**
     * @return HasMany<\XBB\Models\Resource, $this>
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function getAvatarAttribute(): string
    {
        return 'https://www.gravatar.com/avatar/'.hash('sha256', strtolower($this->email)).'?d=robohash&r=x';
    }

    /**
     * Total bytes the user's uploaded media occupy (directories are containers, not media).
     */
    public function storageUsed(): int
    {
        return (int) $this->resources()
            ->where('type', '!=', ResourceType::DIRECTORY->value)
            ->sum('size');
    }

    /**
     * A negative (or unset) quota means the user may store an unlimited amount.
     */
    public function hasUnlimitedQuota(): bool
    {
        return ($this->quota ?? -1) < 0;
    }
}
