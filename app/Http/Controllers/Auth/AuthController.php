<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BedDryer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial tidak valid.'],
            ]);
        }

        $token = $user->createToken($request->device_name ?? 'web')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'user_id' => $user->user_id,
                'nama'    => $user->nama,
                'role'    => $user->role,
                'email'   => $user->email,
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil.']);
    }

    /**
     * Kembalikan daftar bed dryer milik user yang sedang login.
     * Kolom 'lokasi' diambil dari warehouses.nama (relasi bed_dryers.warehouse_id).
     */
    public function myBedDryers(Request $request)
    {
        $user = $request->user();

        $dryers = BedDryer::query()
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'bed_dryers.warehouse_id')
            ->where('bed_dryers.user_id', $user->user_id)
            ->orderBy('bed_dryers.nama')
            ->get([
                'bed_dryers.dryer_id',
                'bed_dryers.nama',
                // ambil nama gudang sebagai 'lokasi'
                \DB::raw('COALESCE(warehouses.nama, bed_dryers.lokasi) as lokasi'),
                'bed_dryers.deskripsi',
            ]);

        return response()->json($dryers);
    }
}
