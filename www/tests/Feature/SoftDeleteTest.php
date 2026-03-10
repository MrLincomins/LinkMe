<?php

namespace Tests\Feature;

use App\Models\ShortDomain;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ShortDomain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->domain = ShortDomain::factory()->create();
    }

    public function test_deleted_link_not_accessible_via_redirect(): void
    {
        $domain = ShortDomain::factory()->verified()->withoutTarget()->create(['name' => 'localhost']);
        $link = ShortLink::factory()->for($domain, 'domain')->create([
            'code' => 'del',
            'target_url' => 'https://example.com',
        ]);

        $link->delete();

        $this->get('/del')->assertStatus(404);
    }

    public function test_list_trashed_links(): void
    {
        $link = ShortLink::factory()->for($this->domain, 'domain')->create();
        $link->delete();

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/links/trashed');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_restore_trashed_link(): void
    {
        $link = ShortLink::factory()->for($this->domain, 'domain')->create([
            'code' => 'revive',
        ]);
        $link->delete();

        Sanctum::actingAs($this->user);

        $this->postJson("/api/links/{$link->id}/restore")->assertOk();

        $this->assertDatabaseHas('short_links', [
            'id' => $link->id,
            'deleted_at' => null,
        ]);
    }

    public function test_force_delete_trashed_link(): void
    {
        $link = ShortLink::factory()->for($this->domain, 'domain')->create();
        $link->delete();

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/links/{$link->id}/force")->assertOk();

        $this->assertDatabaseMissing('short_links', ['id' => $link->id]);
    }
}
