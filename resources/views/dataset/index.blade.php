<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pelatihan</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Data Pelatihan</h1>

        <div class="bg-white shadow-md rounded-lg p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Process ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi Pengeringan (menit)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detail</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($trainingGroups as $group)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $group->process->process_id ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $group->drying_time }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('dataset.show', $group->process->process_id ?? 0) }}" class="text-blue-500 hover:text-blue-700">Lihat Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>