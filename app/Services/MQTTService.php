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
    protected $client;
    protected $topics = [];         // device_id => {device_id, address, dryer_id}
    protected $sensorDataBuffer = [];

    public function __construct()
    {
        $this->topics = SensorDevice::where('status', true)
            ->select('device_id', 'address', 'dryer_id')
            ->get()
            ->keyBy('device_id');

        if ($this->topics->isEmpty()) {
            Log::warning('No active sensor devices found in database');
        }

        $this->client = new MqttClient('127.0.0.1', 4321, 'laravel-client-' . uniqid());
        $this->client->connect();
    }

    public function subscribe()
    {
        try {
            foreach ($this->topics as $device) {
                $this->client->subscribe($device->address, function ($topic, $message) {
                    $this->handleMessage($topic, $message);
                }, 0);
            }
            Log::info('Subscribed to MQTT topics: ' . implode(', ', $this->topics->pluck('address')->toArray()));
            $this->client->loop(true);
        } catch (MqttClientException $e) {
            Log::error('Failed to subscribe to MQTT topics: ' . $e->getMessage());
        }
    }

    protected function handleMessage($topic, $message)
    {
        Log::info('Received MQTT message', ['topic' => $topic, 'message' => $message]);
        try {
            $data = json_decode($message, true);
            if (!$data || !isset($data['panel_id'])) {
                Log::error('Invalid MQTT message format', ['topic' => $topic, 'message' => $message]);
                return;
            }

            $panel_id = (int) $data['panel_id'];
            $device   = $this->topics->get($panel_id);
            if (!$device || $device->address !== $topic) {
                Log::error('Invalid panel_id or topic mismatch', ['panel_id' => $panel_id, 'topic' => $topic]);
                return;
            }

            // Proses per dryer
            $dryingProcess = DryingProcess::where('dryer_id', $device->dryer_id)
                ->whereIn('status', ['pending', 'ongoing'])
                ->first();

            if (!$dryingProcess) {
                $dryingProcess = DryingProcess::create([
                    'dryer_id'           => $device->dryer_id,
                    'status'             => 'pending',
                    'timestamp_mulai'    => null,
                    'grain_type_id'      => null,
                    'berat_gabah_awal'   => null,
                    'kadar_air_target'   => null,
                    'durasi_rekomendasi' => 0,
                ]);
            }

            $this->sensorDataBuffer[$panel_id] = [
                'process_id'       => $dryingProcess->process_id,
                'device_id'        => $panel_id,
                'timestamp'        => now(),
                'kadar_air_gabah'  => isset($data['grain_moisture']) ? (float) $data['grain_moisture'] : null,
                'suhu_gabah'       => isset($data['grain_temperature']) ? (float) $data['grain_temperature'] : null,
                'suhu_ruangan'     => isset($data['room_temperature']) ? (float) $data['room_temperature'] : null,
                'suhu_pembakaran'  => isset($data['burning_temperature']) ? (float) $data['burning_temperature'] : null,
                'status_pengaduk'  => isset($data['stirrer_status']) ? (bool) $data['stirrer_status'] : null,
            ];

            if (count($this->sensorDataBuffer) === $this->topics->count()) {
                $this->processAndSendData($dryingProcess);
            }
        } catch (\Exception $e) {
            Log::error('Error processing MQTT message: ' . $e->getMessage());
        }
    }

    protected function processAndSendData($dryingProcess)
    {
        try {
            foreach ($this->sensorDataBuffer as $data) {
                SensorData::create($data);
            }
            Log::info('Sensor data saved', ['process_id' => $dryingProcess->process_id, 'records' => count($this->sensorDataBuffer)]);

            if (is_null($dryingProcess->grain_type_id) || is_null($dryingProcess->berat_gabah_awal) || is_null($dryingProcess->kadar_air_target)) {
                $this->sensorDataBuffer = [];
                return;
            }

            $buffer_age = time() - min(array_map(fn($d) => $d['timestamp']->timestamp, $this->sensorDataBuffer));
            if ($buffer_age > 60) {
                $this->sensorDataBuffer = [];
                return;
            }

            $suhu_gabah_values      = array_filter(array_column($this->sensorDataBuffer, 'suhu_gabah'), fn($v) => !is_null($v));
            $kadar_air_gabah_values = array_filter(array_column($this->sensorDataBuffer, 'kadar_air_gabah'), fn($v) => !is_null($v));
            $suhu_ruangan_values    = array_filter(array_column($this->sensorDataBuffer, 'suhu_ruangan'), fn($v) => !is_null($v));
            $suhu_pembakaran_values = array_filter(array_column($this->sensorDataBuffer, 'suhu_pembakaran'), fn($v) => !is_null($v));
            $status_pengaduk_values = array_filter(array_column($this->sensorDataBuffer, 'status_pengaduk'), fn($v) => !is_null($v));

            if (empty($suhu_gabah_values) || empty($kadar_air_gabah_values) || empty($suhu_ruangan_values) || empty($suhu_pembakaran_values) || empty($status_pengaduk_values)) {
                $this->sensorDataBuffer = [];
                return;
            }

            $payload = [
                'process_id'        => $dryingProcess->process_id,
                'grain_type_id'     => $dryingProcess->grain_type_id,
                'suhu_gabah'        => number_format(array_sum($suhu_gabah_values) / count($suhu_gabah_values), 7, '.', ''),
                'kadar_air_gabah'   => number_format(array_sum($kadar_air_gabah_values) / count($kadar_air_gabah_values), 7, '.', ''),
                'suhu_ruangan'      => number_format(array_sum($suhu_ruangan_values) / count($suhu_ruangan_values), 7, '.', ''),
                'suhu_pembakaran'   => number_format(array_sum($suhu_pembakaran_values) / count($suhu_pembakaran_values), 7, '.', ''),
                'status_pengaduk'   => (bool) reset($status_pengaduk_values),
                'kadar_air_target'  => (float) $dryingProcess->kadar_air_target,
                'weight'            => (float) $dryingProcess->berat_gabah_awal,
                'timestamp'         => time(),
            ];

            $response = Http::timeout(10)->post(env('ML_API') . '/predict-now', $payload);
            if (!$response->successful()) {
                Log::error('Failed to send data to prediction service', [
                    'status' => $response->status(), 'body' => $response->body()
                ]);
            }

            $this->sensorDataBuffer = [];
        } catch (\Exception $e) {
            Log::error('Error processing and sending data: ' . $e->getMessage());
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
