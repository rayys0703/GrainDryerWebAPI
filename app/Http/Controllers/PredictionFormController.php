<?php

namespace App\Http\Controllers;

use App\Models\GrainType;
use App\Models\DryingProcess;
use App\Models\PredictionEstimation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PredictionFormController extends Controller
{
    public function show()
    {
        try {
            $grainTypes = GrainType::select('grain_type_id', 'nama_jenis')->get();
            $activeProcess = DryingProcess::whereIn('status', ['pending', 'ongoing'])
                ->select('process_id', 'status', 'berat_gabah_awal', 'kadar_air_target', 'grain_type_id')
                ->first();

            // Ambil durasi_rekomendasi dari prediction_estimations (data terakhir)
            $latestEstimation = null;
            if ($activeProcess) {
                $latestEstimation = PredictionEstimation::where('process_id', $activeProcess->process_id)
                    ->orderBy('timestamp', 'desc')
                    ->select('estimasi_durasi')
                    ->first();
            }

            return view('prediction_form', [
                'grainTypes' => $grainTypes,
                'activeProcess' => $activeProcess,
                'latestEstimation' => $latestEstimation,
                'error' => null
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading prediction form: ' . $e->getMessage());
            return view('prediction_form', [
                'grainTypes' => collect([]),
                'activeProcess' => null,
                'latestEstimation' => null,
                'error' => 'Gagal memuat data formulir. Silakan coba lagi.'
            ]);
        }
    }
}