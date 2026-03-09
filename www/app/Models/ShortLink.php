<?php

namespace App\Models;

use App\Enums\RedirectType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed $redirect_type
 * @property mixed $code
 * @property mixed $target_url
 * @property mixed $id
 * @property mixed $extra_path
 * @property mixed $extra_query
 * @property mixed $forward_query
 */

class ShortLink extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'domain_id',
        'target_url',
        'redirect_type',
        'forward_query',
        'extra_query',
        'extra_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'redirect_type' => RedirectType::class,
            'forward_query' => 'boolean',
            'is_active' => 'boolean',
            'hit_count' => 'integer',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(ShortDomain::class, 'domain_id');
    }

    public function passwords(): HasMany
    {
        return $this->hasMany(ShortLinkPassword::class);
    }

    public function getShortPathAttribute(): string
    {
        return "/{$this->code}/";
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDomain($query, int $domainId)
    {
        return $query->where('domain_id', $domainId);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }



    protected static function booted(): void
    {
        static::saving(static function (ShortLink $link) {
            if ($link->domain_id === null) {
                $defaultName = config('linkme.default_domain');
                $domain = ShortDomain::where('name', $defaultName)
                    ->active()
                    ->first();

                if ($domain) {
                    $link->domain_id = $domain->id;
                }
            }
        });
    }

    public function visits(): HasMany
    {
        return $this->hasMany(LinkVisit::class);
    }

}
