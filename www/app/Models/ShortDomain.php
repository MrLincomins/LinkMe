<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property mixed $target_url
 * @property mixed|null $id
 * @property mixed $is_verified
 * @property mixed $extra_query
 * @property mixed $forward_query
 * @property mixed $extra_path
 * @property mixed $redirect_type
 */
class ShortDomain extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'target_url',
        'redirect_type',
        'forward_query',
        'extra_query',
        'extra_path',
        'is_active',
        'is_verified',
    ];

    public function links(): HasMany
    {
        return $this->hasMany(ShortLink::class, 'domain_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saving(function (ShortDomain $domain) {
            if ($domain->name) {
                $domain->name = strtolower(rtrim(trim($domain->name), '.'));
            }
        });
    }
}
