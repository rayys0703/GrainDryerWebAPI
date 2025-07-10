<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DryingProcess;
use App\Models\SensorData;
use App\Models\SensorDevice;
use App\Models\PredictionEstimation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PredictionController extends Controller
{
    public function startPrediction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grain_type_id' => 'required|integer|exists:grain_types,grain_type_id',
            'berat_gabah_awal' => 'required|numeric|min:100',
            'kadar_air_target' => 'required|numeric|min:0|max:100',
            'user_id' => 'nullable|integer|exists:users,user_id'
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for start prediction', ['errors' => $validator->errors()]);
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            // Hentikan semua proses aktif
            $activeProcesses = DryingProcess::whereIn('status', ['pending', 'ongoing'])->get();
            foreach ($activeProcesses as $activeProcess) {
                $latestSensorData = SensorData::where('process_id', $activeProcess->process_id)
                    ->latest('timestamp')
                    ->first();

                $kadar_air_akhir = $latestSensorData ? $latestSensorData->kadar_air_gabah : null;
                $durasi_terlaksana = $activeProcess->timestamp_mulai
                    ? round(Carbon::parse($activeProcess->timestamp_mulai)->diffInMinutes(now()))
                    : 0;

                // Hitung rata-rata estimasi durasi (non-nol)
                $avg_estimasi_durasi = PredictionEstimation::where('process_id', $activeProcess->process_id)
                    ->where('estimasi_durasi', '>', 0)
                    ->avg('estimasi_durasi');

                $activeProcess->update([
                    'status' => 'completed',
                    'kadar_air_akhir' => $kadar_air_akhir,
                    'durasi_terlaksana' => $durasi_terlaksana,
                    'avg_estimasi_durasi' => $avg_estimasi_durasi
                ]);
                Log::info('Active process stopped', [
                    'process_id' => $activeProcess->process_id,
                    'kadar_air_akhir' => $kadar_air_akhir,
                    'durasi_terlaksana' => $durasi_terlaksana,
                    'avg_estimasi_durasi' => $avg_estimasi_durasi
                ]);
            }

            // Buat proses pengeringan baru
            $dryingProcess = DryingProcess::create([
                'grain_type_id' => (int) $request->grain_type_id,
                'berat_gabah_awal' => (float) $request->berat_gabah_awal,
                'berat_gabah_akhir' => (float) $request->berat_gabah_awal,
                'kadar_air_target' => (float) $request->kadar_air_target,
                'user_id' => $request->user_id ?? 1,
                'status' => 'pending',
                'timestamp_mulai' => now(),
                'durasi_rekomendasi' => 0
            ]);

            Log::info('Drying process created', ['process_id' => $dryingProcess->process_id]);

            return response()->json([
                'message' => 'Prediction started successfully, waiting for sensor data...',
                'process_id' => $dryingProcess->process_id
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error starting prediction: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to start prediction'], 500);
        }
    }

    public function stopPrediction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'process_id' => 'required|integer|exists:drying_process,process_id'
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for stop prediction', ['errors' => $validator->errors()]);
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $dryingProcess = DryingProcess::where('process_id', $request->process_id)
                ->whereIn('status', ['pending', 'ongoing'])
                ->first();

            if (!$dryingProcess) {
                Log::info('No active process to stop', ['process_id' => $request->process_id]);
                return response()->json(['message' => 'No active process to stop'], 200);
            }

            $latestSensorData = SensorData::where('process_id', $dryingProcess->process_id)
                ->latest('timestamp')
                ->first();

            $kadar_air_akhir = $latestSensorData ? $latestSensorData->kadar_air_gabah : null;
            $durasi_terlaksana = $dryingProcess->timestamp_mulai
                ? round(Carbon::parse($dryingProcess->timestamp_mulai)->diffInMinutes(now()))
                : 0;

            // Hitung rata-rata estimasi durasi (non-nol)
            $avg_estimasi_durasi = PredictionEstimation::where('process_id', $dryingProcess->process_id)
                ->where('estimasi_durasi', '>', 0)
                ->avg('estimasi_durasi');

            $dryingProcess->update([
                'status' => 'completed',
                'kadar_air_akhir' => $kadar_air_akhir,
                'durasi_terlaksana' => $durasi_terlaksana,
                'avg_estimasi_durasi' => $avg_estimasi_durasi
            ]);

            Log::info('Prediction stopped successfully', [
                'process_id' => $dryingProcess->process_id,
                'kadar_air_akhir' => $kadar_air_akhir,
                'durasi_terlaksana' => $durasi_terlaksana,
                'avg_estimasi_durasi' => $avg_estimasi_durasi
            ]);

            return response()->json([
                'message' => 'Prediction stopped successfully',
                'process_id' => $dryingProcess->process_id
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error stopping prediction: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to stop prediction'], 500);
        }
    }

    public function receivePrediction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'process_id' => 'required|integer|exists:drying_process,process_id',
            'grain_type_id' => 'required|integer|exists:grain_types,grain_type_id',
            'points' => 'required|array|min:1',
            'points.*.point_id' => 'required|integer|min:1',
            'points.*.grain_temperature' => 'nullable|numeric|min:0',
            'points.*.grain_moisture' => 'nullable|numeric|min:0',
            'points.*.room_temperature' => 'nullable|numeric|min:0',
            'points.*.burning_temperature' => 'nullable|numeric|min:0',
            'points.*.stirrer_status' => 'nullable|boolean',
            'avg_grain_temperature' => 'required|numeric|min:0',
            'avg_grain_moisture' => 'required|numeric|min:0',
            'burning_temperature' => 'required|numeric|min:0',
            'stirrer_status' => 'required|boolean',
            'predicted_drying_time' => 'required|numeric|min:0',
            'weight' => 'required|numeric|min:100',
            'timestamp' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for prediction data', ['errors' => $validator->errors()]);
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $dryingProcess = DryingProcess::where('process_id', $request->process_id)
                ->whereIn('status', ['pending', 'ongoing'])
                ->firstOrFail();

            if ($dryingProcess->status == 'pending') {
                $dryingProcess->update(['status' => 'ongoing']);
            }

            if ($dryingProcess->durasi_rekomendasi == 0) {
                $dryingProcess->update([
                    'durasi_rekomendasi' => round($request->predicted_drying_time)
                ]);
            }

            // Simpan estimasi durasi
            PredictionEstimation::create([
                'process_id' => $dryingProcess->process_id,
                'estimasi_durasi' => $request->predicted_drying_time,
                'timestamp' => date('Y-m-d H:i:s', $request->timestamp)
            ]);

            // Perbarui kadar_air_awal jika masih null
            if (is_null($dryingProcess->kadar_air_awal)) {
                $firstSensorData = SensorData::where('process_id', $dryingProcess->process_id)
                    ->whereNotNull('kadar_air_gabah')
                    ->orderBy('timestamp', 'asc')
                    ->first();

                if ($firstSensorData) {
                    $dryingProcess->update(['kadar_air_awal' => $firstSensorData->kadar_air_gabah]);
                    Log::info('Initial moisture updated', [
                        'process_id' => $dryingProcess->process_id,
                        'kadar_air_awal' => $firstSensorData->kadar_air_gabah
                    ]);
                }
            }

            Log::info('Drying process updated', [
                'process_id' => $dryingProcess->process_id,
                'durasi_rekomendasi' => $request->predicted_drying_time
            ]);

            foreach ($request->points as $point) {
                $device = SensorDevice::where('device_id', $point['point_id'])->first();
                if (!$device) {
                    Log::error('Sensor device not found', ['point_id' => $point['point_id']]);
                    return response()->json(['error' => 'Sensor device not found for point_id: ' . $point['point_id']], 404);
                }

                SensorData::create([
                    'process_id' => $dryingProcess->process_id,
                    'device_id' => $device->device_id,
                    'timestamp' => date('Y-m-d H:i:s', $request->timestamp),
                    'kadar_air_gabah' => $point['grain_moisture'],
                    'suhu_gabah' => $point['grain_temperature'],
                    'suhu_ruangan' => $point['room_temperature'],
                    'suhu_pembakaran' => $point['burning_temperature'],
                    'status_pengaduk' => $point['stirrer_status']
                ]);
            }

            Log::info('Sensor data saved', ['process_id' => $dryingProcess->process_id, 'points' => count($request->points)]);

            // Ubah status ke completed hanya jika kadar air target tercapai
            if ($request->avg_grain_moisture <= $dryingProcess->kadar_air_target) {
                // Hitung rata-rata estimasi durasi (non-nol)
                $avg_estimasi_durasi = PredictionEstimation::where('process_id', $dryingProcess->process_id)
                    ->where('estimasi_durasi', '>', 0)
                    ->avg('estimasi_durasi');

                $dryingProcess->update([
                    'status' => 'completed',
                    'kadar_air_akhir' => $request->avg_grain_moisture,
                    'durasi_terlaksana' => $dryingProcess->timestamp_mulai
                        ? round(Carbon::parse($dryingProcess->timestamp_mulai)->diffInMinutes(now()))
                        : 0,
                    'avg_estimasi_durasi' => $avg_estimasi_durasi
                ]);
                Log::info('Drying process completed', [
                    'process_id' => $dryingProcess->process_id,
                    'reason' => 'Kadar air mencapai target',
                    'avg_estimasi_durasi' => $avg_estimasi_durasi
                ]);
            }

            return response()->json([
                'message' => 'Prediction data received and stored successfully',
                'process_id' => $dryingProcess->process_id,
                'estimated_duration' => $dryingProcess->durasi_rekomendasi
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error receiving prediction: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to receive prediction data'], 500);
        }
    }
}