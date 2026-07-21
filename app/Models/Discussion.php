<?php

namespace App\Models;

use App\Enums\DiscussionScope;
use App\Policies\DiscussionPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PURPOSE: Threaded discussion message attached to an article,
 * supporting article-scoped and editorial-scoped threads,
 * optional review binding, and editor resolution.
 *
 * SPECIFICATION: SPEC-06/AC-5, SPEC-06/BR-1, SPEC-06/BR-4
 */
#[Fillable(['article_id', 'user_id', 'parent_id', 'review_id', 'scope', 'message', 'is_resolved', 'resolved_at', 'resolved_by'])]
class Discussion extends Model
{
    use HasFactory;

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    protected function casts(): array
    {
        return [
            'scope' => DiscussionScope::class,
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('is_resolved', false);
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function readUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'discussion_user_reads')
            ->withPivot('read_at');
    }

    public function readBy(User $user): void
    {
        $this->readUsers()->syncWithoutDetaching([$user->id => ['read_at' => now()]]);
    }

    public function isUnreadBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return ! $this->readUsers()
            ->wherePivot('user_id', $user->id)
            ->exists();
    }

    /**
     * Check whether a given user is allowed to see this discussion,
     * delegating to the DiscussionPolicy visibility matrix.
     */
    public function isVisibleTo(User $user, ?Article $article = null): bool
    {
        if ($article) {
            $this->setRelation('article', $article);
        }

        return app(DiscussionPolicy::class)->view($user, $this);
    }
}
