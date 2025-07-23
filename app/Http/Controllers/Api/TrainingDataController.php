<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DryingProcess;
use App\Models\SensorData;
use App\Models\SensorDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class TrainingDataController extends Controller
{
    public function index(): JsonResponse
    {
        // Perpanjang waktu eksekusi agar tidak timeout
        set_time_limit(0); // 0 = unlimited

        try {
            $processes = DryingProcess::where('status', 'completed')
                ->whereNotNull('avg_estimasi_durasi')
                ->cursor(); // cursor lebih ringan dibanding get()

            $result = [];

            foreach ($processes as $process) {
                // Ambil estimasi prediksi sekali saja (untuk efisiensi)
                $estimations = $process->predictionEstimations()->get()->keyBy('timestamp');

                // Ambil sensor data langsung dari database menggunakan query terpisah
                $sensorRows = DB::table('sensor_data')
                    ->join('sensor_devices', 'sensor_data.device_id', '=', 'sensor_devices.device_id')
                    ->where('sensor_data.process_id', $process->process_id)
                    ->orderBy('sensor_data.timestamp')
                    ->select(
                        'sensor_data.*',
                        'sensor_devices.device_name'
                    )
                    ->get()
                    ->groupBy('timestamp');

                $intervals = [];
                $i = 1;
                foreach ($sensorRows as $timestamp => $intervalData) {
                    $intervals[] = [
                        'interval_id' => $i++,
                        'timestamp' => $timestamp,
                        'estimasi_durasi' => $estimations[$timestamp]->estimasi_durasi ?? null,
                        'sensor_data' => $intervalData->map(function ($data) {
                            return [
                                'device_id' => $data->device_id,
                                'device_name' => $data->device_name,
                                'suhu_gabah' => $data->suhu_gabah,
                                'kadar_air_gabah' => $data->kadar_air_gabah,
                                'suhu_ruangan' => $data->suhu_ruangan,
                                'suhu_pembakaran' => $data->suhu_pembakaran,
                                'status_pengaduk' => $data->status_pengaduk
                            ];
                        })->toArray()
                    ];
                }

                $result[] = [
                    'process_id' => $process->process_id,
                    'grain_type_id' => $process->grain_type_id,
                    'berat_gabah' => $process->berat_gabah_awal,
                    'avg_estimasi_durasi' => $process->avg_estimasi_durasi,
                    'intervals' => $intervals
                ];
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error fetching completed processes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to fetch completed processes: ' . $e->getMessage()], 500);
        }
    }
}
