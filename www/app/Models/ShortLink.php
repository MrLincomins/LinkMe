<?php

namespace App\Models;

use App\Enums\RedirectType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShortLink extends Model
{
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

    // ── Relationships ──

    public function domain(): BelongsTo
    {
        return $this->belongsTo(ShortDomain::class, 'domain_id');
    }

    public function passwords(): HasMany
    {
        return $this->hasMany(ShortLinkPassword::class);
    }
}
