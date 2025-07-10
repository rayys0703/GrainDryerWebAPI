<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GrainType;
use Illuminate\Http\Request;

class GrainTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $grainTypes = GrainType::all();
        return view('grain_types.index', compact('grainTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('grain_types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_jenis' => ['required', 'string', 'max:100', 'unique:grain_types,nama_jenis'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        GrainType::create($request->all());

        return redirect()->route('grain-types.index')->with('success', 'Jenis gabah berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $grainType = GrainType::findOrFail($id);
        return view('grain_types.show', compact('grainType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $grainType = GrainType::findOrFail($id);
        return view('grain_types.edit', compact('grainType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $grainType = GrainType::findOrFail($id);

        $request->validate([
            'nama_jenis' => ['required', 'string', 'max:100', 'unique:grain_types,nama_jenis,' . $id . ',grain_type_id'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $grainType->update($request->all());

        return redirect()->route('grain-types.index')->with('success', 'Jenis gabah berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $grainType = GrainType::findOrFail($id);
        $grainType->delete();

        return redirect()->route('grain-types.index')->with('success', 'Jenis gabah berhasil dihapus!');
    }
}