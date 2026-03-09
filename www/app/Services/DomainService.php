<?php

namespace App\Services;

use App\Models\ShortDomain;
use Illuminate\Support\Facades\Cache;

class DomainService
{
    private const CACHE_TTL = 300;

    public function resolveByHost(string $host): ?ShortDomain
    {
        $normalized = $this->normalizeHost($host);

        return Cache::remember(
            "domain:{$normalized}",
            self::CACHE_TTL,
            fn () => ShortDomain::active()->where('name', $normalized)->first()
        );
    }

    public function markVerified(ShortDomain $domain): void
    {
        if (!$domain->is_verified) {
            $domain->update(['is_verified' => true]);
            $this->clearCache($domain->name);
        }
    }

    public function clearCache(string $name): void
    {
        $normalized = $this->normalizeHost($name);
        Cache::forget("domain:{$normalized}");
    }

    private function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = explode(':', $host)[0];
        $host = rtrim($host, '.');

        return $host;
    }
}
