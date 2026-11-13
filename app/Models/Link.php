<?php

namespace App\Models;

use Database\Factories\LinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'url', 'description', 'is_active'])]
class Link extends Model
{
    /** @use HasFactory<LinkFactory> */
    use HasFactory;

    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Whether the stored URL is an absolute http(s) URL with a host, and therefore safe to
     * render as a clickable destination. Guards against bad data that bypassed validation.
     */
    public function hasSafeUrl(): bool
    {
        $parts = parse_url((string) $this->url);

        return is_array($parts)
            && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && filled($parts['host'] ?? null)
            && ! preg_match('/[\s\x00-\x1F\x7F]/', (string) $this->url);
    }

    /**
     * @return BelongsTo<LinkGroup, $this>
     */
    public function linkGroup(): BelongsTo
    {
        return $this->belongsTo(LinkGroup::class);
    }
}
