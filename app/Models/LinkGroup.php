<?php

namespace App\Models;

use Database\Factories\LinkGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * A user's collection of links, published at the permanent public URL /p/{slug}.
 *
 * The slug is generated once at creation and is immutable. Soft-deleted groups keep
 * their slug reserved, and their links are retained so a restore brings them back.
 */
#[Fillable(['name', 'description', 'avatar_path', 'theme', 'appearance', 'is_active'])]
class LinkGroup extends Model
{
    /** @use HasFactory<LinkGroupFactory> */
    use HasFactory, SoftDeletes;

    public const DEFAULT_THEME = 'default';

    public const SLUG_MAX_LENGTH = 64;

    public const SLUG_CREATE_ATTEMPTS = 3;

    protected $attributes = [
        'theme' => self::DEFAULT_THEME,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (LinkGroup $group) {
            $group->slug = filled($group->slug)
                ? Str::slug($group->slug)
                : static::generateUniqueSlug($group->name);
        });

        static::updating(function (LinkGroup $group) {
            if ($group->isDirty('slug')) {
                throw new LogicException('A link group slug cannot be changed after creation.');
            }
        });
    }

    /**
     * Insert a new group, regenerating the slug if a concurrent request claims it
     * between generation and insert. Each attempt runs in its own transaction (or
     * savepoint) so a failed insert does not abort an enclosing Postgres transaction.
     *
     * @throws UniqueConstraintViolationException when every attempt collides
     */
    public function saveWithUniqueSlug(int $attempts = self::SLUG_CREATE_ATTEMPTS): void
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                DB::transaction(fn () => $this->save());

                return;
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= $attempts) {
                    throw $e;
                }

                $this->slug = null;
            }
        }
    }

    public function publicUrl(): string
    {
        return url('/p/'.$this->slug);
    }

    /**
     * Build a lowercase slug from the name, suffixing -2, -3, ... on collision.
     * Soft-deleted groups are included so their slugs are never reissued; the
     * unique index remains the final guard against concurrent inserts.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::of($name)->slug()->limit(self::SLUG_MAX_LENGTH - 10, '')->trim('-')->toString();

        if ($base === '') {
            $base = Str::lower(Str::random(8));
        }

        $taken = static::withTrashed()
            ->where('slug', $base)
            ->orWhere('slug', 'like', $base.'-%')
            ->pluck('slug')
            ->flip();

        $slug = $base;

        for ($suffix = 2; $taken->has($slug); $suffix++) {
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'appearance' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Link, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(Link::class)->orderBy('sort_order')->orderBy('id');
    }
}
