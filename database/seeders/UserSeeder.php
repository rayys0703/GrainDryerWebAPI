<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // akun admin default
        User::updateOrCreate(
            ['email' => 'rayya@gmail.com'], // kunci pakai email
            [
                'nama' => 'Rayya RR',
                'password' => Hash::make('123123123'),
                'role' => 'admin',
            ]
        );

        // akun operator
        User::updateOrCreate(
            ['email' => 'operator@gmail.com'], // kunci pakai email
            [
                'nama' => 'Operator Lapangan',
                'password' => Hash::make('123123123'),
                'role' => 'operator',
            ]
        );
    }
}
