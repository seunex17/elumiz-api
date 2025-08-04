<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'email' => 'seunexseun@gmail.com',
            'password' => bcrypt('seunex15'),
            'full_name' => 'Zubayr Ganiyu',
            'name' => 'seunex',
            'role_id' => 3,
        ]);

        User::create([
            'email' => 'admin@muiz.com',
            'password' => bcrypt('admin'),
            'full_name' => 'Administrator',
            'name' => 'admin',
            'role_id' => 1,
        ]);
    }
}
