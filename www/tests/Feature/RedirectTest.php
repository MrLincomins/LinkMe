<?php

namespace Tests\Feature;

use App\Enums\RedirectType;
use App\Models\ShortDomain;
use App\Models\ShortLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    private ShortDomain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->domain = ShortDomain::factory()->verified()->create([
            'name' => 'localhost',
        ]);
    }

    public function test_redirects_active_link(): void
    {
        $link = ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'test',
            'target_url' => 'https://example.com',
        ]);

        $response = $this->get('/test');

        $response->assertRedirect('https://example.com');
        $response->assertStatus(301);
    }

    public function test_returns_404_for_nonexistent_code(): void
    {
        $response = $this->get('/nonexistent');

        $response->assertStatus(404);
    }

    public function test_returns_404_for_inactive_link(): void
    {
        ShortLink::factory()->inactive()->for($this->domain, 'domain')->create([
            'code' => 'off',
        ]);

        $response = $this->get('/off');

        $response->assertStatus(404);
    }

    public function test_increments_hit_count_on_redirect(): void
    {
        $link = ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'counter',
            'target_url' => 'https://example.com',
            'hit_count' => 0,
        ]);

        $this->get('/counter');
        $this->get('/counter');
        $this->get('/counter');

        $this->assertDatabaseHas('short_links', [
            'id' => $link->id,
            'hit_count' => 3,
        ]);
    }

    public function test_redirect_301_permanent(): void
    {
        ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'perm',
            'target_url' => 'https://example.com',
            'redirect_type' => RedirectType::PERMANENT,
        ]);

        $this->get('/perm')->assertStatus(301);
    }

    public function test_redirect_302_temporary(): void
    {
        ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'temp',
            'target_url' => 'https://example.com',
            'redirect_type' => RedirectType::TEMPORARY,
        ]);

        $this->get('/temp')->assertStatus(302);
    }

    public function test_redirect_307_temporary_preserve(): void
    {
        ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'tp',
            'target_url' => 'https://example.com',
            'redirect_type' => RedirectType::TEMPORARY_PRESERVE,
        ]);

        $this->get('/tp')->assertStatus(307);
    }

    public function test_redirect_308_permanent_preserve(): void
    {
        ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'pp',
            'target_url' => 'https://example.com',
            'redirect_type' => RedirectType::PERMANENT_PRESERVE,
        ]);

        $this->get('/pp')->assertStatus(308);
    }

    public function test_forward_query_params(): void
    {
        ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'fwd',
            'target_url' => 'https://example.com/page',
            'forward_query' => true,
        ]);

        $response = $this->get('/fwd?ref=google&id=42');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('ref=google', $location);
        $this->assertStringContainsString('id=42', $location);
    }

    public function test_extra_query_appended(): void
    {
        ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'eq',
            'target_url' => 'https://example.com',
            'extra_query' => 'utm_source=sniplnk&utm_medium=short',
        ]);

        $response = $this->get('/eq');

        $location = $response->headers->get('Location');
        $this->assertStringContainsString('utm_source=sniplnk', $location);
        $this->assertStringContainsString('utm_medium=short', $location);
    }

    public function test_extra_path_appended(): void
    {
        ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'ep',
            'target_url' => 'https://example.com/base',
            'extra_path' => 'promo/landing',
        ]);

        $response = $this->get('/ep');

        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/base/promo/landing', $location);
    }

    public function test_domain_root_redirect(): void
    {
        $this->domain->update(['target_url' => 'https://homepage.com']);

        $response = $this->get('/nonexistent-code');

        $response->assertRedirect('https://homepage.com');
    }

    public function test_home_page_renders(): void
    {
        config(['sniplnk.admin_domain' => 'localhost']);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('sniplnk');
    }
}
