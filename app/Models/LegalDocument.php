<?php

namespace App\Models;

use App\Support\LegalDefaults;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $slug
 * @property string $title
 * @property string|null $content_html Sanitized HTML. Null means the default text applies.
 * @property Carbon|null $published_at
 */
#[Fillable(['slug', 'title', 'content_html', 'updated_by', 'published_at'])]
class LegalDocument extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * The row for a slug, or an unsaved one holding the default text. Unknown slugs return null.
     */
    public static function forSlug(string $slug): ?self
    {
        if (! LegalDefaults::exists($slug)) {
            return null;
        }

        return self::query()->firstWhere('slug', $slug)
            ?? new self(['slug' => $slug, 'title' => LegalDefaults::title($slug)]);
    }

    public function isCustom(): bool
    {
        return $this->exists && filled($this->content_html);
    }
}
