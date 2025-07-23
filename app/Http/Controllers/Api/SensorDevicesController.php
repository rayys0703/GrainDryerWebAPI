<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorDevice;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SensorDevicesController extends Controller
{
    public function index()
    {
        try {
            $devices = SensorDevice::all();

            $formattedDevices = $devices->map(function ($device) {
                return [
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'address' => $device->address,
                    'status' => (bool) $device->status,
                    'created_at' => $device->created_at ? Carbon::parse($device->created_at)->locale('id')->isoFormat('D MMMM Y') : null,
                    'updated_at' => $device->updated_at ? Carbon::parse($device->updated_at)->locale('id')->isoFormat('D MMMM Y') : null,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $formattedDevices,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data perangkat: ' . $e->getMessage(),
            ], 500);
        }
    }
}