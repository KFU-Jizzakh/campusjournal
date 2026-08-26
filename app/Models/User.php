<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * PURPOSE: Authenticated user identity with Spatie roles/permissions,
 * Filament admin panel integration, and optional profile.
 */
#[Fillable(['email', 'password', 'email_verified_at', 'notification_preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin') || $this->hasPermissionTo('manage-content');
    }

    /**
     * PURPOSE: Queues the Russian verification email to the user's address.
     *
     * SPECIFICATION: SPEC-23/AC-1, AC-3, AC-5, AC-6, AC-8
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function getUserName(): string
    {
        return $this->profile?->full_name ?: $this->email;
    }

    public function getFilamentName(): string
    {
        return $this->profile?->full_name ?: $this->email;
    }

    public function getFullNameAttribute(): string
    {
        return $this->profile?->full_name ?: $this->email;
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function authorProfile(): HasOne
    {
        return $this->hasOne(Author::class);
    }

    public function submittedArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'submitted_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }
}
