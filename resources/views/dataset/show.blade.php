<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Data Pelatihan - Process ID {{ $process->process_id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Detail Data Pelatihan - Process ID {{ $process->process_id }}</h1>

        <a href="{{ route('dataset.index') }}" class="mb-4 inline-block bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Kembali</a>

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Informasi Proses</h2>
            <p><strong>Process ID:</strong> {{ $process->process_id }}</p>
            <p><strong>Durasi Pengeringan:</strong> {{ $trainingGroup->drying_time }} menit</p>
        </div>

        @if ($trainingData->isNotEmpty())
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">Data Pelatihan</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Suhu Gabah (°C)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kadar Air Gabah (%)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Suhu Ruangan (°C)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Berat (kg)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($trainingData as $data)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $data->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ number_format($data->grain_temperature, 1) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ number_format($data->grain_moisture, 1) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ number_format($data->room_temperature, 1) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ number_format($data->weight, 1) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4" role="alert">
                Tidak ada data pelatihan yang tersedia untuk proses ini.
            </div>
        @endif
    </div>
</body>
</html>