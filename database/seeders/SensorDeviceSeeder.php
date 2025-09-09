<?php
/*
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
            ['user_id' => $owner->id, 'nama' => 'Gudang A'],
            [
                'deskripsi'  => 'Gudang utama untuk pengujian & operasional',
            ]
        );

        // 3) Pastikan ada bed dryer milik owner dan ditautkan ke warehouse
        $dryer = BedDryer::firstOrCreate(
            [
                'user_id'      => $owner->id,
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
                    'location'    => $d['location'] ?? null,
                    'status'      => (bool) $d['status'],
                ]
            );
        }
    }
}
*/

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
        // 1) Pastikan terdapat akun user
        $owner = User::first();
        if (!$owner) {
            throw new \RuntimeException('User tidak ditemukan. Jalankan UserSeeder dulu.');
        }

        // 2) Pastikan ada warehouse (gudang) milik user
        $warehouse = Warehouse::firstOrCreate(
            ['user_id' => $owner->id, 'nama' => 'Gudang A'],
            [
                'deskripsi'  => 'Gudang utama untuk pengujian & operasional',
            ]
        );

        // 3) Buat 2 bed dryer milik owner dan ditautkan ke warehouse
        $dryer1 = BedDryer::firstOrCreate(
            [
                'user_id'      => $owner->id,
                'warehouse_id' => $warehouse->warehouse_id,
                'nama'         => 'Bed Dryer 1',
            ],
            [
                'deskripsi' => 'Unit utama untuk pengujian',
            ]
        );

        $dryer2 = BedDryer::firstOrCreate(
            [
                'user_id'      => $owner->id,
                'warehouse_id' => $warehouse->warehouse_id,
                'nama'         => 'Bed Dryer 2',
            ],
            [
                'deskripsi' => 'Unit sekunder untuk pengujian tambahan',
            ]
        );

        // 4) Daftar device untuk dryer pertama (2 perangkat)
        $devicesDryer1 = [
            [
                'device_id'   => 1,
                'device_name' => 'Tombak 1',
                'address'     => 'iot/mitra1/dryer1/1',
                'location'    => 'Sudut Kiri',
                'status'      => true,
            ],
            [
                'device_id'   => 5,
                'device_name' => 'Pembakaran & Pengaduk',
                'address'     => 'iot/mitra1/dryer1/5',
                'location'    => 'Pipa Blower / Pemanas',
                'status'      => true,
            ],
        ];

        // 5) Daftar device untuk dryer kedua (5 perangkat)
        $devicesDryer2 = [
            // [
            //     'device_id'   => 6,
            //     'device_name' => 'Tombak 1',
            //     'address'     => 'iot/sensor/datagabah2/1',
            //     'location'    => 'Sudut Kanan',
            //     'status'      => true,
            // ],
            [
                'device_id'   => 7,
                'device_name' => 'Sudut Kiri',
                'address'     => 'iot/mitra1/dryer2/7',
                'location'    => 'Sudut Kiri',
                'status'      => true,
            ],
            // [
            //     'device_id'   => 8,
            //     'device_name' => 'Tombak 3',
            //     'address'     => 'iot/sensor/datagabah2/3',
            //     'location'    => 'Pintu Keluar',
            //     'status'      => true,
            // ],
            // [
            //     'device_id'   => 9,
            //     'device_name' => 'Tombak 4',
            //     'address'     => 'iot/sensor/datagabah2/4',
            //     'location'    => 'Tengah',
            //     'status'      => true,
            // ],
            // [
            //     'device_id'   => 10,
            //     'device_name' => 'Pembakaran & Pengaduk',
            //     'address'     => 'iot/sensor/datagabah2/5',
            //     'location'    => 'Atas',
            //     'status'      => true,
            // ],
        ];

        // 6) Upsert device untuk dryer pertama
        foreach ($devicesDryer1 as $d) {
            SensorDevice::updateOrCreate(
                [
                    'device_id'   => $d['device_id'],
                ],
                [
                    'dryer_id'    => $dryer1->dryer_id,
                    'device_name' => $d['device_name'],
                    'address'     => $d['address'],
                    'location'    => $d['location'] ?? null,
                    'status'      => (bool) $d['status'],
                ]
            );
        }

        // 7) Upsert device untuk dryer kedua
        // foreach ($devicesDryer2 as $d) {
        //     SensorDevice::updateOrCreate(
        //         [
        //             'device_id'   => $d['device_id'],
        //         ],
        //         [
        //             'dryer_id'    => $dryer2->dryer_id,
        //             'device_name' => $d['device_name'],
        //             'address'     => $d['address'],
        //             'location'    => $d['location'] ?? null,
        //             'status'      => (bool) $d['status'],
        //         ]
        //     );
        // }
    }
}