<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorData extends Model
{
    use HasFactory;

    protected $table = 'sensor_data';
    protected $primaryKey = 'sensor_id';
    protected $fillable = [
        'process_id',
        'device_id',
        'timestamp',
        'kadar_air_gabah',
        'suhu_gabah',
        'suhu_ruangan',
        'suhu_pembakaran',
        'status_pengaduk'
    ];

    public function dryingProcess()
    {
        return $this->belongsTo(DryingProcess::class, 'process_id', 'process_id');
    }

    public function sensorDevice()
    {
        return $this->belongsTo(SensorDevice::class, 'device_id', 'device_id');
    }

    public function device()
    {
        return $this->belongsTo(SensorDevice::class, 'device_id');
    }
}