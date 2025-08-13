<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\BedDryer;
use App\Models\SensorDevice;

class SensorDeviceSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Pastikan owner (admin) ada
        $owner = User::where('email', 'rayya@gmail.com')->first() ?? User::first();
        if (!$owner) {
            throw new \RuntimeException('User owner tidak ditemukan. Jalankan UserSeeder dulu.');
        }

        // 2) Pastikan ada warehouse (gudang) milik owner
        $warehouse = Warehouse::firstOrCreate(
            ['user_id' => $owner->user_id, 'nama' => 'Gudang A'],
            [
                'deskripsi'  => 'Gudang utama untuk pengujian & operasional',
            ]
        );

        // 3) Pastikan ada bed dryer milik owner dan ditautkan ke warehouse
        $dryer = BedDryer::firstOrCreate(
            [
                'user_id'      => $owner->user_id,
                'warehouse_id' => $warehouse->warehouse_id,
                'nama'         => 'Bed Dryer Utama',
            ],
            [
                'deskripsi' => 'Unit utama untuk pengujian',
            ]
        );

        // 4) Daftar device per dryer (unik per dryer)
        $devices = [
            [
                'device_id'   => 1,
                'device_name' => 'Tombak 1',
                'address'     => 'iot/sensor/datagabah/1',
                'location'    => 'Sudut Kiri',
                'status'      => true,
            ],
            [
                'device_id'   => 5,
                'device_name' => 'Pembakaran & Pengaduk',
                'address'     => 'iot/sensor/pembakaran/5',
                'location'    => 'Pipa Blower / Pemanas',
                'status'      => true,
            ],
        ];

        // 5) Upsert device pada dryer tersebut
        foreach ($devices as $d) {
            SensorDevice::updateOrCreate(
                [
                    'device_id'   => $d['device_id'],
                ],
                [
                    'dryer_id'    => $dryer->dryer_id,
                    'device_name' => $d['device_name'],
                    'address'     => $d['address'],
                    'status'      => (bool) $d['status'],
                ]
            );
        }
    }
}
