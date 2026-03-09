<?php

namespace Database\Factories;

use App\Models\ShortLink;
use App\Models\ShortLinkPassword;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShortLinkPasswordFactory extends Factory
{
    protected $model = ShortLinkPassword::class;

    public function definition(): array
    {
        return [
            'short_link_id' => ShortLink::factory(),
            'password' => fake()->word(),
            'target_url' => '',
            'extra_query' => '',
            'extra_path' => '',
            'hit_count' => 0,
            'max_uses' => null,
            'is_active' => true,
        ];
    }

    public function withTarget(string $url = 'https://example.com/secret'): static
    {
        return $this->state(['target_url' => $url]);
    }

    public function withMaxUses(int $max = 5): static
    {
        return $this->state(['max_uses' => $max]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function emptyPassword(): static
    {
        return $this->state(['password' => '']);
    }
}
