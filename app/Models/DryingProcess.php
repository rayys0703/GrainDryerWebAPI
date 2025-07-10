<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DryingProcess extends Model
{
    protected $table = 'drying_process';
    protected $primaryKey = 'process_id';
    public $incrementing = true;
    protected $fillable = [
        'user_id',
        'grain_type_id',
        'timestamp_mulai',
        'timestamp_selesai',
        'berat_gabah_awal',
        'berat_gabah_akhir',
        'kadar_air_awal',
        'kadar_air_target',
        'kadar_air_akhir',
        'durasi_rekomendasi',
        'durasi_aktual',
        'durasi_terlaksana',
        'avg_estimasi_durasi',
        'status',
        'catatan',
        'lokasi', 
    ];

    public function grainType()
    {
        return $this->belongsTo(GrainType::class, 'grain_type_id', 'grain_type_id');
    }

    public function sensorData()
    {
        return $this->hasMany(SensorData::class, 'process_id', 'process_id');
    }
}