<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Prediksi Pengeringan Gabah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Form Prediksi Pengeringan Gabah</h2>

        @if ($error)
            <div class="alert alert-danger">{{ $error }}</div>
        @endif

        <div id="error-message" class="alert alert-danger d-none"></div>
        <div id="success-message" class="alert alert-success d-none"></div>

        @if ($activeProcess && !$isProcessComplete)
            <div class="alert alert-warning">
                Proses pengeringan sudah dimulai, tetapi belum ada estimasi durasi karena data Jenis Gabah, Bobot Gabah, dan Target Kadar Air belum diinput. Silakan lengkapi data di bawah ini.
            </div>
        @endif

        <form id="prediction-form" class="mb-4">
            <div class="mb-3">
                <label for="grain_type_id" class="form-label">Jenis Gabah</label>
                <select class="form-select" id="grain_type_id" name="grain_type_id" required>
                    <option value="">Pilih Jenis Gabah</option>
                    @foreach ($grainTypes as $grainType)
                        <option value="{{ $grainType->grain_type_id }}"
                            {{ $activeProcess && $activeProcess->grain_type_id == $grainType->grain_type_id ? 'selected' : '' }}>
                            {{ $grainType->nama_jenis }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="berat_gabah_awal" class="form-label">Bobot Gabah (kg)</label>
                <input type="number" class="form-control" id="berat_gabah_awal" name="berat_gabah_awal"
                    value="{{ $activeProcess && $activeProcess->berat_gabah_awal ? $activeProcess->berat_gabah_awal : 2000 }}"
                    min="100" step="0.1" required>
            </div>

            <div class="mb-3">
                <label for="kadar_air_target" class="form-label">Target Kadar Air (%)</label>
                <input type="number" class="form-control" id="kadar_air_target" name="kadar_air_target"
                    value="{{ $activeProcess && $activeProcess->kadar_air_target ? $activeProcess->kadar_air_target : 14 }}"
                    min="0" max="100" step="0.1" required>
            </div>

            <button type="submit" class="btn btn-primary" id="start-button"
                {{ $activeProcess && $isProcessComplete ? 'disabled' : '' }}>
                Mulai Prediksi
            </button>
        </form>

        @if ($activeProcess)
            <form id="stop-form" class="mb-4">
                <input type="hidden" id="process_id" name="process_id" value="{{ $activeProcess->process_id }}">
                <button type="submit" class="btn btn-danger">Hentikan Prediksi</button>
            </form>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Status Proses</h5>
                    <p class="card-text">Status: {{ $activeProcess->status }}</p>
                    <p class="card-text">Process ID: {{ $activeProcess->process_id }}</p>
                    <p class="card-text" id="estimated-duration">
                        Estimasi Durasi:
                        @if ($isProcessComplete && $latestEstimation && $latestEstimation->estimasi_durasi > 0)
                            {{ round($latestEstimation->estimasi_durasi) }} menit (~{{ floor($latestEstimation->estimasi_durasi / 60) }} jam {{ $latestEstimation->estimasi_durasi % 60 }} menit)
                        @else
                            Menunggu data lengkap atau prediksi...
                        @endif
                    </p>
                </div>
            </div>
        @endif
    </div>

    <script>
        // Fungsi untuk menampilkan pesan
        function showMessage(elementId, message, isError = true) {
            const element = document.getElementById(elementId);
            element.textContent = message;
            element.classList.remove('d-none');
            setTimeout(() => element.classList.add('d-none'), 5000);
        }

        // Validasi dan submit form
        document.getElementById('prediction-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const startButton = document.getElementById('start-button');
            startButton.disabled = true;

            const data = {
                grain_type_id: parseInt(document.getElementById('grain_type_id').value),
                berat_gabah_awal: parseFloat(document.getElementById('berat_gabah_awal').value),
                kadar_air_target: parseFloat(document.getElementById('kadar_air_target').value),
                user_id: 1 // Ganti dengan ID pengguna yang sesuai
            };

            // Validasi sisi klien
            if (!data.grain_type_id) {
                showMessage('error-message', 'Pilih jenis gabah!');
                startButton.disabled = false;
                return;
            }
            if (data.berat_gabah_awal < 100) {
                showMessage('error-message', 'Bobot gabah minimal 100 kg!');
                startButton.disabled = false;
                return;
            }
            if (data.kadar_air_target < 0 || data.kadar_air_target > 100) {
                showMessage('error-message', 'Target kadar air harus antara 0-100%!');
                startButton.disabled = false;
                return;
            }

            try {
                const response = await axios.post('/api/prediction/start', data);
                showMessage('success-message', response.data.message, false);
                setTimeout(() => window.location.reload(), 1000);
            } catch (error) {
                const errorMsg = error.response?.data?.error || 'Gagal memulai prediksi!';
                showMessage('error-message', errorMsg);
                startButton.disabled = false;
            }
        });

        // Hentikan prediksi
        @if ($activeProcess)
            document.getElementById('stop-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const processId = document.getElementById('process_id').value;

                try {
                    const response = await axios.post('/api/prediction/stop', { process_id: parseInt(processId) });
                    showMessage('success-message', response.data.message, false);
                    setTimeout(() => window.location.reload(), 1000);
                } catch (error) {
                    const errorMsg = error.response?.data?.error || 'Gagal menghentikan prediksi!';
                    showMessage('error-message', errorMsg);
                }
            });
        @endif
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>