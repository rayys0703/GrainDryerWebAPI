<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <-- Tambahkan ini

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // <-- Tambahkan HasApiTokens di sini

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users'; // <-- Penting: Pastikan ini merujuk ke 'users' karena Anda mengganti primary key

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_id'; // <-- Tambahkan ini karena Anda mengganti PK dari 'id' menjadi 'user_id'

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'nama',
        'email',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'created_at' => 'datetime', // Optional, agar created_at otomatis menjadi Carbon instance
        ];
    }
}