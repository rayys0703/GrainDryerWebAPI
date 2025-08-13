<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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

        <!-- AUTH PANEL -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Login</h5>
                <div id="auth-status" class="mb-2 text-muted">Status: <span id="auth-state">Belum login</span></div>

                <form id="login-form" class="row g-2">
                    <div class="col-sm-4">
                        <input type="email" class="form-control" id="email" placeholder="email"
                            value="rayya@gmail.com" required>
                    </div>
                    <div class="col-sm-4">
                        <input type="password" class="form-control" id="password" placeholder="password"
                            value="123123123" required>
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-success">Login</button>
                        <button type="button" class="btn btn-outline-secondary" id="btn-logout"
                            disabled>Logout</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($activeProcess)
            @if (!$isProcessComplete && !$isProcessWaitingData)
                <div class="alert alert-warning">
                    Proses pengeringan sudah dimulai, tetapi belum ada estimasi durasi karena data Jenis Gabah, Bobot
                    Gabah, dan Target Kadar Air belum diinput. Silakan lengkapi data di bawah ini.
                </div>
            @elseif ($isProcessWaitingData)
                <div class="alert alert-info">
                    Proses pengeringan sudah dimulai dan data awal sudah lengkap.
                    Menunggu data sensor dan hasil prediksi...
                </div>
            @endif
        @endif

        <form id="prediction-form" class="mb-4">
            <div class="mb-3">
                <label for="dryer_id" class="form-label">Pilih Bed Dryer</label>
                <select class="form-select" id="dryer_id" name="dryer_id" required disabled>
                    <option value="">Pilih Bed Dryer (login dulu)</option>
                    <!-- opsi akan diisi setelah login dari /api/bed-dryers -->
                </select>
            </div>

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
                    value="{{ $activeProcess && $activeProcess->berat_gabah_awal ? $activeProcess->berat_gabah_awal : 20000 }}"
                    min="100" step="0.1" required>
            </div>

            <div class="mb-3">
                <label for="kadar_air_target" class="form-label">Target Kadar Air (%)</label>
                <input type="number" class="form-control" id="kadar_air_target" name="kadar_air_target"
                    value="{{ $activeProcess && $activeProcess->kadar_air_target ? $activeProcess->kadar_air_target : 14 }}"
                    min="0" max="100" step="0.1" required>
            </div>

            <button type="submit" class="btn btn-primary" id="start-button" disabled>
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
                            {{ round($latestEstimation->estimasi_durasi) }} menit
                            (~{{ floor($latestEstimation->estimasi_durasi / 60) }} jam
                            {{ $latestEstimation->estimasi_durasi % 60 }} menit)
                        @elseif ($isProcessWaitingData)
                            Menunggu data sensor dan hasil prediksi...
                        @else
                            Menunggu kelengkapan data...
                        @endif
                    </p>

                </div>
            </div>
        @endif
    </div>

    <script>
        function showMessage(elementId, message, isError = true) {
            const element = document.getElementById(elementId);
            element.textContent = message;
            element.classList.remove('d-none');
            element.classList.toggle('alert-danger', isError);
            element.classList.toggle('alert-success', !isError);
            setTimeout(() => element.classList.add('d-none'), 5000);
        }

        function setAuth(token) {
            const btnLogout = document.getElementById('btn-logout');
            const startBtn = document.getElementById('start-button');
            const dryerSel = document.getElementById('dryer_id');

            if (token) {
                localStorage.setItem('token', token);
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
                document.getElementById('auth-state').innerText = 'Login';
                btnLogout.disabled = false;
                startBtn.disabled = false;
                dryerSel.disabled = false;
                loadMyBedDryers();
            } else {
                localStorage.removeItem('token');
                delete axios.defaults.headers.common['Authorization'];
                document.getElementById('auth-state').innerText = 'Belum login';
                btnLogout.disabled = true;
                startBtn.disabled = true;
                dryerSel.disabled = true;
                dryerSel.innerHTML = '<option value="">Pilih Bed Dryer (login dulu)</option>';
            }
        }

        async function loadMyBedDryers() {
            const sel = document.getElementById('dryer_id');
            sel.innerHTML = '<option value="">Memuat...</option>';
            try {
                const res = await axios.get('/api/bed-dryers');
                const list = res.data || [];
                if (list.length === 0) {
                    sel.innerHTML = '<option value="">Tidak ada Bed Dryer milik Anda</option>';
                    return;
                }
                sel.innerHTML = '<option value="">Pilih Bed Dryer</option>';
                list.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.dryer_id;
                    opt.textContent = `${d.nama} — ${d.lokasi ?? '-'}`;
                    sel.appendChild(opt);
                });
            } catch (e) {
                sel.innerHTML = '<option value="">Gagal memuat Bed Dryer</option>';
                showMessage('error-message', 'Gagal memuat Bed Dryer milik Anda');
            }
        }

        // Init token if exists
        setAuth(localStorage.getItem('token'));

        // Login
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const res = await axios.post('/api/login', {
                    email: document.getElementById('email').value,
                    password: document.getElementById('password').value,
                    device_name: 'web'
                });
                setAuth(res.data.token);
                showMessage('success-message', 'Login berhasil!', false);
            } catch (err) {
                showMessage('error-message', err.response?.data?.message || 'Login gagal');
            }
        });

        // Logout
        document.getElementById('btn-logout').addEventListener('click', async () => {
            try {
                await axios.post('/api/logout');
            } catch (_) {}
            setAuth(null);
            showMessage('success-message', 'Logout berhasil', false);
        });

        // Start
        document.getElementById('prediction-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const startButton = document.getElementById('start-button');
            startButton.disabled = true;

            const data = {
                dryer_id: parseInt(document.getElementById('dryer_id').value),
                grain_type_id: parseInt(document.getElementById('grain_type_id').value),
                berat_gabah_awal: parseFloat(document.getElementById('berat_gabah_awal').value),
                kadar_air_target: parseFloat(document.getElementById('kadar_air_target').value),
            };

            if (!data.dryer_id) {
                showMessage('error-message', 'Pilih Bed Dryer!');
                startButton.disabled = false;
                return;
            }
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
                showMessage('error-message', JSON.stringify(errorMsg));
                startButton.disabled = false;
            }
        });

        // Stop (jika ada proses)
        @if ($activeProcess)
            document.getElementById('stop-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const processId = document.getElementById('process_id').value;

                try {
                    const response = await axios.post('/api/prediction/stop', {
                        process_id: parseInt(processId)
                    });
                    showMessage('success-message', response.data.message, false);
                    setTimeout(() => window.location.reload(), 1000);
                } catch (error) {
                    const errorMsg = error.response?.data?.error || 'Gagal menghentikan prediksi!';
                    showMessage('error-message', JSON.stringify(errorMsg));
                }
            });
        @endif
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
