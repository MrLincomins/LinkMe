<?php

namespace App\Services;

use App\Models\ShortDomain;

class DomainService
{
    public function resolveByHost(string $host): ?ShortDomain
    {
        $normalized = $this->normalizeHost($host);

        return ShortDomain::active()
            ->where('name', $normalized)
            ->first();
    }

    public function markVerified(ShortDomain $domain): void
    {
        if (!$domain->is_verified) {
            $domain->update(['is_verified' => true]);
        }
    }

    private function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = explode(':', $host)[0];
        $host = rtrim($host, '.');

        return $host;
    }
}
