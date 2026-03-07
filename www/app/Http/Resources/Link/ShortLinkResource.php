<?php

namespace App\Http\Resources\Link;

use App\Http\Resources\Domain\ShortDomainResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $domain
 * @property mixed $id
 * @property mixed $code
 * @property mixed $domain_id
 * @property mixed $short_path
 * @property mixed $target_url
 * @property mixed $redirect_type
 * @property mixed $extra_query
 * @property mixed $forward_query
 * @property mixed $extra_path
 * @property mixed $hit_count
 * @property mixed $is_active
 * @property mixed $created_at
 * @property mixed $updated_at
 */
class ShortLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $domainName = $this->whenLoaded('domain', fn () => $this->domain->name);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'domain_id' => $this->domain_id,
            'domain' => new ShortDomainResource($this->whenLoaded('domain')),
            'short_url' => $this->whenLoaded('domain', function () use ($request) {
                $scheme = $request->isSecure() ? 'https' : 'http';
                return "{$scheme}://{$this->domain->name}{$this->short_path}";
            }),
            'target_url' => $this->target_url,
            'redirect_type' => $this->redirect_type?->value,
            'forward_query' => $this->forward_query,
            'extra_query' => $this->extra_query,
            'extra_path' => $this->extra_path,
            'hit_count' => $this->hit_count,
            'is_active' => $this->is_active,
            'passwords' => ShortLinkPasswordResource::collection(
                $this->whenLoaded('passwords')
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
