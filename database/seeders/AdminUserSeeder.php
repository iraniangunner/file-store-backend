<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // اگر قبلا ادمین ساخته نشده بود، یکی بساز
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('18861995Fm$'), // پسورد پیش‌فرض
                'role' => 'admin',
            ]
        );
    }
}
