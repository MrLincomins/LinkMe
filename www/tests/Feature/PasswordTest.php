<?php

namespace Tests\Feature;

use App\Models\ShortDomain;
use App\Models\ShortLink;
use App\Models\ShortLinkPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    private ShortDomain $domain;
    private ShortLink $link;

    protected function setUp(): void
    {
        parent::setUp();

        $this->domain = ShortDomain::factory()->verified()->create([
            'name' => 'localhost',
        ]);

        $this->link = ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'secret',
            'target_url' => 'https://example.com/protected',
        ]);
    }

    public function test_shows_password_form_on_get(): void
    {
        ShortLinkPassword::factory()->for($this->link, 'shortLink')->create([
            'password' => 'abc123',
        ]);

        $response = $this->get('/secret');

        $response->assertStatus(200);
        $response->assertSee('Password required');
    }

    public function test_correct_password_redirects(): void
    {
        ShortLinkPassword::factory()->for($this->link, 'shortLink')->create([
            'password' => 'abc123',
        ]);

        $response = $this->post('/secret', ['password' => 'abc123']);

        $response->assertRedirect('https://example.com/protected');
    }

    public function test_wrong_password_returns_401(): void
    {
        ShortLinkPassword::factory()->for($this->link, 'shortLink')->create([
            'password' => 'abc123',
        ]);

        $response = $this->post('/secret', ['password' => 'wrong']);

        $response->assertStatus(401);
        $response->assertSee('Incorrect password');
    }

    public function test_password_with_custom_target(): void
    {
        ShortLinkPassword::factory()->for($this->link, 'shortLink')->create([
            'password' => 'vip',
            'target_url' => 'https://example.com/vip-area',
        ]);

        $response = $this->post('/secret', ['password' => 'vip']);

        $response->assertRedirect('https://example.com/vip-area');
    }

    public function test_password_increments_hit_counts(): void
    {
        $pw = ShortLinkPassword::factory()->for($this->link, 'shortLink')->create([
            'password' => 'track',
        ]);

        $this->post('/secret', ['password' => 'track']);
        $this->post('/secret', ['password' => 'track']);

        $this->assertDatabaseHas('short_links', [
            'id' => $this->link->id,
            'hit_count' => 2,
        ]);

        $this->assertDatabaseHas('short_link_passwords', [
            'id' => $pw->id,
            'hit_count' => 2,
        ]);
    }

    public function test_password_deactivates_after_max_uses(): void
    {
        $pw = ShortLinkPassword::factory()->for($this->link, 'shortLink')->create([
            'password' => 'limited',
            'max_uses' => 2,
        ]);

        $this->post('/secret', ['password' => 'limited']);
        $this->post('/secret', ['password' => 'limited']);

        $this->assertDatabaseHas('short_link_passwords', [
            'id' => $pw->id,
            'hit_count' => 2,
            'is_active' => false,
        ]);

        // Third attempt should fail
        $response = $this->post('/secret', ['password' => 'limited']);
        $response->assertStatus(401);
    }

    public function test_inactive_password_is_rejected(): void
    {
        ShortLinkPassword::factory()->inactive()->for($this->link, 'shortLink')->create([
            'password' => 'disabled',
        ]);

        $response = $this->post('/secret', ['password' => 'disabled']);

        $response->assertStatus(401);
    }

    public function test_empty_password_works(): void
    {
        ShortLinkPassword::factory()->emptyPassword()->for($this->link, 'shortLink')->create();

        $response = $this->post('/secret', ['password' => '']);

        $response->assertRedirect('https://example.com/protected');
    }
}
