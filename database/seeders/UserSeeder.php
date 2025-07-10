<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User; // Pastikan ini di-import

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat akun admin default
        User::create([
            'nama' => 'Rayya RR',
            'username' => 'rayya', // Anda sebut username 'rayya' di chat sebelumnya
            'email' => 'rayya@gmail.com',
            'password' => Hash::make('123123123'),
            'role' => 'admin', // Atur role sebagai admin
        ]);

        // Contoh akun petani
        User::create([
            'nama' => 'Petani Contoh',
            'username' => 'petani_1',
            'email' => 'petani1@example.com',
            'password' => Hash::make('password'),
            'role' => 'petani',
        ]);

        // Contoh akun operator
        User::create([
            'nama' => 'Operator Lapangan',
            'username' => 'operator_1',
            'email' => 'operator1@example.com',
            'password' => Hash::make('password'),
            'role' => 'operator',
        ]);
    }
}