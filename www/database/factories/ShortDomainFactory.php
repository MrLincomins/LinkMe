<?php

namespace Database\Factories;

use App\Enums\RedirectType;
use App\Models\ShortDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShortDomainFactory extends Factory
{
    protected $model = ShortDomain::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->domainName(),
            'target_url' => fake()->url(),
            'redirect_type' => RedirectType::PERMANENT,
            'forward_query' => false,
            'extra_query' => '',
            'extra_path' => '',
            'is_active' => true,
            'is_verified' => false,
        ];
    }

    public function verified(): static
    {
        return $this->state(['is_verified' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withoutTarget(): static
    {
        return $this->state(['target_url' => null]);
    }
}
