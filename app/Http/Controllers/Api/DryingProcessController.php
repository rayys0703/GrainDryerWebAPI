<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DryingProcess;
use App\Models\SensorData;
use App\Models\GrainType;
use Carbon\Carbon;

class DryingProcessController extends Controller
{
    public function getHistory(Request $request)
    {
        try {
            $user_id = 1;
            $processes = DryingProcess::where('user_id', $user_id)
                ->where('process_id', '>=', 1)
                ->where('status', 'completed')
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
                            'startDateFull' => Carbon::parse($process->timestamp_mulai)->format('Y-m-d H:i:s'),
                            'endDate' => $process->timestamp_selesai ? Carbon::parse($process->timestamp_selesai)->format('d F Y') : 'N/A',
                            'startTime' => Carbon::parse($process->timestamp_mulai)->format('H:i'),
                            'endTime' => $process->timestamp_selesai ? Carbon::parse($process->timestamp_selesai)->format('H:i') : 'N/A',
                            'initialWeight' => $firstSensor ? $process->berat_gabah_awal : 'N/A',
                            'finalWeight' => $lastSensor ? $process->berat_gabah_akhir : 'N/A',
                            'estimatedDuration' => $this->formatDuration($process->durasi_rekomendasi),
                            'executedDuration' => $this->formatDuration($process->durasi_terlaksana),
                            'actualDuration' => $process->durasi_aktual ? $this->formatDuration($process->durasi_aktual) : 'N/A',
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
            $user_id = 1;
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);
            $latest = $request->query('latest', false);
            $sortOrder = $request->query('sort_order', 'desc'); // Default descending

            $process = DryingProcess::where('process_id', $processId)
                ->where('user_id', $user_id)
                ->with('grainType')
                ->first();

            if (!$process) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proses pengeringan tidak ditemukan atau tidak diizinkan.',
                ], 404);
            }

            // Ambil data sensor dengan urutan berdasarkan sort_order
            $query = SensorData::where('process_id', $processId)
                ->with('sensorDevice')
                ->orderBy('timestamp', $sortOrder);

            $sensorData = $query->get();

            // Kelompokkan berdasarkan timestamp tanpa milidetik
            $groupedData = $sensorData->groupBy(function ($data) {
                return Carbon::parse($data->timestamp)->format('Y-m-d H:i:s');
            });

            // Hitung total interval
            $totalRecords = $groupedData->count();

            // Urutkan berdasarkan timestamp dan tetapkan interval
            $formattedSensorData = $groupedData
                ->sortBy(function ($group, $timestamp) use ($sortOrder) {
                    return $sortOrder === 'asc' ? Carbon::parse($timestamp)->timestamp : -Carbon::parse($timestamp)->timestamp;
                })
                ->values()
                ->map(function ($group, $index) use ($totalRecords, $sortOrder) {
                    $intervalData = [];
                    $grainMoistures = [];

                    // Tetapkan interval berdasarkan urutan
                    $interval = $sortOrder === 'asc' ? $index + 1 : $totalRecords - $index;

                    foreach ($group as $data) {
                        $deviceName = $data->sensorDevice->device_name ?? 'Unknown';
                        $deviceData = [];

                        if ($data->suhu_pembakaran !== null) {
                            $deviceData['burning_temperature'] = number_format((float) $data->suhu_pembakaran, 2, '.', '');
                        }
                        if ($data->suhu_ruangan !== null) {
                            $deviceData['room_temperature'] = number_format((float) $data->suhu_ruangan, 2, '.', '');
                        }
                        if ($data->kadar_air_gabah !== null) {
                            $deviceData['grain_moisture'] = number_format((float) $data->kadar_air_gabah, 2, '.', '');
                            $grainMoistures[] = (float) $data->kadar_air_gabah;
                        }
                        if ($data->suhu_gabah !== null) {
                            $deviceData['grain_temperature'] = number_format((float) $data->suhu_gabah, 2, '.', '');
                        }
                        if ($data->berat_gabah !== null) {
                            $deviceData['weight'] = number_format((float) $data->berat_gabah, 2, '.', '');
                        }
                        if ($data->status_pengaduk !== null) {
                            $deviceData['stirrer_status'] = (bool) $data->status_pengaduk;
                        }

                        if (!empty($deviceData)) {
                            $intervalData[$deviceName] = $deviceData;
                        }
                    }

                    $result = [
                        'interval' => max(1, $interval),
                        'timestamp' => $group->first()->timestamp,
                        'data' => $intervalData,
                    ];

                    if (!empty($grainMoistures)) {
                        $result['average_grain_moisture'] = number_format(round(array_sum($grainMoistures) / count($grainMoistures), 2), 2, '.', '');
                    } else {
                        $result['average_grain_moisture'] = null;
                    }

                    return $result;
                });

            // Terapkan pagination
            $offset = ($page - 1) * $perPage;
            $paginatedData = $latest ? $formattedSensorData->take(10) : $formattedSensorData->slice($offset, $perPage);

            $response = [
                'success' => true,
                'data' => [
                    'process_id' => $process->process_id,
                    'grain_type' => $process->grainType->nama_jenis ?? 'Unknown',
                    'sensor_data' => $paginatedData->values(),
                ],
            ];

            if (!$latest) {
                $response['pagination'] = [
                    'current_page' => $page,
                    'last_page' => ceil($totalRecords / $perPage),
                    'per_page' => $perPage,
                    'total' => $totalRecords,
                ];
            }

            return response()->json($response, 200);
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