<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PURPOSE: Single bibliographic reference belonging to an
 * article, with auto-extracted DOI, positional ordering,
 * citation-count tracking, and individual format export.
 *
 * SPECIFICATION: SPEC-15
 */
#[Fillable(['article_id', 'raw', 'doi', 'order', 'cited_count'])]
class Reference extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'cited_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $reference) {
            if ($reference->isDirty('raw') && ! $reference->isDirty('doi')) {
                $reference->doi = self::extractDoi($reference->raw);
            }
        });
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public static function extractDoi(string $text): ?string
    {
        if (preg_match('#\b(10\.\d{4,9}/[^\s,]+)#i', $text, $m)) {
            return rtrim($m[1], '.,;');
        }

        return null;
    }

    /**
     * Export this reference as a RIS record.
     */
    public function toRis(): string
    {
        $ris = "TY  - JOUR\n";
        if ($this->doi) {
            $ris .= "DO  - {$this->doi}\n";
        }
        $ris .= "N1  - {$this->raw}\n";
        $ris .= "ER  - \n";

        return $ris;
    }

    /**
     * Export this reference as a BibTeX entry.
     */
    public function toBibtex(): string
    {
        $key = $this->doi ? str_replace(['/', '.'], '_', $this->doi) : 'ref'.$this->id;

        $bib = "@article{{$key},\n";
        if ($this->doi) {
            $bib .= '  doi = {'.$this->escapeLatex($this->doi)."},\n";
        }
        $bib .= '  note = {'.$this->escapeLatex($this->raw)."},\n";
        $bib .= "}\n";

        return $bib;
    }

    private function escapeLatex(string $value): string
    {
        return str_replace(
            ['\\', '{', '}'],
            ['\\\\', '\\{', '\\}'],
            $value
        );
    }
}
