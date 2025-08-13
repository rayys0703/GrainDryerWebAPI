<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Realtime Monitoring Bed Dryer</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body>
<div class="container py-4">
  <h3 class="mb-3">Realtime Monitoring</h3>

  <div id="msg" class="alert d-none"></div>

  <div class="row g-3 align-items-end mb-3">
    <div class="col-sm-6">
      <label class="form-label" for="dryer_id">Pilih Bed Dryer</label>
      <select id="dryer_id" class="form-select" disabled>
        <option value="">Login untuk memuat Bed Dryer...</option>
      </select>
    </div>
    <div class="col-sm-6 text-end">
      <button id="btn-refresh" class="btn btn-outline-primary" disabled>Refresh Sekarang</button>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Status Proses</div>
        <div class="card-body" id="process-card">
          <div class="text-muted">Belum ada data.</div>
        </div>
      </div>
      <div class="card mt-3">
        <div class="card-header">Sensor Terkini (per-device)</div>
        <div class="card-body" id="now-sensors">
          <div class="text-muted">Belum ada data.</div>
        </div>
      </div>

      <div class="card mt-3">
        <div class="card-header">Sensor Awal (per-device)</div>
        <div class="card-body" id="initial-sensors">
          <div class="text-muted">Belum ada data.</div>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Dashboard</div>
        <div class="card-body" id="dashboard">
          <div><b>Kadar Air Saat Ini:</b> <span id="current-moisture">-</span></div>
          <div><b>Estimasi Durasi:</b> <span id="estimated-duration">-</span></div>
          <div><b>Estimasi Selesai:</b> <span id="estimated-finish">-</span></div>
          <div><b>Peringatan Kadar Air:</b> <span id="moisture-warning" class="badge bg-secondary">-</span></div>
          <hr>
          <div>
            <b>Plot Singkat (5 titik terakhir):</b>
            <ul id="plots" class="small mt-2 text-muted"></ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Pakai token dari localStorage (sudah dilakukan di halaman form prediksi)
  const token = localStorage.getItem('token');
  if (token) axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

  const msgBox = document.getElementById('msg');
  function showMsg(text, type='success') {
    msgBox.textContent = text;
    msgBox.className = 'alert alert-' + type;
    msgBox.classList.remove('d-none');
    setTimeout(()=> msgBox.classList.add('d-none'), 4000);
  }

  const dryerSel = document.getElementById('dryer_id');
  const btnRefresh = document.getElementById('btn-refresh');

  async function loadMyBedDryers() {
    try {
      const res = await axios.get('/api/bed-dryers');
      const list = res.data || [];
      dryerSel.innerHTML = '<option value="">Pilih Bed Dryer</option>';
      list.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.dryer_id;
        opt.textContent = `${d.nama} — ${d.lokasi ?? '-'}`;
        dryerSel.appendChild(opt);
      });
      dryerSel.disabled = false;
      btnRefresh.disabled = false;
    } catch (e) {
      dryerSel.innerHTML = '<option value="">Gagal memuat (pastikan login)</option>';
      showMsg('Gagal memuat Bed Dryer milik Anda (401 jika belum login).', 'danger');
    }
  }

  function fillProcessCard(dp) {
    const el = document.getElementById('process-card');
    if (!dp) {
      el.innerHTML = '<div class="text-muted">Belum ada proses aktif untuk dryer ini.</div>';
      return;
    }
    el.innerHTML = `
      <div><b>Process ID:</b> ${dp.process_id}</div>
      <div><b>Jenis Gabah:</b> ${dp.nama_jenis ?? '-'}</div>
      <div><b>Berat Gabah Awal:</b> ${dp.berat_gabah_awal ?? '-'}</div>
      <div><b>Target Kadar Air:</b> ${dp.kadar_air_target ?? '-'}</div>
      <div><b>Status:</b> <span class="badge ${dp.status==='ongoing'?'bg-success':'bg-warning'}">${dp.status}</span></div>
      <div><b>Mulai:</b> ${dp.started_at ?? '-'}</div>
      <div><b>Kadar Air Awal/Akhir:</b> ${dp.kadar_air_awal ?? '-'} / ${dp.kadar_air_akhir ?? '-'}</div>
      <div><b>Durasi Rekomendasi:</b> ${dp.durasi_rekomendasi ?? '-'}</div>
      <div><b>Durasi Terlaksana:</b> ${dp.durasi_terlaksana ?? '-'}</div>
    `;
  }

  function fillSensors(elId, group) {
    const el = document.getElementById(elId);
    const data = group?.data || [];
    if (!data.length) {
      el.innerHTML = '<div class="text-muted">Belum ada data.</div>';
      return;
    }
    let html = '';
    data.forEach(s => {
      html += `
        <div class="border rounded p-2 mb-2">
          <b>${s.device_name}</b> (ID ${s.device_id})<br>
          MC: ${s.grain_moisture ?? '-'}% |
          T_gabah: ${s.grain_temperature ?? '-'}°C |
          T_room: ${s.room_temperature ?? '-'}°C |
          T_burn: ${s.burning_temperature ?? '-'}°C |
          Stirrer: ${s.stirrer_status === null ? '-' : (s.stirrer_status ? 'On' : 'Off')}<br>
          <small class="text-muted">${s.timestamp}</small>
        </div>
      `;
    });
    const avg = [];
    if (group.averageGrainMoisture)    avg.push(`Rata-rata MC: ${group.averageGrainMoisture}%`);
    if (group.averageGrainTemperature) avg.push(`Rata-rata T_gabah: ${group.averageGrainTemperature}°C`);
    if (group.averageRoomTemperature)  avg.push(`Rata-rata T_room: ${group.averageRoomTemperature}°C`);

    el.innerHTML = html + (avg.length ? `<div class="small text-muted">${avg.join(' • ')}</div>` : '');
  }

  function fillDashboard(d) {
    document.getElementById('current-moisture').innerText   = d.current_moisture ?? '-';
    document.getElementById('estimated-duration').innerText = d.estimated_duration ?? '-';
    document.getElementById('estimated-finish').innerText   = d.estimated_finish ?? '-';
    const warn = document.getElementById('moisture-warning');
    warn.className = 'badge ' + (d.is_moisture_warning ? 'bg-danger' : 'bg-success');
    warn.innerText = d.is_moisture_warning ? 'Warning' : 'Normal';

    // Tampilkan list 5 titik terakhir secara ringkas
    const ul = document.getElementById('plots');
    const rows = [];
    (d.moisture_data || []).forEach(x => rows.push(`[MC ${x.time}] ${x.data}`));
    (d.grain_temperature_data || []).forEach(x => rows.push(`[Tg ${x.time}] ${x.data}`));
    (d.room_temperature_data || []).forEach(x => rows.push(`[Tr ${x.time}] ${x.data}`));
    (d.burning_temperature_data || []).forEach(x => rows.push(`[Tb ${x.time}] ${x.data}`));
    ul.innerHTML = rows.length ? rows.map(r => `<li>${r}</li>`).join('') : '<li class="text-muted">Belum ada data.</li>';
  }

  async function fetchRealtime(dryerId) {
    const res = await axios.get('/api/realtime-data', { params: { dryer_id: dryerId } });
    const j = res.data;
    fillProcessCard(j.drying_process);
    fillSensors('now-sensors', j.now_sensors);
    fillSensors('initial-sensors', j.initial_sensors);
  }

  async function fetchDashboard(dryerId) {
    const res = await axios.get('/api/dashboard-data', { params: { dryer_id: dryerId } });
    fillDashboard(res.data);
  }

  let timer = null;
  async function startPolling() {
    const id = dryerSel.value;
    if (!id) return;
    const run = async () => {
      try {
        await Promise.all([fetchRealtime(id), fetchDashboard(id)]);
      } catch (e) {
        showMsg('Gagal memuat data realtime/dashboard (cek login & permission)', 'danger');
      }
    };
    await run();
    clearInterval(timer);
    timer = setInterval(run, 5000);
  }

  // Events
  dryerSel.addEventListener('change', startPolling);
  btnRefresh.addEventListener('click', startPolling);

  // init
  if (token) {
    loadMyBedDryers();
  } else {
    showMsg('Anda perlu login (token Sanctum) agar bisa memuat daftar Bed Dryer.', 'warning');
  }
</script>

</body>
</html>
