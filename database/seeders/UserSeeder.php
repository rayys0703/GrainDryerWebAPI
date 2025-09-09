<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'rayya@gmail.com'],
            [
                'nama' => 'Rayya RR',
                'password' => Hash::make('123123123'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'operator@gmail.com'],
            [
                'nama' => 'Operator Lapangan',
                'password' => Hash::make('123123123'),
                'role' => 'operator',
            ]
        );
    }
}
