<?php

namespace App\Models;

use App\Enums\ArticleFileLicense;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * PURPOSE: Versioned copyright agreement template. Stores the
 * title, short summary for the submission form, full legal
 * text, and the license type (CC BY, etc.). Only one version is
 * active at a time; version number auto-increments for audit trail.
 *
 * SPECIFICATION: SPEC-14/AC-1, SPEC-14/AC-5, SPEC-14/AC-6, SPEC-14/BR-3, SPEC-14/BR-4, SPEC-14/BR-5
 */
#[Fillable(['version', 'title', 'short_text', 'full_text', 'license', 'is_active', 'published_at'])]
class CopyrightAgreement extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_at' => 'datetime',
            'license' => ArticleFileLicense::class,
        ];
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(ArticleAgreement::class, 'copyright_agreement_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public static function current(): ?self
    {
        return static::where('is_active', true)->first();
    }

    public function activate(): void
    {
        DB::transaction(function () {
            static::where('id', '!=', $this->id)->update(['is_active' => false]);

            $this->update([
                'is_active' => true,
                'published_at' => $this->published_at ?? now(),
            ]);
        });
    }

    public static function nextVersion(): int
    {
        return (int) static::max('version') + 1;
    }
}
