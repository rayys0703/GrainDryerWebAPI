<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class WebAuthController extends Controller
{
    /**
     * Tampilkan form login.
     * Tidak perlu method terpisah karena kita menggunakan closure di routes/web.php
     * Public function showLoginForm() { return view('auth.login'); }
     */

    /**
     * Tampilkan form register.
     * Tidak perlu method terpisah karena kita menggunakan closure di routes/web.php
     * Public function showRegisterForm() { return view('auth.register'); }
     */

    /**
     * Handle user registration for web.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'nama' => ['required', 'string', 'max:100'],
                'username' => ['required', 'string', 'max:50', 'unique:users,username'],
                'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'role' => ['nullable', 'in:admin,petani,operator'],
            ]);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        User::create([
            'nama' => $request->nama,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'petani',
            'created_at' => now(),
        ]);

        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan login.');
    }

    /**
     * Handle user login for web.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
            ]);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];

        // Custom authentication based on username
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Redirect berdasarkan role
            switch (Auth::user()->role) {
                case 'admin':
                    return redirect()->intended('/admin/dashboard'); // Ganti dengan route dashboard admin
                case 'petani':
                    return redirect()->intended('/petani/dashboard'); // Ganti dengan route dashboard petani
                case 'operator':
                    return redirect()->intended('/operator/dashboard'); // Ganti dengan route dashboard operator
                default:
                    return redirect()->intended('/dashboard'); // Default dashboard
            }
        }

        return redirect()->back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->withInput();
    }

    /**
     * Handle user logout for web.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }
}