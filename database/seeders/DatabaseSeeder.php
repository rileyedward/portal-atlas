<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reference data (maps, items, markers, loot pools...) is loaded by the
     * 2026_09_30_000010_seed_reference_data migration, so `migrate` alone
     * gives a complete database. This seeder only adds a local test admin.
     *
     * Refresh reference data at any time with:
     *   php artisan db:seed --class=GameDataSeeder
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $admin = User::firstOrCreate(['email' => 'admin@test.com'], [
            'name' => 'Test Admin',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['role' => UserRole::Admin])->save();
    }
}
