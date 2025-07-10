<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorData;
use App\Models\DryingProcess;
use App\Models\SensorDevice;
use App\Models\PredictionEstimation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RealtimeDataController extends Controller
{
    public function index()
    {
        try {
            // Ambil proses pengeringan aktif
            $dryingProcess = DryingProcess::whereIn('status', ['ongoing', 'pending'])
                ->select('process_id', 'grain_type_id', 'berat_gabah_awal as berat_gabah', 'kadar_air_target', 'status', 'created_at as started_at')
                ->orderBy('created_at', 'desc')
                ->first();

            Log::info('Drying process found: ' . ($dryingProcess ? '1' : '0'));

            $sensors = [];
            $initialSensors = [];
            $durasi_rekomendasi = 0;

            if ($dryingProcess) {
                // Ambil durasi_rekomendasi dari prediction_estimations (data terakhir)
                $latestEstimation = PredictionEstimation::where('process_id', $dryingProcess->process_id)
                    ->orderBy('timestamp', 'desc')
                    ->select('estimasi_durasi')
                    ->first();

                $durasi_rekomendasi = $latestEstimation ? (int) $latestEstimation->estimasi_durasi : 0;

                // Ambil data sensor terbaru
                $latestSensors = SensorData::select(
                    'device_id',
                    'suhu_gabah as grain_temperature',
                    'kadar_air_gabah as grain_moisture',
                    'suhu_ruangan as room_temperature',
                    'created_at as timestamp'
                )
                ->where('process_id', $dryingProcess->process_id)
                ->whereIn('sensor_id', function ($query) use ($dryingProcess) {
                    $query->select(DB::raw('MAX(sensor_id)'))
                          ->from('sensor_data')
                          ->where('process_id', $dryingProcess->process_id)
                          ->groupBy('device_id');
                })
                ->get();

                Log::info('Latest sensor data found: ' . $latestSensors->count() . ' devices');

                $sensors = $latestSensors->map(function ($sensor) {
                    $device = SensorDevice::where('device_id', $sensor->device_id)->first();
                    return [
                        'device_id' => $sensor->device_id,
                        'device_name' => $device ? $device->device_name : 'Unknown',
                        'location' => $device ? $device->location : 'Unknown',
                        'device_type' => $device ? $device->device_type : 'unknown',
                        'grain_temperature' => round((float) $sensor->grain_temperature, 1),
                        'grain_moisture' => round((float) $sensor->grain_moisture, 1),
                        'room_temperature' => round((float) $sensor->room_temperature, 1),
                        'timestamp' => Carbon::parse($sensor->timestamp)->toIso8601String(),
                    ];
                })->toArray();

                // Ambil data sensor awal
                $initialSensorsQuery = SensorData::select(
                    'device_id',
                    'suhu_gabah as grain_temperature',
                    'kadar_air_gabah as grain_moisture',
                    'suhu_ruangan as room_temperature',
                    'created_at as timestamp'
                )
                ->where('process_id', $dryingProcess->process_id)
                ->whereIn('sensor_id', function ($query) use ($dryingProcess) {
                    $query->select(DB::raw('MIN(sensor_id)'))
                          ->from('sensor_data')
                          ->where('process_id', $dryingProcess->process_id)
                          ->groupBy('device_id');
                })
                ->get();

                Log::info('Initial sensor data found: ' . $initialSensorsQuery->count() . ' devices');

                $initialSensors = $initialSensorsQuery->map(function ($sensor) {
                    $device = SensorDevice::where('device_id', $sensor->device_id)->first();
                    return [
                        'device_id' => $sensor->device_id,
                        'device_name' => $device ? $device->device_name : 'Unknown',
                        'location' => $device ? $device->location : 'Unknown',
                        'device_type' => $device ? $device->device_type : 'unknown',
                        'grain_temperature' => round((float) $sensor->grain_temperature, 1),
                        'grain_moisture' => round((float) $sensor->grain_moisture, 1),
                        'room_temperature' => round((float) $sensor->room_temperature, 1),
                        'timestamp' => Carbon::parse($sensor->timestamp)->toIso8601String(),
                    ];
                })->toArray();
            }

            $response = [
                'sensors' => $sensors,
                'initial_sensors' => $initialSensors,
                'drying_process' => $dryingProcess ? [
                    'process_id' => $dryingProcess->process_id,
                    'grain_type_id' => $dryingProcess->grain_type_id,
                    'berat_gabah' => (float) $dryingProcess->berat_gabah,
                    'kadar_air_target' => (float) $dryingProcess->kadar_air_target,
                    'durasi_rekomendasi' => $durasi_rekomendasi,
                    'status' => $dryingProcess->status,
                    'started_at' => Carbon::parse($dryingProcess->started_at)->toIso8601String(),
                ] : null,
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error fetching realtime data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch realtime data'], 500);
        }
    }
}