<?php

namespace Database\Seeders;

use App\Models\Link;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

/**
 * Optional development data. Run with: php artisan db:seed --class=LinkGroupSeeder
 *
 * Adds sample groups to an existing user (SEED_USER_EMAIL, default test@example.com).
 * It never creates accounts. Model events must stay enabled so slugs are generated.
 */
class LinkGroupSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SEED_USER_EMAIL', 'test@example.com');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->command?->warn("No user with email [{$email}] found; skipping sample link groups.");

            return;
        }

        foreach (range(1, 3) as $i) {
            $group = $user->linkGroups()->create([
                'name' => fake()->company(),
                'description' => fake()->sentence(12),
            ]);

            Link::factory()
                ->count(fake()->numberBetween(3, 6))
                ->for($group)
                ->state(new Sequence(fn (Sequence $sequence) => ['sort_order' => $sequence->index]))
                ->create();
        }

        $this->command?->info("Created 3 sample link groups for [{$email}].");
    }
}
