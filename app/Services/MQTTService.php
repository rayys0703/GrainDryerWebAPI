<?php

namespace App\Services;

use App\Models\DryingProcess;
use App\Models\SensorData;
use App\Models\SensorDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\Exceptions\MqttClientException;

class MQTTService
{
    /** @var MqttClient */
    protected $client;

    /** @var array<int, array> dryer_id => [ [device_id, address, dryer_id], ... ] */
    protected $devicesByDryer = [];

    /** @var array<string, array> topic => [device_id, address, dryer_id] */
    protected $topicMap = [];

    /** @var array<int, array<int, array>> buffers per dryer: buffers[dryer_id][device_id] = data array */
    protected $buffers = [];

    /** @var array<string, bool> daftar topic yang saat ini disubscribe */
    protected $currentSubscriptions = [];

    /** @var int detik antara refresh daftar device */
    protected $refreshInterval = 15;

    /** @var int timestamp terakhir refresh */
    protected $lastRefreshAt = 0;

    /** @var int window maksimal umur data buffer (detik) untuk dianggap satu batch */
    protected $bufferWindowSeconds = 60;

    public function __construct()
    {
        $this->client = new MqttClient('127.0.0.1', 4321, 'laravel-client-' . uniqid('', true));
        $this->client->connect();

        // initial load + subscribe
        $this->reloadDevicesAndSyncSubscriptions(true);
    }

