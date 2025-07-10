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

        <a href="{{ route('validation.index') }}" class="mb-4 inline-block bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Kembali</a>

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

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Informasi Proses</h2>
            @php
                $firstSensor = $sensorData->first();
                $lastSensor = $sensorData->last();
                $beratAwal = $firstSensor ? $firstSensor->berat_gabah ?? $process->berat_gabah : $process->berat_gabah;
                $beratAkhir = $lastSensor ? $lastSensor->berat_gabah ?? $process->berat_gabah : $process->berat_gabah;
                $kadarAirAwal = $firstSensor ? $firstSensor->kadar_air_gabah ?? null : null;
                $kadarAirAkhir = $lastSensor ? $lastSensor->kadar_air_gabah ?? null : null;
                $suhuGabahAwal = $firstSensor ? $firstSensor->suhu_gabah ?? null : null;
                $suhuGabahAkhir = $lastSensor ? $lastSensor->suhu_gabah ?? null : null;
                $suhuRuanganAwal = $firstSensor ? $firstSensor->suhu_ruangan ?? null : null;
                $suhuRuanganAkhir = $lastSensor ? $lastSensor->suhu_ruangan ?? null : null;
            @endphp
            <p><strong>Berat Gabah Awal:</strong> {{ $beratAwal ?? 'Tidak tersedia' }} kg</p>
            <p><strong>Berat Gabah Akhir:</strong> {{ $beratAkhir ?? 'Tidak tersedia' }} kg</p>
            <p><strong>Kadar Air Gabah Awal:</strong> {{ $kadarAirAwal ?? 'Tidak tersedia' }} %</p>
            <p><strong>Kadar Air Gabah Akhir:</strong> {{ $kadarAirAkhir ?? 'Tidak tersedia' }} %</p>
            <p><strong>Suhu Gabah Awal:</strong> {{ $suhuGabahAwal ?? 'Tidak tersedia' }} °C</p>
            <p><strong>Suhu Gabah Akhir:</strong> {{ $suhuGabahAkhir ?? 'Tidak tersedia' }} °C</p>
            <p><strong>Suhu Ruangan Awal:</strong> {{ $suhuRuanganAwal ?? 'Tidak tersedia' }} °C</p>
            <p><strong>Suhu Ruangan Akhir:</strong> {{ $suhuRuanganAkhir ?? 'Tidak tersedia' }} °C</p>
            <p><strong>Berat Gabah:</strong> {{ $process->berat_gabah }} kg</p>
            <p><strong>Durasi Rekomendasi:</strong> {{ $process->durasi_rekomendasi }} menit</p>
            <p><strong>Durasi Aktual:</strong> {{ $process->durasi_aktual ?? 'Belum divalidasi' }} menit</p>
        </div>
    </div>
</body>
</html>