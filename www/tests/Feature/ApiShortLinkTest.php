<?php

namespace Tests\Feature;

use App\Models\ShortDomain;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiShortLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ShortDomain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->domain = ShortDomain::factory()->create(['name' => 'test.com']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/links')->assertStatus(401);
    }

    public function test_list_links(): void
    {
        ShortLink::factory()->count(3)->for($this->domain, 'domain')->create();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/links');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_create_link(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/links', [
            'code' => 'newlink',
            'domain_id' => $this->domain->id,
            'target_url' => 'https://example.com/target',
            'redirect_type' => '301',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.code', 'newlink');

        $this->assertDatabaseHas('short_links', [
            'code' => 'newlink',
            'domain_id' => $this->domain->id,
        ]);
    }

    public function test_show_link_with_passwords(): void
    {
        $link = ShortLink::factory()->for($this->domain, 'domain')->create();
        $link->passwords()->create([
            'password' => 'test',
            'target_url' => '',
            'extra_query' => '',
            'extra_path' => '',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/links/{$link->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data.passwords');
    }

    public function test_update_link(): void
    {
        $link = ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'old',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/links/{$link->id}", [
            'code' => 'updated',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.code', 'updated');
    }

    public function test_delete_link(): void
    {
        $link = ShortLink::factory()->for($this->domain, 'domain')->create();

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/links/{$link->id}")->assertOk();

        $this->assertDatabaseMissing('short_links', ['id' => $link->id]);
    }

    public function test_duplicate_code_on_same_domain_fails(): void
    {
        ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'taken',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/links', [
            'code' => 'taken',
            'domain_id' => $this->domain->id,
            'target_url' => 'https://example.com',
            'redirect_type' => '301',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('code');
    }

    public function test_same_code_on_different_domain_works(): void
    {
        ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'shared',
        ]);

        $other = ShortDomain::factory()->create(['name' => 'other.com']);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/links', [
            'code' => 'shared',
            'domain_id' => $other->id,
            'target_url' => 'https://example.com',
            'redirect_type' => '301',
        ]);

        $response->assertStatus(201);
    }

    public function test_search_links(): void
    {
        ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'findme',
            'target_url' => 'https://example.com',
        ]);
        ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'other',
            'target_url' => 'https://other.com',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/links?search=findme');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }
}
