<?php

namespace App\Observers;

use App\Models\ShortDomain;
use App\Services\DomainService;

class ShortDomainObserver
{
    public function __construct(
        private DomainService $domainService
    ) {}

    public function updated(ShortDomain $domain): void
    {
        $this->domainService->clearCache($domain->name);

        if ($domain->wasChanged('name')) {
            $this->domainService->clearCache($domain->getOriginal('name'));
        }
    }

    public function deleted(ShortDomain $domain): void
    {
        $this->domainService->clearCache($domain->name);
    }
}
