<?php

namespace Database\Factories;

use App\Models\Link;
use App\Models\LinkGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Link>
 */
class LinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'link_group_id' => LinkGroup::factory(),
            'title' => fake()->randomElement(['Website', 'Book a meeting', 'LinkedIn', 'Portfolio', 'Contact us', 'Latest news']),
            'url' => 'https://'.fake()->domainName().'/'.fake()->slug(2),
            'description' => fake()->optional(0.3)->sentence(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
