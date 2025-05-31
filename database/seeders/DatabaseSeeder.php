<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Сидирование демо-пользователей и адресов
        $users = [
            [
                'name' => 'Alice Demo',
                'email' => 'demo1@example.com',
                'phone' => '+66 1234 5678',
                'password' => Hash::make('demo'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bob Demo',
                'email' => 'demo2@example.com',
                'phone' => '+66 2345 6789',
                'password' => Hash::make('demo'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Charlie Demo',
                'email' => 'demo3@example.com',
                'phone' => '+66 3456 7890',
                'password' => Hash::make('demo'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        foreach ($users as $user) {
            $existing = DB::table('users')->where('email', $user['email'])->first();
            if (!$existing) {
                $userId = DB::table('users')->insertGetId($user);
            } else {
                $userId = $existing->id;
            }
            DB::table('addresses')->insert([
                [
                    'user_id' => $userId,
                    'address' => '123 Main St, Bangkok',
                    'label' => 'Home',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'user_id' => $userId,
                    'address' => '456 Office Rd, Bangkok',
                    'label' => 'Work',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
