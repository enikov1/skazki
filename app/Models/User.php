<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'oauth_provider', 'oauth_id', 'avatar_path', 'profile_preferences'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'profile_preferences' => 'array'];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function readingHistories(): HasMany
    {
        return $this->hasMany(ReadingHistory::class);
    }

    public function consentEvents(): HasMany
    {
        return $this->hasMany(UserConsentEvent::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('slug', $role);
    }

    /** @param list<string> $roles */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->contains(fn (Role $role) => in_array($role->slug, $roles, true));
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()->whereHas('permissions', fn ($query) => $query->where('slug', $permission))->exists();
    }
}
