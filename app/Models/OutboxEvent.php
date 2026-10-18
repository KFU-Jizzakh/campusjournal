<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * PURPOSE: Immutable append-only audit log recording every domain
 * event with actor, subject, event name, and payload snapshot.
 */
#[Fillable(['actor_id', 'name', 'subject_type', 'subject_id', 'payload'])]
class OutboxEvent extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function log(string $name, ?Model $subject = null, array $payload = [], ?User $actor = null): static
    {
        $actorId = $actor?->id ?? auth()->id();

        if ($actorId === null) {
            logger()->warning('OutboxEvent logged without actor', [
                'name' => $name,
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
            ]);
        }

        return static::create([
            'actor_id' => $actorId,
            'name' => $name,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'payload' => $payload ?: null,
        ]);
    }
}
