<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::upsert([
            [
                'name' => 'Admin User',
                'email' => 'admin@mail.com',
                'password' => Hash::make('password'), // Password is usually required for new users
            ],
        ], ['email'], ['name', 'password']);
        $this->call([LocaleSeeder::class, TagSeeder::class]);
    }
}
