<?php

namespace Database\Factories;

use App\Enums\RedirectType;
use App\Models\ShortDomain;
use App\Models\ShortLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ShortLinkFactory extends Factory
{
    protected $model = ShortLink::class;

    public function definition(): array
    {
        return [
            'code' => Str::lower(Str::random(8)),
            'domain_id' => ShortDomain::factory(),
            'target_url' => fake()->url(),
            'redirect_type' => RedirectType::PERMANENT,
            'forward_query' => false,
            'extra_query' => '',
            'extra_path' => '',
            'hit_count' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withForwardQuery(): static
    {
        return $this->state(['forward_query' => true]);
    }

    public function withExtraQuery(string $query = 'utm_source=sniplnk'): static
    {
        return $this->state(['extra_query' => $query]);
    }

    public function withExtraPath(string $path = 'promo'): static
    {
        return $this->state(['extra_path' => $path]);
    }

    public function temporary(): static
    {
        return $this->state(['redirect_type' => RedirectType::TEMPORARY]);
    }
}
