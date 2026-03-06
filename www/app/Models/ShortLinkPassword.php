<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortLinkPassword extends Model
{

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
}
