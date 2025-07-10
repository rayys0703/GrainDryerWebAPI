<?php

namespace Database\Seeders;

use App\Models\DryingProcess;
use App\Models\SensorData;
use App\Models\SensorDevice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainingDataSeeder extends Seeder
{
    public function run(): void
    {
        // Parameter dasar
        $intervals = 21;
        $weight = 2000.0; // Berat gabah (kg)
        $kadar_air_target = 14.0; // Target kadar air
        $user_id = 1; // Misalkan user_id

        // Definisi grup pelatihan
        $groups = [
            [
                'drying_time' => 1200,
                'avg_estimasi_durasi' => 1150.0, // Simulasi rata-rata estimasi
                'grain_type_id' => 1,
                'initial_moisture' => 28.0,
                'final_moisture' => 14.0,
                'initial_grain_temp' => 28.0,
                'final_grain_temp' => 40.0,
                'room_temp_min' => 27.0,
                'room_temp_max' => 28.0,
                'burning_temperature' => 60.0,
                'stirrer_status' => true
            ],
            [
                'drying_time' => 1000,
                'avg_estimasi_durasi' => 950.0,
                'grain_type_id' => 1,
                'initial_moisture' => 25.0,
                'final_moisture' => 14.0,
                'initial_grain_temp' => 26.0,
                'final_grain_temp' => 40.0,
                'room_temp_min' => 26.0,
                'room_temp_max' => 27.0,
                'burning_temperature' => 55.0,
                'stirrer_status' => false
            ],
            [
                'drying_time' => 800,
                'avg_estimasi_durasi' => 780.0,
                'grain_type_id' => 1,
                'initial_moisture' => 28.0,
                'final_moisture' => 14.0,
                'initial_grain_temp' => 25.0,
                'final_grain_temp' => 38.0,
                'room_temp_min' => 28.0,
                'room_temp_max' => 29.0,
                'burning_temperature' => 58.0,
                'stirrer_status' => true
            ],
            [
                'drying_time' => 480,
                'avg_estimasi_durasi' => 460.0,
                'grain_type_id' => 1,
                'initial_moisture' => 20.0,
                'final_moisture' => 12.0,
                'initial_grain_temp' => 30.0,
                'final_grain_temp' => 45.0,
                'room_temp_min' => 25.0,
                'room_temp_max' => 26.0,
                'burning_temperature' => 62.0,
                'stirrer_status' => false
            ],
        ];

        // Data perangkat
        $devices = [
            1 => ['device_name' => 'Tombak 1', 'address' => 'iot/sensor/datagabah/1'],
            // 2 => ['device_name' => 'Tombak 2', 'address' => 'iot/sensor/datagabah/2'],
            // 3 => ['device_name' => 'Tombak 3', 'address' => 'iot/sensor/datagabah/3'],
            // 4 => ['device_name' => 'Tombak 4', 'address' => 'iot/sensor/datagabah/4'],
            2 => ['device_name' => 'Pembakaran', 'address' => 'iot/sensor/pembakaran/2'],
        ];

        DB::transaction(function () use ($intervals, $weight, $kadar_air_target, $user_id, $groups, $devices) {
            foreach ($groups as $group_config) {
                // Hitung perubahan per interval
                $moisture_step = ($group_config['initial_moisture'] - $group_config['final_moisture']) / ($intervals - 1);
                $grain_temp_step = ($group_config['final_grain_temp'] - $group_config['initial_grain_temp']) / ($intervals - 1);

                // Buat drying process
                $process = DryingProcess::create([
                    'user_id' => $user_id,
                    'grain_type_id' => $group_config['grain_type_id'],
                    'berat_gabah_awal' => $weight,
                    'berat_gabah_akhir' => $weight,
                    'kadar_air_target' => $kadar_air_target,
                    'kadar_air_awal' => $group_config['initial_moisture'],
                    'kadar_air_akhir' => $kadar_air_target,
                    'status' => 'completed',
                    'durasi_rekomendasi' => $group_config['drying_time'],
                    'durasi_aktual' => $group_config['drying_time'],
                    'durasi_terlaksana' => $group_config['drying_time'],
                    'avg_estimasi_durasi' => $group_config['avg_estimasi_durasi'],
                    'timestamp_mulai' => now(),
                    'timestamp_selesai' => now()->addMinutes($group_config['drying_time']),
                ]);

                // Generate data sintesis untuk sensor_data
                for ($interval = 1; $interval <= $intervals; $interval++) {
                    $base_moisture = $group_config['initial_moisture'] - ($interval - 1) * $moisture_step;
                    $base_grain_temp = $group_config['initial_grain_temp'] + ($interval - 1) * $grain_temp_step;
                    $timestamp = now()->addMinutes(($interval - 1) * ($group_config['drying_time'] / $intervals));

                    foreach ($devices as $device_id => $device_info) {
                        // Buat atau pastikan perangkat ada
                        $device = SensorDevice::firstOrCreate(
                            ['device_id' => $device_id],
                            [
                                'device_name' => $device_info['device_name'],
                                'address' => $device_info['address']
                            ]
                        );

                        // Data untuk tombak pengering (1-4)
                        if ($device_id <= 1) {
                            $moisture = round($base_moisture + mt_rand(-2, 2) / 10.0, 1);
                            $grain_temp = round($base_grain_temp + mt_rand(-5, 5) / 10.0, 1);
                            $room_temp = round(mt_rand($group_config['room_temp_min'] * 10, $group_config['room_temp_max'] * 10) / 10.0, 1);

                            SensorData::create([
                                'process_id' => $process->process_id,
                                'device_id' => $device_id,
                                'timestamp' => $timestamp,
                                'kadar_air_gabah' => $moisture,
                                'suhu_gabah' => $grain_temp,
                                'suhu_ruangan' => $room_temp,
                            ]);
                        } else {
                            // Data untuk tombak pembakaran (2)
                            SensorData::create([
                                'process_id' => $process->process_id,
                                'device_id' => $device_id,
                                'timestamp' => $timestamp,
                                'suhu_pembakaran' => $group_config['burning_temperature'],
                                'status_pengaduk' => $group_config['stirrer_status'],
                            ]);
                        }
                    }
                }
            }
        });
    }
}