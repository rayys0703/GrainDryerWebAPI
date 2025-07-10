<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Proses Pengeringan</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Validasi Proses Pengeringan</h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <a href="{{ route('validation.create') }}" class="mb-4 inline-block bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Tambah Proses Baru</a>

        <div class="bg-white shadow-md rounded-lg p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Process ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Berat Gabah Awal (kg)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi Rekomendasi (menit)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi Aktual (menit)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detail</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($completedProcesses as $process)
                        @php
                            $firstSensor = \App\Models\SensorData::where('process_id', $process->process_id)->orderBy('timestamp')->first();
                            $beratAwal = $firstSensor ? $firstSensor->berat_gabah ?? $process->berat_gabah : $process->berat_gabah;
                        @endphp
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $process->process_id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ number_format($beratAwal, 1) }} kg</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $process->durasi_rekomendasi }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $process->durasi_aktual ?? 'Belum divalidasi' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if (!$process->durasi_aktual)
                                    <form action="{{ route('validation.process', $process->process_id) }}" method="POST">
                                        @csrf
                                        <div class="flex items-center space-x-2">
                                            <input type="number" name="durasi_aktual" class="border rounded px-2 py-1" placeholder="Masukkan durasi aktual" required>
                                            <button type="submit" class="bg-blue-500 text-white px-4 py-1 rounded hover:bg-blue-600">Validasi</button>
                                        </div>
                                    </form>
                                @else
                                    Sudah divalidasi
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('validation.show', $process->process_id) }}" class="text-blue-500 hover:text-blue-700">Lihat Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>