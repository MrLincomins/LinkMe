<?php

namespace App\Http\Resources\Domain;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $name
 * @property mixed $target_url
 * @property mixed $redirect_type
 * @property mixed $forward_query
 * @property mixed $is_active
 * @property mixed $extra_query
 * @property mixed $extra_path
 * @property mixed $created_at
 * @property mixed $is_verified
 */
class ShortDomainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'target_url' => $this->target_url,
            'redirect_type' => $this->redirect_type?->value,
            'forward_query' => $this->forward_query,
            'extra_query' => $this->extra_query,
            'extra_path' => $this->extra_path,
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'links_count' => $this->whenCounted('links'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
