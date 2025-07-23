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

class TrainingFileExcelSeeder extends Seeder
{
    public function run(): void
    {
        // Define the directory for Excel files
        $importDir = storage_path('app/import');
        $files = glob("{$importDir}/*.xlsx");

        if (empty($files)) {
            Log::warning("No .xlsx files found in {$importDir}");
            echo "No .xlsx files found in {$importDir}.\n";
            return;
        }

        // Devices configuration
        $devices = [
            1 => ['device_name' => 'Tombak 1', 'address' => 'iot/sensor/datagabah/1']
            // 5 => ['device_name' => 'Pembakaran', 'address' => 'iot/sensor/pembakaran/5'],
        ];

        // Parameters
        $user_id = 1;
        $kadar_air_target = 14.0;
        $grain_type_id = 1;

        foreach ($files as $file) {
            try {
                echo "Processing file: {$file}\n";
                Log::info("Processing file: {$file}");

                // Load Excel file using PhpSpreadsheet
                $spreadsheet = IOFactory::load($file);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                // Validate that sheet has data
                if (empty($rows)) {
                    Log::warning("File {$file} is empty or could not be read.");
                    echo "File {$file} is empty or could not be read.\n";
                    continue;
                }

                // Get and remove header
                $header = array_shift($rows);

                // Validate header (expected columns)
                $expectedColumns = [
                    0 => 'interval_seconds',
                    1 => 'estimasi_minutes',
                    2 => 'grain_moisture',
                    3 => 'grain_temperature',
                    4 => 'room_temperature',
                    5 => 'burn_temperature',
                    6 => 'weight',
                ];

                if (count($header) < count($expectedColumns)) {
                    Log::warning("File {$file} has fewer columns than expected.");
                    echo "File {$file} has fewer columns than expected.\n";
                    continue;
                }

                // Map rows to data
                $mapped = [];
                foreach ($rows as $rowIndex => $row) {
                    // Ensure row has enough columns
                    if (count($row) < count($expectedColumns)) {
                        Log::warning("Row " . ($rowIndex + 2) . " in {$file} has missing columns.");
                        continue;
                    }

                    $mapped[] = [
                        'interval_seconds'   => isset($row[0]) ? (float) $row[0] : null,
                        'estimasi_minutes'   => isset($row[1]) ? (float) $row[1] : null,
                        'grain_moisture'     => isset($row[2]) ? (float) $row[2] : null,
                        'grain_temperature'  => isset($row[3]) ? (float) $row[3] : null,
                        'room_temperature'   => isset($row[4]) ? (float) $row[4] : null,
                        'burn_temperature'   => isset($row[5]) ? (float) $row[5] : null,
                        'weight'             => isset($row[6]) ? (float) $row[6] : null,
                    ];
                }

                // Skip if no valid data
                if (empty($mapped)) {
                    Log::warning("No valid data extracted from {$file}.");
                    echo "No valid data extracted from {$file}.\n";
                    continue;
                }

                // Calculate initial and final values
                $valid_data = array_filter($mapped, function ($item) {
                    return !is_null($item['interval_seconds']) && !is_null($item['grain_moisture']) && !is_null($item['grain_temperature']);
                });

                if (empty($valid_data)) {
                    Log::warning("No valid data with required fields in {$file}.");
                    echo "No valid data with required fields in {$file}.\n";
                    continue;
                }

                // Sort by interval to get initial and final values
                usort($valid_data, function ($a, $b) {
                    return $a['interval_seconds'] <=> $b['interval_seconds'];
                });

                $initial_data = reset($valid_data);
                $final_data = end($valid_data);
                $total_duration_minutes = ceil($final_data['interval_seconds'] / 60);

                // Calculate avg_estimasi_durasi from estimasi_minutes (non-zero)
                $estimasi_minutes = array_filter(array_column($mapped, 'estimasi_minutes'), function ($value) {
                    return !is_null($value) && $value > 0;
                });
                $avg_estimasi_durasi = !empty($estimasi_minutes) ? array_sum($estimasi_minutes) / count($estimasi_minutes) : $total_duration_minutes;
                $avg_estimasi_durasi = (int) round($avg_estimasi_durasi, 0);

                // Process within a transaction
                DB::transaction(function () use ($file, $mapped, $user_id, $kadar_air_target, $grain_type_id, $devices, $initial_data, $final_data, $total_duration_minutes, $avg_estimasi_durasi) {
                    // Create DryingProcess
                    $process = DryingProcess::create([
                        'user_id' => $user_id,
                        'grain_type_id' => $grain_type_id,
                        'berat_gabah_awal' => $initial_data['weight'],
                        'berat_gabah_akhir' => $final_data['weight'],
                        'kadar_air_target' => $kadar_air_target,
                        'kadar_air_awal' => $initial_data['grain_moisture'],
                        'kadar_air_akhir' => $final_data['grain_moisture'],
                        'status' => 'completed',
                        'durasi_rekomendasi' => $total_duration_minutes,
                        'durasi_aktual' => $total_duration_minutes,
                        'durasi_terlaksana' => $total_duration_minutes,
                        'avg_estimasi_durasi' => $avg_estimasi_durasi,
                        'timestamp_mulai' => Carbon::now(),
                        'timestamp_selesai' => Carbon::now()->addMinutes($total_duration_minutes),
                    ]);

                    // Save data per interval
                    foreach ($mapped as $item) {
                        // Skip if required fields are null
                        if (is_null($item['interval_seconds']) || is_null($item['grain_moisture']) || is_null($item['grain_temperature'])) {
                            Log::warning("Skipping invalid data in {$file} for interval {$item['interval_seconds']}: " . json_encode($item));
                            continue;
                        }

                        // Save to PredictionEstimation
                        PredictionEstimation::create([
                            'process_id' => $process->process_id,
                            'estimasi_durasi' => $item['estimasi_minutes'],
                            'timestamp' => Carbon::now()->addSeconds($item['interval_seconds']),
                        ]);

                        // Save to SensorData
                        foreach ($devices as $device_id => $device_info) {
                            if ($device_id == 1) {
                                SensorData::create([
                                    'process_id' => $process->process_id,
                                    'device_id' => $device_id,
                                    'timestamp' => Carbon::now()->addSeconds($item['interval_seconds']),
                                    'kadar_air_gabah' => $item['grain_moisture'],
                                    'suhu_gabah' => $item['grain_temperature'],
                                    'suhu_ruangan' => $item['room_temperature'],
                                    'suhu_pembakaran' => $item['burn_temperature'],
                                    'status_pengaduk' => false,
                                ]);
                            // } elseif ($device_id == 5) {
                            //     SensorData::create([
                            //         'process_id' => $process->process_id,
                            //         'device_id' => $device_id,
                            //         'timestamp' => Carbon::now()->addSeconds($item['interval_seconds']),
                            //         'suhu_pembakaran' => $item['burn_temperature'],
                            //         'status_pengaduk' => false,
                            //     ]);
                            }
                        }
                    }
                });

                Log::info("Successfully imported data from {$file}. Avg Estimasi Durasi: {$avg_estimasi_durasi}");
                echo "Successfully imported data from {$file}. Avg Estimasi Durasi: {$avg_estimasi_durasi}\n";

            } catch (\Exception $e) {
                Log::error("Failed to process file {$file}: {$e->getMessage()}");
                echo "Failed to process file {$file}: {$e->getMessage()}\n";
                continue;
            }
        }

        echo "Import process completed.\n";
        Log::info("Import process completed.");
    }
}