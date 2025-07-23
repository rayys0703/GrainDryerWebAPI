<?php

namespace App\Http\Controllers;

use App\Models\GrainType;
use App\Models\DryingProcess;
use App\Models\PredictionEstimation;
use App\Models\SensorData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PredictionFormController extends Controller
{
    public function show()
    {
        try {
            $grainTypes = GrainType::select('grain_type_id', 'nama_jenis')->get();
            $activeProcess = DryingProcess::whereIn('status', ['pending', 'ongoing'])
                ->select('process_id', 'status', 'berat_gabah_awal', 'kadar_air_target', 'grain_type_id', 'durasi_rekomendasi')
                ->first();

            $latestEstimation = null;
            $isProcessComplete = false;
            if ($activeProcess) {
                $latestEstimation = PredictionEstimation::where('process_id', $activeProcess->process_id)
                    ->orderBy('timestamp', 'desc')
                    ->select('estimasi_durasi')
                    ->first();

                // Periksa apakah proses memiliki data lengkap
                $isProcessComplete = !is_null($activeProcess->grain_type_id) &&
                                    !is_null($activeProcess->berat_gabah_awal) &&
                                    !is_null($activeProcess->kadar_air_target);

                // Ambil data sensor terbaru
                $latestSensorData = SensorData::where('process_id', $activeProcess->process_id)
                    ->latest('timestamp')
                    ->first();
            }

            return view('prediction_form', [
                'grainTypes' => $grainTypes,
                'activeProcess' => $activeProcess,
                'latestEstimation' => $latestEstimation,
                'isProcessComplete' => $isProcessComplete,
                'latestSensorData' => $latestSensorData ?? null,
                'error' => null
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading prediction form: ' . $e->getMessage());
            return view('prediction_form', [
                'grainTypes' => collect([]),
                'activeProcess' => null,
                'latestEstimation' => null,
                'isProcessComplete' => false,
                'latestSensorData' => null,
                'error' => 'Gagal memuat data formulir. Silakan coba lagi.'
            ]);
        }
    }
}