<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorData;
use App\Models\DryingProcess;
use App\Models\SensorDevice;
use App\Models\PredictionEstimation;
use App\Models\GrainType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RealtimeDataController extends Controller
{
    public function index()
    {
        try {
            // Ambil proses pengeringan aktif (ongoing atau pending) terlebih dahulu
            $dryingProcess = DryingProcess::whereIn('status', ['ongoing', 'pending'])
                ->select(
                    'process_id',
                    'grain_type_id',
                    'berat_gabah_awal as berat_gabah',
                    'kadar_air_target',
                    'kadar_air_awal',
                    'kadar_air_akhir',
                    'durasi_rekomendasi',
                    'durasi_terlaksana',
                    'avg_estimasi_durasi',
                    'status',
                    'created_at as started_at',
                    'timestamp_selesai'
                )
                ->orderBy('created_at', 'desc')
                ->first();

            // Jika tidak ada proses aktif, ambil proses completed terakhir
            if (!$dryingProcess) {
                $dryingProcess = DryingProcess::where('status', 'completed')
                    ->select(
                        'process_id',
                        'grain_type_id',
                        'berat_gabah_awal as berat_gabah',
                        'kadar_air_target',
                        'kadar_air_awal',
                        'kadar_air_akhir',
                        'durasi_rekomendasi',
                        'durasi_terlaksana',
                        'avg_estimasi_durasi',
                        'status',
                        'created_at as started_at',
                        'timestamp_selesai'
                    )
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            Log::info('Drying process found: ' . ($dryingProcess ? '1' : '0'));

            $sensors = [];
            $initialSensors = [];
            $nama_jenis = null;

            if ($dryingProcess) {
                // Ambil nama_jenis dari tabel grain_types
                $grainType = GrainType::where('grain_type_id', $dryingProcess->grain_type_id)
                    ->select('nama_jenis')
                    ->first();
                $nama_jenis = $grainType ? $grainType->nama_jenis : null;

                // Ambil data sensor terbaru
                $latestSensors = SensorData::select(
                    'device_id',
                    'suhu_gabah as grain_temperature',
                    'kadar_air_gabah as grain_moisture',
                    'suhu_ruangan as room_temperature',
                    'suhu_pembakaran as burning_temperature',
                    'status_pengaduk as stirrer_status',
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
                        'grain_temperature' => $sensor->grain_temperature !== null ? round((float) $sensor->grain_temperature, 1) : null,
                        'grain_moisture' => $sensor->grain_moisture !== null ? round((float) $sensor->grain_moisture, 1) : null,
                        'room_temperature' => $sensor->room_temperature !== null ? round((float) $sensor->room_temperature, 1) : null,
                        'burning_temperature' => $sensor->burning_temperature !== null ? round((float) $sensor->burning_temperature, 1) : null,
                        'stirrer_status' => $sensor->stirrer_status !== null ? (bool) $sensor->stirrer_status : null,
                        'timestamp' => Carbon::parse($sensor->timestamp)->toIso8601String(),
                    ];
                })->toArray();

                // Ambil data sensor awal
                $initialSensorsQuery = SensorData::select(
                    'device_id',
                    'suhu_gabah as grain_temperature',
                    'kadar_air_gabah as grain_moisture',
                    'suhu_ruangan as room_temperature',
                    'suhu_pembakaran as burning_temperature',
                    'status_pengaduk as stirrer_status',
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
                        'grain_temperature' => $sensor->grain_temperature !== null ? round((float) $sensor->grain_temperature, 1) : null,
                        'grain_moisture' => $sensor->grain_moisture !== null ? round((float) $sensor->grain_moisture, 1) : null,
                        'room_temperature' => $sensor->room_temperature !== null ? round((float) $sensor->room_temperature, 1) : null,
                        'burning_temperature' => $sensor->burning_temperature !== null ? round((float) $sensor->burning_temperature, 1) : null,
                        'stirrer_status' => $sensor->stirrer_status !== null ? (bool) $sensor->stirrer_status : null,
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
                    'nama_jenis' => $nama_jenis,
                    'berat_gabah' => (float) $dryingProcess->berat_gabah,
                    'kadar_air_target' => (float) $dryingProcess->kadar_air_target,
                    'kadar_air_awal' => (float) $dryingProcess->kadar_air_awal,
                    'kadar_air_akhir' => $dryingProcess->kadar_air_akhir !== null ? (float) $dryingProcess->kadar_air_akhir : null,
                    'durasi_rekomendasi' => $dryingProcess->durasi_rekomendasi,
                    'avg_estimasi_durasi' => round($dryingProcess->avg_estimasi_durasi, 0),
                    'durasi_terlaksana' => $dryingProcess->durasi_terlaksana,
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