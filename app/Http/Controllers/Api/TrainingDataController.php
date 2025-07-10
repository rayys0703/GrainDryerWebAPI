<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DryingProcess;
use App\Models\SensorData;
use App\Models\SensorDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrainingDataController extends Controller
{
    public function index()
    {
        try {
            $processes = DryingProcess::where('status', 'completed')
                ->whereNotNull('avg_estimasi_durasi')
                ->with(['sensorData' => function ($query) {
                    $query->join('sensor_devices', 'sensor_data.device_id', '=', 'sensor_devices.device_id')
                          ->select('sensor_data.*', 'sensor_devices.device_name');
                }])
                ->get();

            $formattedData = $processes->map(function ($process) {
                return [
                    'process_id' => $process->process_id,
                    'grain_type_id' => $process->grain_type_id,
                    'berat_gabah' => $process->berat_gabah_awal,
                    'avg_estimasi_durasi' => $process->avg_estimasi_durasi,
                    'sensor_data' => $process->sensorData->map(function ($data) {
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
            })->toArray();

            return response()->json($formattedData);
        } catch (\Exception $e) {
            Log::error('Error fetching completed processes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to fetch completed processes: ' . $e->getMessage()], 500);
        }
    }
}