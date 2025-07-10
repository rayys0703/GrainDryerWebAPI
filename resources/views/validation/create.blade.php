<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Proses Pengeringan Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Buat Proses Pengeringan Baru</h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('validation.store') }}" method="POST" id="processForm">
            @csrf
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">Informasi Proses</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jenis Gabah</label>
                        <select name="grain_type_id" class="mt-1 block w-full border rounded px-2 py-1" required>
                            @if ($grainTypes->isNotEmpty())
                                <option value="" disabled selected>Pilih Jenis Gabah</option>
                                @foreach ($grainTypes as $grainType)
                                    <option value="{{ $grainType->grain_type_id }}">{{ $grainType->nama_jenis }}</option>
                                @endforeach
                            @else
                                <option value="" disabled>Tidak ada jenis gabah</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Durasi Aktual (menit)</label>
                        <input type="number" name="durasi_aktual" class="mt-1 block w-full border rounded px-2 py-1" required>
                    </div>
                </div>
            </div>

            <div id="entries">
                <div class="bg-white shadow-md rounded-lg p-6 mb-4 entry-row">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Entri Data</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Berat Gabah (kg)</label>
                            <input type="number" name="entries[0][berat_gabah]" class="mt-1 block w-full border rounded px-2 py-1" step="0.1" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kadar Air Gabah (%)</label>
                            <input type="number" name="entries[0][kadar_air_gabah]" class="mt-1 block w-full border rounded px-2 py-1" step="0.1" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Suhu Gabah (°C)</label>
                            <input type="number" name="entries[0][suhu_gabah]" class="mt-1 block w-full border rounded px-2 py-1" step="0.1" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Suhu Ruangan (°C)</label>
                            <input type="number" name="entries[0][suhu_ruangan]" class="mt-1 block w-full border rounded px-2 py-1" step="0.1" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                            <input type="datetime-local" name="entries[0][timestamp]" class="mt-1 block w-full border rounded px-2 py-1" required>
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" id="addEntry" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mb-4">Tambah Entri Baru</button>
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Simpan</button>
        </form>

        <script>
            document.getElementById('addEntry').addEventListener('click', function() {
                const entries = document.getElementById('entries');
                const newIndex = entries.getElementsByClassName('entry-row').length;
                const newEntry = document.querySelector('.entry-row').cloneNode(true);

                // Update name attributes to use new index
                newEntry.querySelectorAll('input').forEach(input => {
                    const name = input.getAttribute('name').replace('[0]', '[' + newIndex + ']');
                    input.setAttribute('name', name);
                    input.value = ''; // Reset input
                });

                entries.appendChild(newEntry);
            });
        </script>
    </div>
</body>
</html>