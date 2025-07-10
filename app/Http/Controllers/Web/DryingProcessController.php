<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DryingProcess;
use App\Models\GrainType;
use App\Models\User; // Untuk dropdown user, jika admin bisa memilih
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DryingProcessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = DryingProcess::with('user', 'grainType');

        if (Auth::user()->role !== 'admin') {
            $query->where('user_id', Auth::user()->user_id);
        }

        $dryingProcesses = $query->get();
        return view('drying_processes.index', compact('dryingProcesses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $grainTypes = GrainType::all();
        // Jika admin, bisa memilih user lain, jika tidak, otomatis user_id sendiri
        $users = Auth::user()->role === 'admin' ? User::all() : collect([Auth::user()]);
        return view('drying_processes.create', compact('grainTypes', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'grain_type_id' => ['required', 'exists:grain_types,grain_type_id'],
            'berat_gabah' => ['required', 'numeric', 'min:0'],
            'kadar_air_target' => ['required', 'numeric', 'min:0', 'max:100'],
            'durasi_rekomendasi' => ['required', 'integer', 'min:0'],
            // 'timestamp_mulai' => ['nullable', 'date'], // Kita set otomatis di controller
            'timestamp_selesai' => ['nullable', 'date'],
            'kadar_air_akhir' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'durasi_aktual' => ['nullable', 'integer', 'min:0'],
            'durasi_terlaksana' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:pending,ongoing,completed'],
        ]);

        $userId = Auth::user()->role === 'admin' && $request->has('user_id')
            ? $request->user_id
            : Auth::user()->user_id;

        DryingProcess::create([
            'user_id' => $userId,
            'grain_type_id' => $request->grain_type_id,
            'timestamp_mulai' => now(), // Mengisi waktu mulai secara otomatis
            'timestamp_selesai' => $request->timestamp_selesai,
            'berat_gabah' => $request->berat_gabah,
            'kadar_air_target' => $request->kadar_air_target,
            'kadar_air_akhir' => $request->kadar_air_akhir,
            'durasi_rekomendasi' => $request->durasi_rekomendasi,
            'durasi_aktual' => $request->durasi_aktual,
            'durasi_terlaksana' => $request->durasi_terlaksana ?? 0,
            'status' => $request->status ?? 'pending',
        ]);

        return redirect()->route('drying-processes.index')->with('success', 'Proses pengeringan berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $process = DryingProcess::with('user', 'grainType')->findOrFail($id);

        if (Auth::user()->role !== 'admin' && $process->user_id !== Auth::user()->user_id) {
            abort(403, 'Unauthorized action.');
        }

        return view('drying_processes.show', compact('process'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $process = DryingProcess::with('user', 'grainType')->findOrFail($id);

        if (Auth::user()->role !== 'admin' && $process->user_id !== Auth::user()->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $grainTypes = GrainType::all();
        $users = Auth::user()->role === 'admin' ? User::all() : collect([Auth::user()]);

        return view('drying_processes.edit', compact('process', 'grainTypes', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $process = DryingProcess::findOrFail($id);

        if (Auth::user()->role !== 'admin' && $process->user_id !== Auth::user()->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'grain_type_id' => ['required', 'exists:grain_types,grain_type_id'],
            'berat_gabah' => ['required', 'numeric', 'min:0'],
            'kadar_air_target' => ['required', 'numeric', 'min:0', 'max:100'],
            'durasi_rekomendasi' => ['required', 'integer', 'min:0'],
            'timestamp_selesai' => ['nullable', 'date'],
            'kadar_air_akhir' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'durasi_aktual' => ['nullable', 'integer', 'min:0'],
            'durasi_terlaksana' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:pending,ongoing,completed'],
        ]);

        $process->update($request->all());

        return redirect()->route('drying-processes.index')->with('success', 'Proses pengeringan berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $process = DryingProcess::findOrFail($id);

        if (Auth::user()->role !== 'admin' && $process->user_id !== Auth::user()->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $process->delete();

        return redirect()->route('drying-processes.index')->with('success', 'Proses pengeringan berhasil dihapus!');
    }
}