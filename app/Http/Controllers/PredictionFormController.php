<?php

namespace App\Http\Controllers;

use App\Models\GrainType;
use App\Models\DryingProcess;
use App\Models\PredictionEstimation;
use Illuminate\Http\Request;

class PredictionFormController extends Controller
{
    public function show(Request $request)
    {
        $grainTypes = GrainType::orderBy('nama_jenis')->get();

        // Proses terakhir (boleh filter milik user jika perlu)
        $activeProcess = DryingProcess::latest('created_at')->first();

        $isProcessComplete = false;
        $isProcessWaitingData = false;
        $latestEstimation = null;

        if ($activeProcess) {
            $isProcessComplete = $activeProcess->status === 'completed';

            // Ambil estimasi terakhir kalau ada
            $latestEstimation = PredictionEstimation::where('process_id', $activeProcess->process_id)
                ->latest('timestamp')->first();

            // Kalau proses tidak completed, tapi data input sudah lengkap → berarti tinggal tunggu sensor/prediksi
            if (!$isProcessComplete &&
                $activeProcess->grain_type_id &&
                $activeProcess->berat_gabah_awal &&
                $activeProcess->kadar_air_target) {
                $isProcessWaitingData = true;
            }
        }

        // bedDryers diambil client-side via API
        $bedDryers = collect();

        return view('prediction_form', compact(
            'grainTypes',
            'bedDryers',
            'activeProcess',
            'isProcessComplete',
            'isProcessWaitingData',
            'latestEstimation'
        ))->with('error', null);
    }
}
