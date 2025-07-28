<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🔍 Starting database seeding...');

        // Create default user first
        $this->command->info('👤 Creating default user...');
        if (!User::where('email', 'mr.lukas.schmidt@gmail.com')->exists()) {
            User::factory()->create([
                'name' => 'Lukas Schmidt',
                'email' => 'mr.lukas.schmidt@gmail.com',
                'password' => bcrypt('password'),
            ]);
            $this->command->info('✅ User created successfully!');
        } else {
            $this->command->info('👤 User already exists, skipping...');
        }

        // Import ZIP codes from DAWA API
        $this->command->info('📮 Importing ZIP codes from DAWA...');
        Artisan::call('import:dar-zips');
        $this->command->info('✅ ZIP codes imported successfully!');

        // Seed city aliases
        $this->command->info('🏙️ Seeding city aliases...');
        $this->call(CityAliasSeeder::class);

        $this->command->info('✅ Database seeding completed!');
    }

}
