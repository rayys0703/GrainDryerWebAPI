@extends('layouts.app')

@section('title', 'Detail Proses Pengeringan')

@section('content')
    <div class="fluent-card p-6">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6">Detail Proses Pengeringan #{{ $process->process_id }}</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
            <div>
                <p><strong class="font-medium">Pengguna:</strong> {{ $process->user->nama ?? 'N/A' }}</p>
                <p><strong class="font-medium">Username:</strong> {{ $process->user->username ?? 'N/A' }}</p>
                <p><strong class="font-medium">Jenis Gabah:</strong> {{ $process->grainType->nama_jenis ?? 'N/A' }}</p>
                <p><strong class="font-medium">Berat Gabah:</strong> {{ $process->berat_gabah }} kg</p>
                <p><strong class="font-medium">Kadar Air Target:</strong> {{ $process->kadar_air_target }}%</p>
                <p><strong class="font-medium">Kadar Air Akhir:</strong> {{ $process->kadar_air_akhir ?? '-' }}%</p>
            </div>
            <div>
                <p><strong class="font-medium">Waktu Mulai:</strong> {{ $process->timestamp_mulai->format('d F Y, H:i:s') }}</p>
                <p><strong class="font-medium">Waktu Selesai:</strong> {{ $process->timestamp_selesai ? $process->timestamp_selesai->format('d F Y, H:i:s') : '-' }}</p>
                <p><strong class="font-medium">Durasi Rekomendasi:</strong> {{ $process->durasi_rekomendasi }} menit</p>
                <p><strong class="font-medium">Durasi Aktual:</strong> {{ $process->durasi_aktual ?? '-' }} menit</p>
                <p><strong class="font-medium">Durasi Terlaksana:</strong> {{ $process->durasi_terlaksana }} menit</p>
                <p><strong class="font-medium">Status:</strong>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                        @if($process->status === 'completed') bg-green-100 text-green-800
                        @elseif($process->status === 'ongoing') bg-blue-100 text-blue-800
                        @else bg-yellow-100 text-yellow-800 @endif">
                        {{ ucfirst($process->status) }}
                    </span>
                </p>
            </div>
        </div>

        <div class="mt-8 flex justify-end space-x-2">
            <a href="{{ route('drying-processes.index') }}" class="fluent-button bg-gray-600 hover:bg-gray-700">Kembali</a>
            <a href="{{ route('drying-processes.edit', $process->process_id) }}" class="fluent-button">Edit Proses</a>
        </div>
    </div>
@endsection