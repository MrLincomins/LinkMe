<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
