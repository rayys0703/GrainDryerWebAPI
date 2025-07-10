@extends('layouts.app')

@section('title', 'Daftar Proses Pengeringan')

@section('content')
    <div class="fluent-card p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Daftar Proses Pengeringan</h1>
            <a href="{{ route('drying-processes.create') }}" class="fluent-button">Mulai Proses Baru</a>
        </div>

        @if($dryingProcesses->isEmpty())
            <p class="text-gray-600">Belum ada proses pengeringan yang terdaftar.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="py-3 px-4 border-b">ID</th>
                            <th class="py-3 px-4 border-b">Pengguna</th>
                            <th class="py-3 px-4 border-b">Jenis Gabah</th>
                            <th class="py-3 px-4 border-b">Mulai</th>
                            <th class="py-3 px-4 border-b">Selesai</th>
                            <th class="py-3 px-4 border-b">Berat (kg)</th>
                            <th class="py-3 px-4 border-b">KA Target</th>
                            <th class="py-3 px-4 border-b">Status</th>
                            <th class="py-3 px-4 border-b">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($dryingProcesses as $process)
                            <tr>
                                <td class="py-3 px-4">{{ $process->process_id }}</td>
                                <td class="py-3 px-4">{{ $process->user->nama ?? 'N/A' }}</td>
                                <td class="py-3 px-4">{{ $process->grainType->nama_jenis ?? 'N/A' }}</td>
                                <td class="py-3 px-4">{{ $process->timestamp_mulai->format('d/m/Y H:i') }}</td>
                                <td class="py-3 px-4">{{ $process->timestamp_selesai ? $process->timestamp_selesai->format('d/m/Y H:i') : '-' }}</td>
                                <td class="py-3 px-4">{{ $process->berat_gabah }}</td>
                                <td class="py-3 px-4">{{ $process->kadar_air_target }}%</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if($process->status === 'completed') bg-green-100 text-green-800
                                        @elseif($process->status === 'ongoing') bg-blue-100 text-blue-800
                                        @else bg-yellow-100 text-yellow-800 @endif">
                                        {{ ucfirst($process->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <a href="{{ route('drying-processes.show', $process->process_id) }}" class="text-gray-600 hover:text-gray-800 text-sm mr-2">Detail</a>
                                    <a href="{{ route('drying-processes.edit', $process->process_id) }}" class="text-blue-600 hover:text-blue-800 text-sm mr-2">Edit</a>
                                    <form action="{{ route('drying-processes.destroy', $process->process_id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus proses ini?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection