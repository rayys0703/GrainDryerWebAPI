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
    protected $topics = [];
    protected $sensorDataBuffer = [];

    public function __construct()
    {
        $this->topics = SensorDevice::select('device_id', 'address')->get()->keyBy('device_id');
        if ($this->topics->isEmpty()) {
            Log::warning('No sensor devices found in database');
        }

        $this->client = new MqttClient('broker.hivemq.com', 1883, 'laravel-client-' . uniqid());
        $this->client->connect(null, true, [
            'username' => 'graindryer',
            'password' => 'polindra'
        ]);
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
            $device = $this->topics->get($panel_id);
            if (!$device || $device->address !== $topic) {
                Log::error('Invalid panel_id or topic mismatch', ['panel_id' => $panel_id, 'topic' => $topic]);
                return;
            }

            $this->sensorDataBuffer[$panel_id] = [
                'point_id' => $panel_id,
                'grain_temperature' => isset($data['grain_temperature']) ? (float) $data['grain_temperature'] : null,
                'grain_moisture' => isset($data['grain_moisture']) ? (float) $data['grain_moisture'] : null,
                'room_temperature' => isset($data['room_temperature']) ? (float) $data['room_temperature'] : null,
                'burning_temperature' => isset($data['burning_temperature']) ? (float) $data['burning_temperature'] : null,
                'stirrer_status' => isset($data['stirrer_status']) ? (bool) $data['stirrer_status'] : null,
                'timestamp' => time()
            ];
            Log::info('Stored in buffer', ['panel_id' => $panel_id, 'buffer_size' => count($this->sensorDataBuffer)]);

            if (count($this->sensorDataBuffer) === $this->topics->count()) {
                Log::info('Buffer complete, processing data', ['buffer' => $this->sensorDataBuffer]);
                $this->processAndSendData();
            }
        } catch (\Exception $e) {
            Log::error('Error processing MQTT message: ' . $e->getMessage());
        }
    }

    protected function processAndSendData()
    {
        try {
            $dryingProcess = DryingProcess::whereIn('status', ['pending', 'ongoing'])->first();
            if (!$dryingProcess) {
                Log::info('No active drying process found, clearing buffer');
                $this->sensorDataBuffer = [];
                return;
            }

            $buffer_age = time() - min(array_map(fn($data) => $data['timestamp'], $this->sensorDataBuffer));
            if ($buffer_age > 60) {
                Log::warning('Buffer timeout, clearing incomplete data');
                $this->sensorDataBuffer = [];
                return;
            }

            $points = [];
            foreach ($this->sensorDataBuffer as $panel_id => $data) {
                $points[] = [
                    'point_id' => $data['point_id'],
                    'grain_temperature' => $data['grain_temperature'],
                    'grain_moisture' => $data['grain_moisture'],
                    'room_temperature' => $data['room_temperature'],
                    'burning_temperature' => $data['burning_temperature'],
                    'stirrer_status' => $data['stirrer_status'],
                ];
            }

            $payload = [
                'process_id' => $dryingProcess->process_id,
                'grain_type_id' => $dryingProcess->grain_type_id,
                'points' => $points,
                'weight' => (float) $dryingProcess->berat_gabah_awal,
                'timestamp' => time()
            ];

            $response = Http::timeout(10)->post('http://127.0.0.1:5000/process-sensor-data', $payload);
            if ($response->successful()) {
                Log::info('Data sent to prediction service', ['process_id' => $dryingProcess->process_id]);
                foreach ($points as $point) {
                    SensorData::create([
                        'process_id' => $dryingProcess->process_id,
                        'device_id' => $point['point_id'],
                        'timestamp' => now(),
                        'kadar_air_gabah' => $point['grain_moisture'],
                        'suhu_gabah' => $point['grain_temperature'],
                        'suhu_ruangan' => $point['room_temperature'],
                        'suhu_pembakaran' => $point['burning_temperature'],
                        'status_pengaduk' => $point['stirrer_status'],
                    ]);
                }
            } else {
                Log::error('Failed to send data to prediction service', [
                    'status' => $response->status(),
                    'body' => $response->body()
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