    /**
     * Loop MQTT + refresh device secara periodik.
     */
    public function subscribe()
    {
        try {
            Log::info('MQTT loop started');

            while (true) {
                // Proses pesan yang masuk (non-blocking loop sekali)
                $this->client->loop(false);

                // Periodik: refresh daftar device dan sync subscription
                $this->maybeRefreshDevices();

                // kecilkan CPU usage
                usleep(200000); // 0.2s
            }
        } catch (MqttClientException $e) {
            Log::error('Failed in MQTT loop: ' . $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in MQTT loop: ' . $e->getMessage());
        }
    }

    /**
     * Cek apakah perlu refresh device.
     */
    protected function maybeRefreshDevices(): void
    {
        $now = time();
        if ($now - $this->lastRefreshAt >= $this->refreshInterval) {
            $this->reloadDevicesAndSyncSubscriptions();
        }
    }

    /**
     * Muat ulang device dari DB, kelompokkan per dryer, dan sinkronkan subscribe/unsubscribe topik.
     */
    protected function reloadDevicesAndSyncSubscriptions(bool $initial = false): void
    {
        $this->lastRefreshAt = time();

        // Ambil device aktif
        $devices = SensorDevice::where('status', true)
            ->select('device_id', 'address', 'dryer_id')
            ->get();

        // Build struktur baru
        $newTopicMap = [];
        $newDevicesByDryer = [];
        foreach ($devices as $d) {
            if (empty($d->address) || empty($d->dryer_id)) {
                continue;
            }
            $newTopicMap[$d->address] = [
                'device_id' => (int) $d->device_id,
                'address'   => (string) $d->address,
                'dryer_id'  => (int) $d->dryer_id,
            ];
            $newDevicesByDryer[$d->dryer_id] ??= [];
            $newDevicesByDryer[$d->dryer_id][$d->device_id] = [
                'device_id' => (int) $d->device_id,
                'address'   => (string) $d->address,
                'dryer_id'  => (int) $d->dryer_id,
            ];
        }

        // Hitung perbedaan subscription
        $oldTopics = array_keys($this->currentSubscriptions);
        $newTopics = array_keys($newTopicMap);
        $toSubscribe   = array_diff($newTopics, $oldTopics);
        $toUnsubscribe = array_diff($oldTopics, $newTopics);

        // Unsubscribe topik yang hilang
        foreach ($toUnsubscribe as $topic) {
            try {
                $this->client->unsubscribe($topic);
                unset($this->currentSubscriptions[$topic]);
                Log::info("Unsubscribed from topic: {$topic}");
            } catch (\Throwable $e) {
                Log::warning("Failed to unsubscribe topic {$topic}: " . $e->getMessage());
            }

            // Bersihkan buffer device terkait (jika ada)
            if (isset($this->topicMap[$topic])) {
                $dryerId  = $this->topicMap[$topic]['dryer_id'];
                $deviceId = $this->topicMap[$topic]['device_id'];
                unset($this->buffers[$dryerId][$deviceId]);
                if (empty($this->buffers[$dryerId])) {
                    unset($this->buffers[$dryerId]);
                }
            }
        }

        // Subscribe topik baru
        foreach ($toSubscribe as $topic) {
            try {
                $this->client->subscribe($topic, function ($incomingTopic, $message) {
                    $this->handleMessage($incomingTopic, $message);
                }, 0);
                $this->currentSubscriptions[$topic] = true;
                Log::info("Subscribed to topic: {$topic}");
            } catch (\Throwable $e) {
                Log::error("Failed to subscribe topic {$topic}: " . $e->getMessage());
            }
        }

        // Update peta dan grup setelah sync
        $this->topicMap = $newTopicMap;
        $this->devicesByDryer = $newDevicesByDryer;

        if ($initial) {
            Log::info('Initial MQTT subscriptions synced: ' . implode(', ', array_keys($this->currentSubscriptions)));
        } else {
            Log::info('Refreshed MQTT subscriptions. Now subscribed: ' . implode(', ', array_keys($this->currentSubscriptions)));
        }
    }

    /**
     * Terima pesan dari topik.
     */
    protected function handleMessage(string $topic, string $message): void
    {
        Log::info('Received MQTT message', ['topic' => $topic, 'message' => $message]);

        try {
            // Identifikasi device & dryer dari topik
            $meta = $this->topicMap[$topic] ?? null;
            if (!$meta) {
                Log::warning('Message on unknown topic (not in subscription map)', ['topic' => $topic]);
                return;
            }

            $deviceId = (int) $meta['device_id'];
            $dryerId  = (int) $meta['dryer_id'];

            // Parse payload
            $data = json_decode($message, true);
            if (!is_array($data)) {
                Log::error('Invalid JSON payload', ['topic' => $topic, 'message' => $message]);
                return;
            }

            // (Opsional) fallback panel_id; tapi kita pakai topik sebagai sumber kebenaran
            if (isset($data['panel_id']) && (int)$data['panel_id'] !== $deviceId) {
                Log::warning('panel_id mismatch with topic mapping', [
                    'topic_device_id' => $deviceId,
                    'payload_panel_id' => (int)$data['panel_id'],
                ]);
            }

            // Cari / buat proses untuk dryer ini
            $dryingProcess = DryingProcess::where('dryer_id', $dryerId)
                ->whereIn('status', ['pending', 'ongoing'])
                ->first();

            if (!$dryingProcess) {
                $dryingProcess = DryingProcess::create([
                    'dryer_id'           => $dryerId,
                    'status'             => 'pending',
                    'timestamp_mulai'    => null,
                    'grain_type_id'      => null,
                    'berat_gabah_awal'   => null,
                    'kadar_air_target'   => null,
                    'durasi_rekomendasi' => 0,
                ]);
            }

            // Simpan ke DB (raw) per event supaya historis tetap ada
            $row = [
                'process_id'      => $dryingProcess->process_id,
                'device_id'       => $deviceId,
                'timestamp'       => now(),
                'kadar_air_gabah' => isset($data['grain_moisture'])     ? (float) $data['grain_moisture']     : null,
                'suhu_gabah'      => isset($data['grain_temperature'])  ? (float) $data['grain_temperature']  : null,
                'suhu_ruangan'    => isset($data['room_temperature'])   ? (float) $data['room_temperature']   : null,
                'suhu_pembakaran' => isset($data['burning_temperature'])? (float) $data['burning_temperature']: null,
                'status_pengaduk' => array_key_exists('stirrer_status', $data) ? (bool) $data['stirrer_status'] : null,
            ];
            SensorData::create($row);

            // Taruh juga ke buffer per-dryer (untuk penghitungan batch)
            $this->buffers[$dryerId][$deviceId] = $row;

            // Jika semua device aktif untuk dryer ini sudah mengirim batch dalam window, proses batch
            $expectedDevices = isset($this->devicesByDryer[$dryerId]) ? array_keys($this->devicesByDryer[$dryerId]) : [];
            $gotDevices      = isset($this->buffers[$dryerId]) ? array_keys($this->buffers[$dryerId]) : [];

            if (!empty($expectedDevices) && $this->hasAllDevices($expectedDevices, $gotDevices)) {
                // Cek umur buffer: semua timestamp harus dalam window
                if ($this->isBufferFresh($this->buffers[$dryerId])) {
                    $this->processAndSendData($dryingProcess, $dryerId);
                } else {
                    // buffer terlalu tua → reset
                    $this->buffers[$dryerId] = [];
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error processing MQTT message: ' . $e->getMessage());
        }
    }

    /**
     * Pastikan semua device yang diharapkan sudah ada di buffer dryer.
     */
    protected function hasAllDevices(array $expectedDeviceIds, array $gotDeviceIds): bool
    {
        sort($expectedDeviceIds);
        sort($gotDeviceIds);
        return $expectedDeviceIds === $gotDeviceIds;
    }

    /**
     * Cek apakah seluruh data buffer dryer masih dalam window waktu yang diizinkan.
     *
     * @param array<int, array> $bufferForDryer [device_id => row]
     */
    protected function isBufferFresh(array $bufferForDryer): bool
    {
        if (empty($bufferForDryer)) return false;
        $now = time();
        $minTs = PHP_INT_MAX;
        foreach ($bufferForDryer as $row) {
            $ts = isset($row['timestamp']) ? strtotime($row['timestamp']) : $now;
            if ($ts < $minTs) $minTs = $ts;
        }
        return ($now - $minTs) <= $this->bufferWindowSeconds;
    }

    /**
     * Proses satu batch untuk dryer tertentu, lalu kirim ke service prediksi.
     */
    protected function processAndSendData(DryingProcess $dryingProcess, int $dryerId): void
    {
        try {
            $buffer = $this->buffers[$dryerId] ?? [];
            if (empty($buffer)) {
                return;
            }

            // Pastikan proses punya field minimum agar prediksi valid.
            if (is_null($dryingProcess->grain_type_id)
                || is_null($dryingProcess->berat_gabah_awal)
                || is_null($dryingProcess->kadar_air_target)) {
                // belum siap prediksi → hanya reset buffer dryer
                $this->buffers[$dryerId] = [];
                return;
            }

            // Kumpulkan nilai rata2 dari buffer per device dalam batch ini
            $suhu_gabah_values = [];
            $kadar_air_values  = [];
            $suhu_ruang_values = [];
            $suhu_bakar_values = [];
            $stirrer_values    = [];

            foreach ($buffer as $row) {
                if (!is_null($row['suhu_gabah']))      $suhu_gabah_values[] = (float) $row['suhu_gabah'];
                if (!is_null($row['kadar_air_gabah'])) $kadar_air_values[]  = (float) $row['kadar_air_gabah'];
                if (!is_null($row['suhu_ruangan']))    $suhu_ruang_values[] = (float) $row['suhu_ruangan'];
                if (!is_null($row['suhu_pembakaran'])) $suhu_bakar_values[] = (float) $row['suhu_pembakaran'];
                if (!is_null($row['status_pengaduk'])) $stirrer_values[]    = (bool)  $row['status_pengaduk'];
            }

            if (empty($suhu_gabah_values) || empty($kadar_air_values) || empty($suhu_ruang_values) || empty($suhu_bakar_values) || empty($stirrer_values)) {
                $this->buffers[$dryerId] = [];
                return;
            }

            $payload = [
                'process_id'       => $dryingProcess->process_id,
                'grain_type_id'    => $dryingProcess->grain_type_id,
                'suhu_gabah'       => number_format(array_sum($suhu_gabah_values) / count($suhu_gabah_values), 7, '.', ''),
                'kadar_air_gabah'  => number_format(array_sum($kadar_air_values)  / count($kadar_air_values),  7, '.', ''),
                'suhu_ruangan'     => number_format(array_sum($suhu_ruang_values) / count($suhu_ruang_values), 7, '.', ''),
                'suhu_pembakaran'  => number_format(array_sum($suhu_bakar_values) / count($suhu_bakar_values), 7, '.', ''),
                'status_pengaduk'  => (bool) reset($stirrer_values),
                'kadar_air_target' => (float) $dryingProcess->kadar_air_target,
                'weight'           => (float) $dryingProcess->berat_gabah_awal,
                'timestamp'        => time(),
            ];

            if (env('ML_API')) {
                $response = Http::timeout(10)->post(rtrim(env('ML_API'), '/') . '/predict-now', $payload);
                if (!$response->successful()) {
                    Log::error('Failed to send data to prediction service', [
                        'status' => $response->status(), 'body' => $response->body()
                    ]);
                }
            } else {
                Log::warning('ML_API env is not set; skipping prediction POST.');
            }

            // Reset buffer untuk dryer ini saja (dryer lain tidak terganggu)
            $this->buffers[$dryerId] = [];

            Log::info('Batch processed & sent', [
                'dryer_id'   => $dryerId,
                'process_id' => $dryingProcess->process_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error processing and sending data: ' . $e->getMessage());
            // Jika error, jangan biarkan buffer membusuk: reset saja dryer ini
            $this->buffers[$dryerId] = [];
        }
    }

    public function stop()
    {
        try {
            $this->client->disconnect();
            Log::info('MQTT client disconnected');
        } catch (MqttClientException $e) {
            Log::error('Failed to disconnect MQTT client: ' . $e->getMessage());
        }
    }
}
