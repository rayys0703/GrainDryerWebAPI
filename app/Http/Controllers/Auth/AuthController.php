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
                'user_id' => $user->id,
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
     * Lokasi diambil dari warehouses.nama dan tampilkan juga sensor devices.
     */
    public function myBedDryers(Request $request)
    {
        $user = $request->user();

        $dryers = BedDryer::with([
                'warehouse:warehouse_id,nama',
                'devices:device_id,dryer_id,device_name,address,location,status'
            ])
            ->where('user_id', $user->id)
            ->orderBy('nama')
            ->get();

        $payload = $dryers->map(function ($d) {
            return [
                'dryer_id'        => $d->dryer_id,
                'nama'            => $d->nama,
                // lokasi dari warehouses.nama
                'lokasi'          => optional($d->warehouse)->nama,
                'deskripsi'       => $d->deskripsi,
                'sensor_devices'  => $d->devices->map(function ($dev) {
                    return [
                        'device_id'   => $dev->device_id,
                        'device_name' => $dev->device_name,
                        'address'     => $dev->address,
                        'location'     => $dev->location,
                        'status'      => (bool) $dev->status,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json($payload);
    }
}
