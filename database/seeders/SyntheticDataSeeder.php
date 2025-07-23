<?php

namespace Database\Seeders;

use App\Models\DryingProcess;
use App\Models\SensorData;
use App\Models\PredictionEstimation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SyntheticDataSeeder extends Seeder
{
    public function run(): void
    {
        // Daftar file Excel dan konfigurasi
        $files = [
            'Jurnal 1' => [
                'file' => 'Jurnal 1 - Data_Sintetis_Pengeringan_Gabah_FDM_Variabel.xlsx',
                'grain_type_id' => 1,
                'total_minutes' => 1200,
                'initial_moisture' => 28.0,
                'final_moisture' => 14.0,
                'initial_grain_temp' => 26.0,
                'final_grain_temp' => 40.0,
                'weight' => 20000.0,
            ],
            // 'Jurnal 2' => [
            //     'file' => 'Jurnal 2 - Data_Sintetis_Pengeringan_Gabah_FDM.xlsx',
            //     'grain_type_id' => 1,
            //     'total_minutes' => 60,
            //     'initial_moisture' => 26.8,
            //     'final_moisture' => 13.78,
            //     'initial_grain_temp' => 26.0,
            //     'final_grain_temp' => 45.0,
            //     'weight' => 0.3,
            // ],
            // 'Jurnal 3a' => [
            //     'file' => 'Jurnal 3a - Data_Sintetis_Pengeringan_Gabah_FDM.xlsx',
            //     'grain_type_id' => 1,
            //     'total_minutes' => 120,
            //     'initial_moisture' => 25.2,
            //     'final_moisture' => 13.3,
            //     'initial_grain_temp' => 28.9,
            //     'final_grain_temp' => 59.8,
            //     'weight' => 15.0,
            // ],
            // 'Jurnal 3b' => [
            //     'file' => 'Jurnal 3b - Data_Sintetis_Pengeringan_Gabah_FDM.xlsx',
            //     'grain_type_id' => 1,
            //     'total_minutes' => 240,
            //     'initial_moisture' => 25.3,
            //     'final_moisture' => 13.6,
            //     'initial_grain_temp' => 27.9,
            //     'final_grain_temp' => 36.7,
            //     'weight' => 15.0,
            // ],
            // 'Jurnal 3c' => [
            //     'file' => 'Jurnal 3c - Data_Sintetis_Pengeringan_Gabah_FDM.xlsx',
            //     'grain_type_id' => 1,
            //     'total_minutes' => 105,
            //     'initial_moisture' => 24.7,
            //     'final_moisture' => 13.1,
            //     'initial_grain_temp' => 33.7,
            //     'final_grain_temp' => 66.8,
            //     'weight' => 15.0,
            // ],
        ];

        // Perangkat
        $devices = [
            1 => ['device_name' => 'Tombak 1', 'address' => 'iot/sensor/datagabah/1'],
            5 => ['device_name' => 'Pembakaran', 'address' => 'iot/sensor/pembakaran/5'],
        ];

        // Parameter
        $user_id = 1;
        $kadar_air_target = 14.0;
        $interval_seconds = 5;

        foreach ($files as $jurnal_name => $config) {
            try {
                // Baca file Excel
                $file_path = public_path('assets/dataset/' . $config['file']);
                if (!file_exists($file_path)) {
                    Log::error("File tidak ditemukan: {$file_path}");
                    continue;
                }

                $spreadsheet = IOFactory::load($file_path);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                // Lewati header (baris pertama)
                $data = array_slice($rows, 1);

                // Hitung avg_estimasi_durasi dari Waktu (menit) > 0
                $time_minutes = array_filter(array_column($data, 1), function ($time) {
                    return (float) $time > 0;
                });
                $avg_estimasi_durasi = !empty($time_minutes) ? array_sum($time_minutes) / count($time_minutes) : $config['total_minutes'];
                $avg_estimasi_durasi = (int) round($avg_estimasi_durasi, 0);

                // Agregasi data per interval 1 menit
                $interval_data = [];
                $total_intervals = (int) ceil($config['total_minutes']);
                for ($i = 0; $i < $total_intervals; $i++) {
                    $start_time = $i * $interval_seconds;
                    $end_time = ($i + 1) * $interval_seconds;
                    $subset = array_filter($data, function ($row) use ($start_time, $end_time) {
                        $time_seconds = (float) $row[0];
                        return $time_seconds >= $start_time && $time_seconds < $end_time;
                    });

                    if (empty($subset)) {
                        continue;
                    }

                    // Hitung rata-rata untuk interval
                    $moisture_sum = 0;
                    $grain_temp_sum = 0;
                    $room_temp_sum = 0;
                    $count = count($subset);
                    foreach ($subset as $row) {
                        $moisture_sum += (float) $row[2]; // Kadar Air Gabah (%)
                        $grain_temp_sum += (float) $row[3]; // Suhu Gabah (°C)
                        $room_temp_sum += (float) $row[4]; // Suhu Ruangan (°C)
                    }

                    $interval_data[] = [
                        'moisture' => round($moisture_sum / $count, 1),
                        'grain_temp' => round($grain_temp_sum / $count, 1),
                        'room_temp' => round($room_temp_sum / $count, 1),
                        'burning_temp' => round(mt_rand(400, 650) / 10.0, 1), // Suhu pembakaran acak 40-65°C
                        'stirrer_status' => false,
                        'timestamp' => Carbon::now()->addSeconds($start_time),
                        'estimasi_durasi' => round($config['total_minutes'] - ($start_time / 60), 1), // Estimasi linier
                    ];
                }

                // Simpan ke database dalam transaksi
                DB::transaction(function () use ($config, $interval_data, $user_id, $kadar_air_target, $devices, $avg_estimasi_durasi) {
                    // Buat proses pengeringan
                    $process = DryingProcess::create([
                        'user_id' => $user_id,
                        'grain_type_id' => $config['grain_type_id'],
                        'berat_gabah_awal' => $config['weight'],
                        'berat_gabah_akhir' => $config['weight'] * ($config['final_moisture'] / $config['initial_moisture']),
                        'kadar_air_target' => $kadar_air_target,
                        'kadar_air_awal' => $config['initial_moisture'],
                        'kadar_air_akhir' => $config['final_moisture'],
                        'status' => 'completed',
                        'durasi_rekomendasi' => (int) $config['total_minutes'],
                        'durasi_aktual' => $avg_estimasi_durasi,
                        'durasi_terlaksana' => $avg_estimasi_durasi,
                        'avg_estimasi_durasi' => $avg_estimasi_durasi,
                        'timestamp_mulai' => Carbon::now(),
                        'timestamp_selesai' => Carbon::now()->addMinutes($config['total_minutes']),
                    ]);

                    // Simpan data per interval
                    foreach ($interval_data as $data) {
                        // Simpan ke PredictionEstimation
                        PredictionEstimation::create([
                            'process_id' => $process->process_id,
                            'estimasi_durasi' => $data['estimasi_durasi'],
                            'timestamp' => $data['timestamp'],
                        ]);

                        // Simpan ke SensorData
                        foreach ($devices as $device_id => $device_info) {
                            if ($device_id == 1) {
                                SensorData::create([
                                    'process_id' => $process->process_id,
                                    'device_id' => $device_id,
                                    'timestamp' => $data['timestamp'],
                                    'kadar_air_gabah' => $data['moisture'],
                                    'suhu_gabah' => $data['grain_temp'],
                                    'suhu_ruangan' => $data['room_temp'],
                                ]);
                            } elseif ($device_id == 5) {
                                SensorData::create([
                                    'process_id' => $process->process_id,
                                    'device_id' => $device_id,
                                    'timestamp' => $data['timestamp'],
                                    'suhu_pembakaran' => $data['burning_temp'],
                                    'status_pengaduk' => $data['stirrer_status'],
                                ]);
                            }
                        }
                    }
                });

                Log::info("Data dari {$jurnal_name} berhasil disimpan ke database. Avg Estimasi Durasi: {$avg_estimasi_durasi}");
            } catch (\Exception $e) {
                Log::error("Error memproses {$jurnal_name}: {$e->getMessage()}");
            }
        }
    }
}