<?php

namespace Tests\Feature;

use App\Models\ShortDomain;
use App\Services\DomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_is_cached_after_first_resolve(): void
    {
        $domain = ShortDomain::factory()->create(['name' => 'cached.test']);

        $service = app(DomainService::class);

        $result = $service->resolveByHost('cached.test');
        $this->assertNotNull($result);

        $this->assertTrue(Cache::has('domain:cached.test'));
    }

    public function test_cache_cleared_when_domain_updated(): void
    {
        $domain = ShortDomain::factory()->create(['name' => 'update.test']);

        $service = app(DomainService::class);
        $service->resolveByHost('update.test');

        $this->assertTrue(Cache::has('domain:update.test'));

        $domain->update(['target_url' => 'https://new-target.com']);

        $this->assertFalse(Cache::has('domain:update.test'));
    }
}
