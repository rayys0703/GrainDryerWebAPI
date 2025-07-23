<?php

namespace Database\Seeders;

use App\Models\DryingProcess;
use App\Models\SensorData;
use App\Models\SensorDevice;
use App\Models\PredictionEstimation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainingDataSeeder extends Seeder
{
    public function run(): void
    {
        $intervals = 21;
        $weight = 2000.0;
        $kadar_air_target = 14.0;
        $user_id = 1;

        $group_config = [
            'drying_time' => 1000,
            'avg_estimasi_durasi' => 1150.0,
            'grain_type_id' => 1,
            'initial_moisture' => 28.0,
            'final_moisture' => 14.0,
            'initial_grain_temp' => 28.0,
            'final_grain_temp' => 40.0,
            'room_temp_min' => 27.0,
            'room_temp_max' => 28.0,
            'burning_temperature' => 60.0,
            'stirrer_status' => true
        ];

        // Perangkat
        $devices = [
            1 => ['device_name' => 'Tombak 1', 'address' => 'iot/sensor/datagabah/1'],
            5 => ['device_name' => 'Pembakaran', 'address' => 'iot/sensor/pembakaran/5'],
        ];

        DB::transaction(function () use ($intervals, $weight, $kadar_air_target, $user_id, $group_config, $devices) {
            // Hitung perubahan per interval
            $moisture_step = ($group_config['initial_moisture'] - $group_config['final_moisture']) / ($intervals - 1);
            $grain_temp_step = ($group_config['final_grain_temp'] - $group_config['initial_grain_temp']) / ($intervals - 1);
            $interval_duration = $group_config['drying_time'] / $intervals; // Durasi per interval

            // Buat proses pengeringan
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

            // Generate data sintesis untuk setiap interval
            for ($interval = 1; $interval <= $intervals; $interval++) {
                $base_moisture = $group_config['initial_moisture'] - ($interval - 1) * $moisture_step;
                $base_grain_temp = $group_config['initial_grain_temp'] + ($interval - 1) * $grain_temp_step;
                $timestamp = now()->addMinutes(($interval - 1) * $interval_duration);

                // Estimasi durasi per interval (misalnya, menurun secara linier)
                $estimasi_durasi = round($group_config['avg_estimasi_durasi'] * (1 - ($interval - 1) / $intervals), 1);

                // Simpan ke prediction_estimations
                PredictionEstimation::create([
                    'process_id' => $process->process_id,
                    'estimasi_durasi' => $estimasi_durasi,
                    'timestamp' => $timestamp,
                ]);

                foreach ($devices as $device_id => $device_info) {
                    // Data untuk tombak pengering (device_id = 1)
                    if ($device_id == 1) {
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
                    } elseif ($device_id == 5) {
                        // Data untuk tombak pembakaran (device_id = 5)
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
        });
    }
}