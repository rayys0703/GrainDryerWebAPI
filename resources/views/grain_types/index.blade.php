@extends('layouts.app')

@section('title', 'Daftar Jenis Gabah')

@section('content')
    <div class="fluent-card p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Daftar Jenis Gabah</h1>
            <a href="{{ route('grain-types.create') }}" class="fluent-button">Tambah Jenis Gabah</a>
        </div>

        @if($grainTypes->isEmpty())
            <p class="text-gray-600">Belum ada jenis gabah yang terdaftar.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="py-3 px-4 border-b">ID</th>
                            <th class="py-3 px-4 border-b">Nama Jenis</th>
                            <th class="py-3 px-4 border-b">Deskripsi</th>
                            <th class="py-3 px-4 border-b">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($grainTypes as $grainType)
                            <tr>
                                <td class="py-3 px-4">{{ $grainType->grain_type_id }}</td>
                                <td class="py-3 px-4">{{ $grainType->nama_jenis }}</td>
                                <td class="py-3 px-4">{{ Str::limit($grainType->deskripsi, 50) }}</td>
                                <td class="py-3 px-4">
                                    <a href="{{ route('grain-types.edit', $grainType->grain_type_id) }}" class="text-blue-600 hover:text-blue-800 text-sm mr-2">Edit</a>
                                    <form action="{{ route('grain-types.destroy', $grainType->grain_type_id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus jenis gabah ini?')">Hapus</button>
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