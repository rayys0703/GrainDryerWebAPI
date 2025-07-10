@extends('layouts.app')

@section('title', 'Edit Jenis Gabah')

@section('content')
    <div class="fluent-card p-6">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6">Edit Jenis Gabah: {{ $grainType->nama_jenis }}</h1>

        <form action="{{ route('grain-types.update', $grainType->grain_type_id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="nama_jenis" class="block text-sm font-medium text-gray-700">Nama Jenis</label>
                <input type="text" name="nama_jenis" id="nama_jenis" value="{{ old('nama_jenis', $grainType->nama_jenis) }}" required
                       class="mt-1 block w-full fluent-input @error('nama_jenis') border-red-500 @enderror">
                @error('nama_jenis')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi (Opsional)</label>
                <textarea name="deskripsi" id="deskripsi" rows="4"
                          class="mt-1 block w-full fluent-input @error('deskripsi') border-red-500 @enderror">{{ old('deskripsi', $grainType->deskripsi) }}</textarea>
                @error('deskripsi')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end space-x-2">
                <a href="{{ route('grain-types.index') }}" class="fluent-button bg-gray-600 hover:bg-gray-700">Batal</a>
                <button type="submit" class="fluent-button">Perbarui</button>
            </div>
        </form>
    </div>
@endsection