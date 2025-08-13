<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DryingProcess;
use App\Models\SensorData;
use App\Models\PredictionEstimation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\BedDryer;

class PredictionController extends Controller
{
    public function startPrediction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dryer_id'         => 'required|integer|exists:bed_dryers,dryer_id',
            'grain_type_id'    => 'required|integer|exists:grain_types,grain_type_id',
            'berat_gabah_awal' => 'required|numeric|min:100',
            'kadar_air_target' => 'required|numeric|min:10|max:20',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for start prediction', ['errors' => $validator->errors()]);
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $user = $request->user();

            // Opsional: pastikan dryer milik user (kalau kebijakan demikian)
            $dryer = BedDryer::where('dryer_id', $request->dryer_id)
                ->when($user && $user->role !== 'admin', fn($q) => $q->where('user_id', $user->user_id))
                ->firstOrFail();

            // Cari proses pending/ongoing pada dryer ini; kalau tidak ada buat pending
            $dryingProcess = DryingProcess::where('dryer_id', $dryer->dryer_id)
                ->whereIn('status', ['pending'])
                ->first();

            if ($dryingProcess) {
                $dryingProcess->update([
                    'grain_type_id'      => (int) $request->grain_type_id,
                    'berat_gabah_awal'   => (float) $request->berat_gabah_awal,
                    'kadar_air_target'   => (float) $request->kadar_air_target,
                    'status'             => 'pending',
                    'timestamp_mulai'    => null,
                    'durasi_rekomendasi' => 0,
                ]);
            } else {
                $dryingProcess = DryingProcess::create([
                    'dryer_id'           => $dryer->dryer_id,
                    'grain_type_id'      => (int) $request->grain_type_id,
                    'berat_gabah_awal'   => (float) $request->berat_gabah_awal,
                    'kadar_air_target'   => (float) $request->kadar_air_target,
                    'status'             => 'pending',
                    'timestamp_mulai'    => null,
                    'durasi_rekomendasi' => 0,
                ]);
            }

            return response()->json([
                'message'    => 'Prediction started successfully, waiting for sensor data...',
                'process_id' => $dryingProcess->process_id,
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
                return response()->json(['message' => 'No active process to stop'], 200);
            }

            $latestSensorData = SensorData::where('process_id', $dryingProcess->process_id)->latest()->first();

            $kadar_air_akhir   = $latestSensorData?->kadar_air_gabah;
            $durasi_terlaksana = $dryingProcess->timestamp_mulai
                ? round(Carbon::parse($dryingProcess->timestamp_mulai)->diffInMinutes(now()))
                : 0;

            $avg_estimasi_durasi = PredictionEstimation::where('process_id', $dryingProcess->process_id)
                ->where('estimasi_durasi', '>', 0)
                ->avg('estimasi_durasi');

            $dryingProcess->update([
                'status'              => 'completed',
                'kadar_air_akhir'     => $kadar_air_akhir,
                'durasi_terlaksana'   => $durasi_terlaksana,
                'avg_estimasi_durasi' => $avg_estimasi_durasi,
                'timestamp_selesai'   => now(),
            ]);

            return response()->json([
                'message'    => 'Prediction stopped successfully',
                'process_id' => $dryingProcess->process_id,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error stopping prediction: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to stop prediction'], 500);
        }
    }

    public function receivePrediction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'process_id'            => 'required|integer|exists:drying_process,process_id',
            'grain_type_id'         => 'required|integer|exists:grain_types,grain_type_id',
            'kadar_air_gabah'       => 'required|numeric|min:0',
            'predicted_drying_time' => 'required|numeric|min:0',
            'timestamp'             => 'required|numeric'
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for prediction data', ['errors' => $validator->errors()]);
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $dryingProcess = DryingProcess::where('process_id', $request->process_id)
                ->whereIn('status', ['pending', 'ongoing'])
                ->firstOrFail();

            if (is_null($dryingProcess->grain_type_id) || is_null($dryingProcess->berat_gabah_awal) || is_null($dryingProcess->kadar_air_target)) {
                return response()->json(['message' => 'Incomplete drying process data, prediction not stored'], 200);
            }

            if ($dryingProcess->status === 'pending') {
                $dryingProcess->update(['status' => 'ongoing']);
            }
            if (is_null($dryingProcess->timestamp_mulai)) {
                $dryingProcess->update(['timestamp_mulai' => now()]);
            }

            $dryingProcess->update(['durasi_rekomendasi' => round($request->predicted_drying_time)]);

            PredictionEstimation::create([
                'process_id'      => $dryingProcess->process_id,
                'estimasi_durasi' => $request->predicted_drying_time,
                'timestamp'       => date('Y-m-d H:i:s', $request->timestamp),
            ]);

            if (is_null($dryingProcess->kadar_air_awal)) {
                $firstSensorData = SensorData::where('process_id', $dryingProcess->process_id)
                    ->whereNotNull('kadar_air_gabah')
                    ->orderBy('timestamp', 'asc')
                    ->first();

                if ($firstSensorData) {
                    $dryingProcess->update(['kadar_air_awal' => $firstSensorData->kadar_air_gabah]);
                }
            }

            $durasiTerlaksana = $dryingProcess->timestamp_mulai
                ? round(Carbon::parse($dryingProcess->timestamp_mulai)->diffInMinutes(now()))
                : 0;
            $dryingProcess->update(['durasi_terlaksana' => $durasiTerlaksana]);

            if ($request->kadar_air_gabah <= $dryingProcess->kadar_air_target) {
                $avg_estimasi_durasi = PredictionEstimation::where('process_id', $dryingProcess->process_id)
                    ->where('estimasi_durasi', '>', 0)
                    ->avg('estimasi_durasi');

                $dryingProcess->update([
                    'status'              => 'completed',
                    'kadar_air_akhir'     => $request->kadar_air_gabah,
                    'durasi_terlaksana'   => $durasiTerlaksana,
                    'avg_estimasi_durasi' => $avg_estimasi_durasi,
                    'timestamp_selesai'   => now(),
                ]);
            }

            return response()->json([
                'message'            => 'Prediction data received and stored successfully',
                'process_id'         => $dryingProcess->process_id,
                'estimated_duration' => $dryingProcess->durasi_rekomendasi,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error receiving prediction: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to receive prediction data'], 500);
        }
    }
}
