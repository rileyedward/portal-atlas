<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
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
    }
}
