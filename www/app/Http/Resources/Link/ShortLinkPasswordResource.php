<?php

namespace App\Http\Resources\Link;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $password
 * @property mixed $target_url
 * @property mixed $extra_query
 * @property mixed $extra_path
 * @property mixed $hit_count
 * @property mixed $max_uses
 * @property mixed $is_active
 * @property mixed $created_at
 */
class ShortLinkPasswordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'password' => $this->password,
            'target_url' => $this->target_url,
            'extra_query' => $this->extra_query,
            'extra_path' => $this->extra_path,
            'hit_count' => $this->hit_count,
            'max_uses' => $this->max_uses,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
