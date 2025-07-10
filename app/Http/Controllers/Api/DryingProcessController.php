<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DryingProcess;
use App\Models\SensorData;
use App\Models\GrainType;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DryingProcessController extends Controller
{
    public function getHistory(Request $request)
    {
        try {
            $user = Auth::user();
            $user_id = 1;
            $processes = DryingProcess::where('user_id', $user_id) //$user->user_id
                ->with('grainType')
                ->orderBy('timestamp_mulai', 'desc')
                ->get()
                ->groupBy(function ($process) {
                    return Carbon::parse($process->timestamp_mulai)->format('d F Y');
                });

            $history = $processes->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'entries' => $group->map(function ($process) {
                        $firstSensor = SensorData::where('process_id', $process->process_id)
                            ->orderBy('timestamp', 'asc')
                            ->first();
                        $lastSensor = SensorData::where('process_id', $process->process_id)
                            ->orderBy('timestamp', 'desc')
                            ->first();

                        return [
                            'process_id' => $process->process_id,
                            'grainType' => $process->grainType->nama_jenis ?? 'Unknown',
                            'startDate' => Carbon::parse($process->timestamp_mulai)->format('d F Y'),
                            'endDate' => $process->timestamp_selesai ? Carbon::parse($process->timestamp_selesai)->format('d F Y') : 'N/A',
                            'startTime' => Carbon::parse($process->timestamp_mulai)->format('H:i'),
                            'endTime' => $process->timestamp_selesai ? Carbon::parse($process->timestamp_selesai)->format('H:i') : 'N/A',
                            'initialWeight' => $firstSensor ? $process->berat_gabah : 0.0,
                            'finalWeight' => $lastSensor ? $lastSensor->berat_gabah ?? $process->berat_gabah : 0.0,
                            'estimatedDuration' => $this->formatDuration($process->durasi_rekomendasi),
                            'executedDuration' => $this->formatDuration($process->durasi_terlaksana),
                            'totalDuration' => $process->durasi_aktual ? $this->formatDuration($process->durasi_aktual) : 'N/A',
                            'status' => ucfirst(strtolower($process->status)),
                            'location' => $process->lokasi ?? 'Gudang Utama',
                            'notes' => $process->catatan ?? '',
                        ];
                    })->toArray(),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $history,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getProcessDetails(Request $request, $processId)
    {
        try {
            $user = Auth::user();
            $user_id = 1;

            $process = DryingProcess::where('process_id', $processId)
                ->where('user_id', $user_id) // $user->user_id
                ->with('grainType')
                ->first();

            if (!$process) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proses pengeringan tidak ditemukan atau tidak diizinkan.',
                ], 404);
            }

            $sensorData = SensorData::where('process_id', $processId)
                ->with('sensorDevice')
                ->orderBy('timestamp', 'asc')
                ->get()
                ->groupBy(function ($data) {
                    return Carbon::parse($data->timestamp)->format('H:i');
                })->map(function ($group, $time) {
                    $intervalData = [];
                    foreach ($group as $data) {
                        $deviceName = $data->sensorDevice->device_name;
                        $intervalData[$deviceName] = [
                            'burning_temperature' => $data->suhu_pembakaran,
                            'room_temperature' => $data->suhu_ruangan,
                            'grain_moisture' => $data->kadar_air_gabah,
                            'grain_temperature' => $data->suhu_gabah,
                            'weight' => $data->berat_gabah ?? null,
                            'stirrer_status' => $data->status_pengaduk,
                        ];
                    }
                    return [
                        'interval' => $time,
                        'data' => $intervalData,
                    ];
                })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'process_id' => $process->process_id,
                    'grain_type' => $process->grainType->nama_jenis ?? 'Unknown',
                    'sensor_data' => $sensorData,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail proses: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatDuration($minutes)
    {
        if ($minutes === null) return 'N/A';
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return $hours > 0 ? "$hours Jam $remainingMinutes Menit" : "$remainingMinutes Menit";
    }
}