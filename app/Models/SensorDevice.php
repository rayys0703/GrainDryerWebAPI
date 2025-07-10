<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorDevice extends Model
{
    use HasFactory;

    protected $table = 'sensor_devices';
    protected $primaryKey = 'device_id';
    protected $fillable = [
        'device_name',
        'address',
        'created_at',
    ];

    // Nonaktifkan updated_at
    public $timestamps = false;

    public function sensorData()
    {
        return $this->hasMany(SensorData::class, 'device_id');
    }

    // Override setUpdatedAt untuk mencegah Laravel mencoba mengisi updated_at
    public function setUpdatedAt($value)
    {
        return $this;
    }
}