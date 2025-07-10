@extends('layouts.app')

@section('title', 'Edit Proses Pengeringan')

@section('content')
    <div class="fluent-card p-6">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6">Edit Proses Pengeringan #{{ $process->process_id }}</h1>

        <form action="{{ route('drying-processes.update', $process->process_id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            @if(Auth::user()->role === 'admin')
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700">Pengguna</label>
                <select name="user_id" id="user_id" required
                        class="mt-1 block w-full fluent-input @error('user_id') border-red-500 @enderror">
                    @foreach($users as $user)
                        <option value="{{ $user->user_id }}" {{ old('user_id', $process->user_id) == $user->user_id ? 'selected' : '' }}>
                            {{ $user->nama }} ({{ $user->username }})
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <div>
                <label for="grain_type_id" class="block text-sm font-medium text-gray-700">Jenis Gabah</label>
                <select name="grain_type_id" id="grain_type_id" required
                        class="mt-1 block w-full fluent-input @error('grain_type_id') border-red-500 @enderror">
                    <option value="">Pilih Jenis Gabah</option>
                    @foreach($grainTypes as $grainType)
                        <option value="{{ $grainType->grain_type_id }}" {{ old('grain_type_id', $process->grain_type_id) == $grainType->grain_type_id ? 'selected' : '' }}>
                            {{ $grainType->nama_jenis }}
                        </option>
                    @endforeach
                </select>
                @error('grain_type_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="berat_gabah" class="block text-sm font-medium text-gray-700">Berat Gabah (kg)</label>
                <input type="number" step="0.01" name="berat_gabah" id="berat_gabah" value="{{ old('berat_gabah', $process->berat_gabah) }}" required
                       class="mt-1 block w-full fluent-input @error('berat_gabah') border-red-500 @enderror">
                @error('berat_gabah')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="kadar_air_target" class="block text-sm font-medium text-gray-700">Kadar Air Target (%)</label>
                <input type="number" step="0.1" name="kadar_air_target" id="kadar_air_target" value="{{ old('kadar_air_target', $process->kadar_air_target) }}" required
                       class="mt-1 block w-full fluent-input @error('kadar_air_target') border-red-500 @enderror">
                @error('kadar_air_target')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="durasi_rekomendasi" class="block text-sm font-medium text-gray-700">Durasi Rekomendasi (menit)</label>
                <input type="number" name="durasi_rekomendasi" id="durasi_rekomendasi" value="{{ old('durasi_rekomendasi', $process->durasi_rekomendasi) }}" required
                       class="mt-1 block w-full fluent-input @error('durasi_rekomendasi') border-red-500 @enderror">
                @error('durasi_rekomendasi')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="timestamp_selesai" class="block text-sm font-medium text-gray-700">Waktu Selesai (Opsional)</label>
                <input type="datetime-local" name="timestamp_selesai" id="timestamp_selesai"
                       value="{{ old('timestamp_selesai', $process->timestamp_selesai ? $process->timestamp_selesai->format('Y-m-d\TH:i') : '') }}"
                       class="mt-1 block w-full fluent-input @error('timestamp_selesai') border-red-500 @enderror">
                @error('timestamp_selesai')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="kadar_air_akhir" class="block text-sm font-medium text-gray-700">Kadar Air Akhir (%) (Opsional)</label>
                <input type="number" step="0.1" name="kadar_air_akhir" id="kadar_air_akhir" value="{{ old('kadar_air_akhir', $process->kadar_air_akhir) }}"
                       class="mt-1 block w-full fluent-input @error('kadar_air_akhir') border-red-500 @enderror">
                @error('kadar_air_akhir')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="durasi_aktual" class="block text-sm font-medium text-gray-700">Durasi Aktual (menit) (Opsional)</label>
                <input type="number" name="durasi_aktual" id="durasi_aktual" value="{{ old('durasi_aktual', $process->durasi_aktual) }}"
                       class="mt-1 block w-full fluent-input @error('durasi_aktual') border-red-500 @enderror">
                @error('durasi_aktual')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="durasi_terlaksana" class="block text-sm font-medium text-gray-700">Durasi Terlaksana (menit) (Opsional)</label>
                <input type="number" name="durasi_terlaksana" id="durasi_terlaksana" value="{{ old('durasi_terlaksana', $process->durasi_terlaksana) }}"
                       class="mt-1 block w-full fluent-input @error('durasi_terlaksana') border-red-500 @enderror">
                @error('durasi_terlaksana')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="status" required
                        class="mt-1 block w-full fluent-input @error('status') border-red-500 @enderror">
                    <option value="pending" {{ old('status', $process->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="ongoing" {{ old('status', $process->status) == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                    <option value="completed" {{ old('status', $process->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end space-x-2">
                <a href="{{ route('drying-processes.index') }}" class="fluent-button bg-gray-600 hover:bg-gray-700">Batal</a>
                <button type="submit" class="fluent-button">Perbarui Proses</button>
            </div>
        </form>
    </div>
@endsection