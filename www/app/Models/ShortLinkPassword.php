<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property mixed $id
 * @property mixed $extra_path
 * @property mixed $extra_query
 * @property mixed $target_url
 * @property mixed $short_link_id
 */
class ShortLinkPassword extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'short_link_id',
        'password',
        'target_url',
        'extra_query',
        'extra_path',
        'max_uses',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'hit_count' => 'integer',
            'max_uses' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function shortLink(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->active()->where(function ($q) {
            $q->whereNull('max_uses')
                ->orWhereColumn('hit_count', '<', 'max_uses');
        });
    }

    public static function countForLink(int $linkId, ?int $excludeId = null): int
    {
        $q = static::where('short_link_id', $linkId);

        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return $q->count();
    }

    public static function hasEmptyPassword(int $linkId, ?int $excludeId = null): bool
    {
        $q = static::where('short_link_id', $linkId)
            ->where('password', '');

        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }

        return $q->exists();
    }

    protected static function booted(): void
    {
        static::saving(static function (ShortLinkPassword $pw) {
            if ($pw->exists) {
                $original = $pw->getOriginal('is_active');
                if ($original === false && $pw->is_active === true) {
                    $pw->hit_count = 0;
                }
            }
        });
    }

}
