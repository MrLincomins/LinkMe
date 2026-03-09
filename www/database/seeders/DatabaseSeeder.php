<?php

namespace Database\Seeders;

use App\Models\ShortDomain;
use App\Models\ShortLink;
use App\Models\ShortLinkPassword;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'),
        ]);

        $main = ShortDomain::factory()->verified()->create([
            'name' => 'localhost',
            'target_url' => 'https://github.com',
        ]);

        $secondary = ShortDomain::factory()->create([
            'name' => 'short.test',
            'target_url' => 'https://example.com',
        ]);

        $links = ShortLink::factory()
            ->count(15)
            ->for($main, 'domain')
            ->create();

        ShortLink::factory()
            ->count(5)
            ->for($secondary, 'domain')
            ->create();

        $links->take(3)->each(function (ShortLink $link) {
            ShortLinkPassword::factory()
                ->count(2)
                ->for($link, 'shortLink')
                ->create();
        });

        ShortLinkPassword::factory()
            ->for($links[3], 'shortLink')
            ->withMaxUses(3)
            ->create(['password' => 'limited']);

        ShortLinkPassword::factory()
            ->for($links[4], 'shortLink')
            ->withTarget('https://example.com/vip')
            ->create(['password' => 'vip']);
    }
}
