<?php

namespace App\Models;

use App\Exceptions\AuthorClaimFailedException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PURPOSE: Article authorship metadata with ORCID, SPIN, degree,
 * organisation, contact details and affiliation location, linked
 * to User and pivotable to Articles.
 */
#[Fillable(['user_id', 'full_name', 'first_name', 'last_name', 'degree', 'position', 'organization', 'bio', 'photo_path', 'orcid', 'email', 'spin_code', 'phone', 'country', 'city', 'author_id_elibrary', 'website', 'invitation_sent_at'])]
class Author extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'invitation_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Link this author record to a user account. Ownership is proven by
     * the contact email snapshot on the INVITING article's pivot matching
     * the user's verified email — any other article carrying the same
     * record does not count. The claiming user must not be the submitter
     * of that article (a submitter listing their own email as a coauthor
     * proves nothing), and an invitation must have actually been sent.
     * Re-links the EXISTING row — never creates a new one.
     * Idempotent when the record already belongs to the same user.
     */
    public function claimFor(User $user, Article $article): void
    {
        if ($this->user_id !== null && (int) $this->user_id !== (int) $user->id) {
            throw new AuthorClaimFailedException;
        }

        if (! $user->hasVerifiedEmail()) {
            throw new AuthorClaimFailedException;
        }

        if ($this->invitation_sent_at === null) {
            throw new AuthorClaimFailedException;
        }

        if ((int) $article->submitted_by === (int) $user->id) {
            throw new AuthorClaimFailedException;
        }

        $ownsRecord = $article->authors()
            ->whereKey($this->id)
            ->whereRaw('LOWER(article_author.email) = LOWER(?)', [$user->email])
            ->exists();

        if (! $ownsRecord) {
            throw new AuthorClaimFailedException;
        }

        $this->update([
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_author')
            ->withPivot('order', 'email', 'phone', 'country', 'city', 'website')
            ->orderByPivot('order');
    }

    public function displayEmail(): ?string
    {
        return $this->pivot?->email ?? $this->email;
    }

    public function displayPhone(): ?string
    {
        return $this->pivot?->phone ?? $this->phone;
    }

    public function displayWebsite(): ?string
    {
        return $this->pivot?->website ?? $this->website;
    }

    public function getNamePartsAttribute(): array
    {
        $first = $this->first_name;
        $last = $this->last_name;

        if (! filled($first) && ! filled($last) && filled($this->full_name)) {
            $parts = preg_split('/\s+/u', trim((string) $this->full_name)) ?: [];
            if (count($parts) >= 2) {
                $last = array_shift($parts);
                $first = implode(' ', $parts);
            } else {
                $last = $parts[0] ?? null;
            }
        }

        return [
            'given_name' => $first ? mb_trim((string) $first) : null,
            'surname' => $last ? mb_trim((string) $last) : (string) $this->full_name,
        ];
    }

    public function getGostNameAttribute(): string
    {
        $parts = explode(' ', $this->full_name);

        if (count($parts) >= 3) {
            return $parts[0].' '.mb_substr($parts[1], 0, 1).'.'.mb_substr($parts[2], 0, 1).'.';
        }

        if (count($parts) === 2) {
            return $parts[0].' '.mb_substr($parts[1], 0, 1).'.';
        }

        return $this->full_name;
    }
}
