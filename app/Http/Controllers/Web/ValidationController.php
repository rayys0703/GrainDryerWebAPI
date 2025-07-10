<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DryingProcess;
use App\Models\TrainingGroup;
use App\Models\TrainingData;
use App\Models\SensorData;
use App\Models\GrainType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ValidationController extends Controller
{
    public function index()
    {
        $completedProcesses = DryingProcess::where('status', 'completed')->with('sensorData')->get();
        return view('validation.index', compact('completedProcesses'));
    }

    public function validateProcess(Request $request, $processId)
    {
        $request->validate([
            'durasi_aktual' => 'required|integer|min:0'
        ]);

        try {
            $process = DryingProcess::findOrFail($processId);
            $process->update(['durasi_aktual' => $request->durasi_aktual, 'status' => 'validated']);

            Log::info("Validated process $processId with actual duration: {$request->durasi_aktual}");
            return redirect()->route('validation.index')->with('success', 'Validasi berhasil.');
        } catch (\Exception $e) {
            Log::error("Error validating process $processId: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat validasi.');
        }
    }

    public function show($processId)
    {
        $process = DryingProcess::findOrFail($processId);
        $sensorData = SensorData::where('process_id', $processId)->orderBy('timestamp')->get();

        return view('validation.show', compact('process', 'sensorData'));
    }

    public function create()
    {
        $grainTypes = GrainType::all();
        return view('validation.create', compact('grainTypes'));
    }

    public function store(Request $request)
    {
        Log::info('Received form data: ', $request->all());

        try {
            $validatedData = $request->validate([
                'grain_type_id' => 'required|exists:grain_types,grain_type_id',
                'durasi_aktual' => 'required|integer|min:0',
                'entries.*.berat_gabah' => 'required|numeric|min:0',
                'entries.*.kadar_air_gabah' => 'required|numeric|min:0',
                'entries.*.suhu_gabah' => 'required|numeric|min:0',
                'entries.*.suhu_ruangan' => 'required|numeric|min:0',
                'entries.*.timestamp' => 'required|date',
            ]);

            Log::info('Validation passed for form data.');

            $userId = Auth::id() ?? 1; // Ambil user_id dari session, default 1 jika tidak ada
            Log::info("Creating process with user_id: $userId, grain_type_id: {$request->grain_type_id}, durasi_aktual: {$request->durasi_aktual}");

            $process = DryingProcess::create([
                'user_id' => $userId,
                'grain_type_id' => $request->grain_type_id,
                'berat_gabah' => $request->entries[0]['berat_gabah'] ?? 0,
                'kadar_air_target' => 14.0,
                'status' => 'completed',
                'durasi_rekomendasi' => $request->durasi_aktual,
                'durasi_aktual' => $request->durasi_aktual,
                'timestamp_mulai' => $request->entries[0]['timestamp'] ?? now(),
                'timestamp_selesai' => $request->entries[0]['timestamp'] ?? now(),
            ]);

            Log::info("Process created with process_id: {$process->process_id}");

            $trainingGroup = TrainingGroup::create([
                'process_id' => $process->process_id,
                'drying_time' => $request->durasi_aktual,
            ]);

            Log::info("Training group created with id: {$trainingGroup->id}");

            foreach ($request->entries as $index => $entry) {
                Log::info("Creating sensor data and training data for entry $index: ", $entry);
                SensorData::create([
                    'process_id' => $process->process_id,
                    'berat_gabah' => $entry['berat_gabah'],
                    'kadar_air_gabah' => $entry['kadar_air_gabah'],
                    'suhu_gabah' => $entry['suhu_gabah'],
                    'suhu_ruangan' => $entry['suhu_ruangan'],
                    'timestamp' => $entry['timestamp'],
                ]);

                TrainingData::create([
                    'training_group_id' => $trainingGroup->id,
                    'grain_temperature' => $entry['suhu_gabah'],
                    'grain_moisture' => $entry['kadar_air_gabah'],
                    'room_temperature' => $entry['suhu_ruangan'],
                    'weight' => $entry['berat_gabah'],
                ]);
            }

            Log::info("Created new process $process->process_id with training data.");
            return redirect()->route('validation.index')->with('success', 'Proses dan data pelatihan berhasil disimpan.');
        } catch (\ValidationException $e) {
            Log::error('Validation failed: ', $e->errors());
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error("Error creating process: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    public function indexDataset()
    {
        $trainingGroups = TrainingGroup::with('process')->get();
        return view('dataset.index', compact('trainingGroups'));
    }

    public function showDataset($processId)
    {
        $process = DryingProcess::findOrFail($processId);
        $trainingGroup = TrainingGroup::where('process_id', $processId)->first();
        $trainingData = TrainingData::where('training_group_id', $trainingGroup ? $trainingGroup->id : null)->get();

        return view('dataset.show', compact('process', 'trainingGroup', 'trainingData'));
    }
}