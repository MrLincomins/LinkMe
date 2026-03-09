<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkVisit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'short_link_id',
        'short_link_password_id',
        'ip',
        'user_agent',
        'referer',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function shortLink(): BelongsTo
    {
        return $this->belongsTo(ShortLink::class);
    }

    public function password(): BelongsTo
    {
        return $this->belongsTo(ShortLinkPassword::class, 'short_link_password_id');
    }
}
