<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\PublicCache;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Loads the reference dataset (marker taxonomy, versions, maps with base
     * images, items, loot pools, objectives and markers). Every step upserts
     * by natural key, so it is safe to run on every deploy:
     *
     *   php artisan db:seed --force
     *
     * A local test admin (admin@test.com / password) is created only when
     * APP_ENV=local.
     */
    public function run(): void
    {
        $this->call([
            MarkerTaxonomySeeder::class,
            GameDataSeeder::class,
        ]);

        if (app()->environment('local')) {
            $admin = User::firstOrCreate(['email' => 'admin@test.com'], [
                'name' => 'Test Admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ]);
            $admin->forceFill(['role' => UserRole::Admin])->save();
        }

        app(PublicCache::class)->flush();
    }
}
