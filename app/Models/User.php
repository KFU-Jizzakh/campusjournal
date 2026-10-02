<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Exceptions\ReviewerRoleRemovalBlockedException;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
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

    /**
     * Display order of roles by descending importance.
     * Roles not listed here are rendered last, alphabetically.
     */
    private const ROLE_ORDER = [
        'admin',
        'editor-in-chief',
        'managing-editor',
        'section-editor',
        'content-manager',
        'reviewer',
        'author',
    ];

    /**
     * Badge colors keyed by role slug (palette of x-status-badge).
     * Unknown roles fall back to 'gray'.
     */
    private const ROLE_COLORS = [
        'admin' => 'danger',
        'editor-in-chief' => 'danger',
        'managing-editor' => 'info',
        'section-editor' => 'info',
        'content-manager' => 'warning',
        'reviewer' => 'gray',
        'author' => 'success',
    ];

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

    /**
     * PURPOSE: Localized display label for a role slug, shared by the
     * dashboard navigation badges and the Filament user table.
     *
     * SPECIFICATION: Falls back to a humanized slug when no translation exists.
     */
    public static function roleLabel(string $role): string
    {
        $label = __("roles.{$role}");

        return $label === "roles.{$role}" ? Str::headline($role) : $label;
    }

    /**
     * PURPOSE: Role badges (label + status-badge color) for the dashboard
     * navigation, ordered by descending role importance.
     *
     * SPECIFICATION: Returns an empty array when the user has no roles.
     */
    public function roleBadges(): array
    {
        return collect($this->getRoleNames())
            ->sortBy(fn (string $role) => array_search($role, self::ROLE_ORDER, strict: true) === false
                ? PHP_INT_MAX
                : array_search($role, self::ROLE_ORDER, strict: true))
            ->map(fn (string $role) => [
                'label' => self::roleLabel($role),
                'color' => self::ROLE_COLORS[$role] ?? 'gray',
            ])
            ->values()
            ->all();
    }

    /**
     * PURPOSE: Adds the reviewer role (self-registration, idempotent).
     */
    public function becomeReviewer(): void
    {
        $this->assignRole('reviewer');
    }

    /**
     * PURPOSE: Drops the reviewer role. Blocked while the user has
     * active review assignments (pending or in progress).
     *
     * SPECIFICATION: Throws ReviewerRoleRemovalBlockedException.
     */
    public function stopBeingReviewer(): void
    {
        $hasActiveReviews = $this->reviews()
            ->whereIn('status', [ReviewStatus::Pending, ReviewStatus::InProgress])
            ->exists();

        if ($hasActiveReviews) {
            throw new ReviewerRoleRemovalBlockedException;
        }

        $this->removeRole('reviewer');
    }

    /**
     * PURPOSE: Whether the reviewer self-registration setting is open.
     */
    public static function reviewerRegistrationOpen(): bool
    {
        return Setting::get('reviewer_self_registration', '1') === '1';
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

    /**
     * PURPOSE: Non-draft articles where the user is a credited coauthor
     * (via the Author profile) but not the submitter. Read-only in the
     * dashboard — the view page is only open to the submitter.
     */
    public function coauthoredArticles(): Builder
    {
        return Article::whereHas('authors', fn (Builder $query) => $query->where('user_id', $this->id))
            ->where('submitted_by', '!=', $this->id)
            ->where('status', '!=', ArticleStatus::Draft);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }
}
