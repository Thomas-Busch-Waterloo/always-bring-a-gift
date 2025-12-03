<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            EventTypeSeeder::class,
        ]);

        // Create default admin user if no users exist
        if (User::count() === 0) {
            $password = str::random(8);

            User::create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_admin' => true,
            ]);

            $this->command->info('👤 Default admin user created');
            $this->command->info('   Email: admin@example.com');
            $this->command->info('   Password: '.$password);
        }
    }
}
